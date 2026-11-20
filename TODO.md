# TODO (z testu webu 20. 11. 2026)

Hotovo v kódu: A1–A3, B1–B3, C1 (aria-label), C5 (Olomouc v title/H1), část D1 a D3.
Níže je, co zbývá. Většinou jde o obsah nebo rozhodnutí, ne o kód.

## C. UX a obsah
- [ ] **C1 ověřit ručně**: klik na ikonu košíku v hlavičce. Je to obyčejný `<a href="/cart">`
      bez JS, takže automat nejspíš klikl mimo. Ověřit na mobilu i desktopu.
- [ ] **C2 výběr roku v kalendáři** (flatpickr, `resources/js/app.js`). Rok v hlavičce se
      dá přepsat ručně, ale není to vidět. Při `showMonths: 2` flatpickr nepodporuje
      `monthSelectorType: 'dropdown'`. Varianty: vlastní select „měsíc + rok“ nad
      kalendářem, nebo zapnout `allowInput` s parsováním „06.07.2027 do 07.07.2027“.
- [ ] **C3 sety a subwoofery**: založit v adminu sety (produkty s položkami v `bundle_items`),
      např. „Obřad“ (bedna + bezdrátový mikrofon), „Svatba s kapelou“ (2× top + 2× sub),
      „DJ párty“. Doporučené na titulní stránce přepnout na sety. Kód sety umí
      (košík i MCP je rozbalují a počítají cenou setu).
- [ ] **C4 FAQ s cenami**: doplnit do `config/seo.php` (otázka „Kolik stojí…“) konkrétní
      rozpětí a odkaz na sety. Potřebuje reálná čísla od Martina. FAQ odpovědi jsou
      v HTML (`<details>`), bez JS je vidět.
- [ ] **B2 orientační cena dovozu**: košík teď ukazuje „dovoz a zapojení: dle místa,
      potvrdíme“. Až bude ceník (např. Olomouc zdarma / X Kč za km), dopočítat ho
      v `ReservationBooking`.

## D. SEO
- [ ] **D1** doplnit `openingHours` do LocalBusiness (`layouts/app.blade.php`), chybí údaje.
- [ ] **D2 vstupní stránky**: Ozvučení svatby Olomouc, Ozvučení kapely, Pronájem
      reproduktorů Olomouc, případně Prostějov, Přerov, Šternberk. Potřebují vlastní
      texty a sety (C3), jinak hrozí tenký/duplicitní obsah.
- [ ] **D3 `/.well-known/`**: Laravel routy existují, ale hosting vrací 403 dřív, než
      request dojde do PHP. Požádat Váš Hosting o povolení `/.well-known/mcp*`
      (nebo celého `/.well-known/`). Do té doby funguje `/mcp/server-card.json`.

## Ostatní
- [ ] laravel/mcp je na v0.1.1. Upgrade by odstranil vlastní opravy v
      `App\Mcp\Servers\BaseServer` a `App\Mcp\Methods\CallTool`; zatím drží.
- [ ] JSON-RPC `id` jako řetězec balíček v0.1 odmítá (spec ho povoluje).
- [ ] Testy pod DDEV běží proti lokální MariaDB (DDEV přebíjí `DB_CONNECTION` z
      phpunit.xml), takže `RefreshDatabase` maže lokální dev data.
