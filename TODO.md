# TODO (z testu webu 20. 11. 2026)

Hotovo v kódu: A1–A3, B1–B3, C1 (aria-label), C3 (sety), C4 (FAQ s cenami), C5, D2 (3 vstupní stránky), část D1 a D3, pásma dopravy.
Níže je, co zbývá. Většinou jde o obsah nebo rozhodnutí, ne o kód.

## C. UX a obsah
- [ ] **C1 ověřit ručně**: klik na ikonu košíku v hlavičce. Je to obyčejný `<a href="/cart">`
      bez JS, takže automat nejspíš klikl mimo. Ověřit na mobilu i desktopu.
- [ ] **C2 výběr roku v kalendáři** (flatpickr, `resources/js/app.js`). Rok v hlavičce se
      dá přepsat ručně, ale není to vidět. Při `showMonths: 2` flatpickr nepodporuje
      `monthSelectorType: 'dropdown'`. Varianty: vlastní select „měsíc + rok“ nad
      kalendářem, nebo zapnout `allowInput` s parsováním „06.07.2027 do 07.07.2027“.
- [ ] **C3 zkontrolovat sety** (`database/migrations/2026_11_21_000000_create_event_sets.php`):
      složení a ceny navrhl Claude. Hlavně: potřebují topy Nexo PS15R2 a subwoofery MT18
      opravdu „Amprack FP“? Úpravy pak v adminu.
- [ ] **C4/B2 ceny dopravy** (`config/contact.php`, `delivery_zones`): 600 / 1 000 / 1 600 Kč,
      dál dohodou. Navrženo bez ceníku, potvrdit. Texty FAQ (`config/seo.php`, `config/landing.php`)
      ceny opakují, při změně upravit i tam.

## D. SEO
- [ ] **D1** doplnit `openingHours` do LocalBusiness (`layouts/app.blade.php`), chybí údaje.
- [ ] **D2 další vstupní stránky**: stránky pro Prostějov, Přerov, Šternberk, až budou mít
      vlastní obsah (reference, fotky z akcí). Stávající: `config/landing.php`.
- [ ] **D3 `/.well-known/`**: Laravel routy existují, ale hosting vrací 403 dřív, než
      request dojde do PHP. Požádat Váš Hosting o povolení `/.well-known/mcp*`
      (nebo celého `/.well-known/`). Do té doby funguje `/mcp/server-card.json`.

## Ostatní
- [ ] laravel/mcp je na v0.1.1. Upgrade by odstranil vlastní opravy v
      `App\Mcp\Servers\BaseServer` a `App\Mcp\Methods\CallTool`; zatím drží.
- [ ] JSON-RPC `id` jako řetězec balíček v0.1 odmítá (spec ho povoluje).
- [ ] Testy pod DDEV běží proti lokální MariaDB (DDEV přebíjí `DB_CONNECTION` z
      phpunit.xml), takže `RefreshDatabase` maže lokální dev data.
- [ ] Smazat testovací rezervaci **#22** („TEST Claude (smazat)“, 30. 12. 2027) v adminu.
- [ ] Admin MCP token v Claude Code (`.mcp.json` / `MCP_TOKEN`) neodpovídá produkci (401).
