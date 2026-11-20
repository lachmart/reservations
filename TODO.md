# TODO (z testu webu 20. 11. 2026)

Hotovo v kódu (C1: hláška už nepřekrývá ikonu košíku; C2 výběr měsíce v kalendáři; ceny dopravy 600/1 000/1 600 Kč potvrzené, instalace a obsluha za příplatek):
 A1–A3, B1–B3, C1 (aria-label), C3 (sety), C4 (FAQ s cenami), C5, D2 (3 vstupní stránky), část D1 a D3, pásma dopravy.
Níže je, co zbývá. Většinou jde o obsah nebo rozhodnutí, ne o kód.

## C. UX a obsah
- [ ] **Nexo rack**: PS15R2 potřebují Nexo rack, který v katalogu není. Set Svatba s kapelou
      proto jede s PS10R2. Až bude rack v katalogu, jde případně udělat i větší set s PS15.

## D. SEO
- [ ] **D1** doplnit `openingHours` do LocalBusiness (`layouts/app.blade.php`), chybí údaje.
- [ ] **D2 další vstupní stránky**: stránky pro Prostějov, Přerov, Šternberk, až budou mít
      vlastní obsah (reference, fotky z akcí). Stávající: `config/landing.php`.
- [ ] **D3 `/.well-known/`** (zatím odloženo, hosting neřešíme): Laravel routy existují, ale hosting vrací 403 dřív, než
      request dojde do PHP. Požádat Váš Hosting o povolení `/.well-known/mcp*`
      (nebo celého `/.well-known/`). Do té doby funguje `/mcp/server-card.json`.

## Ostatní
- [ ] Ceny setů a dopravy jsou v textech na více místech: `config/seo.php`, `config/landing.php`,
      `public/llms.txt`, `resources/views/ai.blade.php`. Při změně ceny v adminu upravit i tam.
- [ ] laravel/mcp je na v0.1.1. Upgrade by odstranil vlastní opravy v
      `App\Mcp\Servers\BaseServer` a `App\Mcp\Methods\CallTool`; zatím drží.
- [ ] JSON-RPC `id` jako řetězec balíček v0.1 odmítá (spec ho povoluje).
- [ ] Testy pod DDEV běží proti lokální MariaDB (DDEV přebíjí `DB_CONNECTION` z
      phpunit.xml), takže `RefreshDatabase` maže lokální dev data.
