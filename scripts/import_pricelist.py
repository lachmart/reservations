#!/usr/bin/env python3
"""
Idempotent product-catalog importer for the Maraka rental app, driven through the
admin MCP server. Parses the Czech AV price-list xlsx and upserts categories and
products (match by exact product name -> update; otherwise create).

Grouping rules (validated against technika_cenik-rent-1.xlsx):
  - Section header rows (ZVUKOVÁ TECHNIKA, …) and the column header are skipped.
  - A "xxx" price or a name starting with ">" marks a sub-item.
      * For CABLE categories (Kabel*, Redukce*, cívka, Quadro) each sub-item is
        its own product (different lengths).
      * Otherwise the sub-item is a component of the preceding base product and is
        folded into its description ("Obsahuje: …") — e.g. racks.
  - Blank price -> left untouched on update; 0 on create.

Usage:
  MCP_TOKEN=... python3 scripts/import_pricelist.py <file.xlsx> [--base URL] [--dry-run]
  (token also read from .env.production if MCP_TOKEN is unset)
"""
import argparse, json, os, re, ssl, sys, time, urllib.request
from xml.etree import ElementTree as ET

DEFAULT_BASE = "https://api.marakarental.cz/mcp/admin"
NS = {'m': 'http://schemas.openxmlformats.org/spreadsheetml/2006/main'}
SECTIONS = {"ZVUKOVÁ TECHNIKA", "SVĚTELNÁ TECHNIKA", "DISTRO KABELY 230V, 400V", "STAGE SUPPORT"}


def token():
    t = os.environ.get("MCP_TOKEN")
    if t:
        return t.strip()
    for path in (".env.production", ".env"):
        if os.path.exists(path):
            for line in open(path):
                if line.startswith("MCP_TOKEN="):
                    return line.split("=", 1)[1].strip()
    sys.exit("No MCP token: set MCP_TOKEN or add it to .env.production")


def parse(path):
    z = __import__("zipfile").ZipFile(path)
    names = z.namelist()
    shared = []
    if 'xl/sharedStrings.xml' in names:
        for si in ET.fromstring(z.read('xl/sharedStrings.xml')).findall('m:si', NS):
            shared.append("".join(t.text or "" for t in si.iter('{%s}t' % NS['m'])))

    def colnum(ref):
        c = "".join(ch for ch in ref if ch.isalpha()); n = 0
        for ch in c: n = n * 26 + (ord(ch) - 64)
        return n

    def rv(row):
        cells = {}
        for c in row.findall('m:c', NS):
            ref = c.get('r'); t = c.get('t'); v = c.find('m:v', NS); val = ""
            if v is not None:
                val = shared[int(v.text)] if t == 's' and v.text and v.text.isdigit() else (v.text or "")
            if val.strip() != "": cells[colnum(ref)] = val.strip()
        return [cells.get(i, "") for i in range(1, 6)]

    sheet = sorted(n for n in names if re.match(r'xl/worksheets/sheet\d+\.xml', n))[0]
    rows = [rv(r) for r in ET.fromstring(z.read(sheet)).findall('.//m:row', NS)]

    is_num = lambda x: bool(re.fullmatch(r"\d+([.,]\d+)?", x))
    is_cable = lambda c: c.startswith("Kabel") or c.startswith("Redukce") or "cívka" in c or c == "Quadro"

    products = []; cur = None; curcat = ""
    for cat, name, cnt, unit, price in rows:
        if cat in SECTIONS and not name: continue
        if cat.lower() == "kategorie" or not name: continue
        if cat: curcat = cat
        nm = name.lstrip("> ").strip()
        if name.startswith(">") or price.lower() == "xxx":
            if is_cable(curcat):
                products.append({"category": curcat, "name": nm, "stock": cnt,
                                 "price": "" if price.lower() == "xxx" else price, "components": []})
            elif cur:
                cur["components"].append(nm)
            continue
        cur = {"category": curcat, "name": nm, "stock": cnt,
               "price": price if is_num(price) else "", "components": []}
        products.append(cur)
    return products


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("xlsx")
    ap.add_argument("--base", default=DEFAULT_BASE)
    ap.add_argument("--dry-run", action="store_true")
    args = ap.parse_args()
    TOK = token(); CTX = ssl.create_default_context()

    def mcp(name, a):
        body = json.dumps({"jsonrpc": "2.0", "id": 1, "method": "tools/call",
                           "params": {"name": name, "arguments": a}}).encode()
        req = urllib.request.Request(args.base, data=body, headers={
            "Content-Type": "application/json", "Authorization": "Bearer " + TOK})
        d = json.loads(urllib.request.urlopen(req, context=CTX, timeout=30).read())
        r = d["result"]; t = r["content"][0]["text"]
        try: return (not r["isError"]), json.loads(t)
        except Exception: return (not r["isError"]), t

    products = parse(args.xlsx)
    print(f"parsed {len(products)} products from {args.xlsx}  (target: {args.base})")

    _, cats = mcp("list-categories", {})
    cat_id = {c["name"]: c["id"] for c in cats.get("categories", [])}
    _, prods = mcp("list-products", {"limit": 1000})
    prod_id = {p["name"]: p["id"] for p in prods.get("products", [])}
    print(f"existing: {len(cat_id)} categories, {len(prod_id)} products")

    created = updated = failed = 0; fails = []
    for p in products:
        cat = p["category"]
        if cat and cat not in cat_id:
            if args.dry_run:
                cat_id[cat] = -1
            else:
                ok, d = mcp("create-category", {"name": cat})
                if ok: cat_id[cat] = d["id"]
                else: fails.append(("cat " + cat, d)); continue
        cid = cat_id.get(cat)
        stock = int(p["stock"]) if p["stock"].isdigit() else 1
        price = float(p["price"].replace(",", ".")) if p["price"] else None
        desc = ("Obsahuje: " + ", ".join(p["components"])) if p["components"] else None

        if p["name"] in prod_id:  # UPDATE
            a = {"id": prod_id[p["name"]], "stock": stock}
            if cid: a["category_id"] = cid
            if price is not None: a["price_per_day"] = price     # keep manual price if sheet blank
            if desc is not None: a["description"] = desc
            if args.dry_run: updated += 1; continue
            ok, d = mcp("update-product", a); updated += ok; failed += (not ok)
            if not ok: fails.append((p["name"], d))
        else:  # CREATE
            a = {"name": p["name"], "category_id": cid, "stock": stock,
                 "price_per_day": price if price is not None else 0}
            if desc: a["description"] = desc
            if args.dry_run: created += 1; continue
            ok, d = mcp("create-product", a); created += ok; failed += (not ok)
            if not ok: fails.append((p["name"], d))
        time.sleep(0.04)

    print(f"\n{'DRY-RUN ' if args.dry_run else ''}created={created} updated={updated} failed={failed}")
    for n, e in fails[:20]: print("  !", n, "->", str(e)[:100])


if __name__ == "__main__":
    main()
