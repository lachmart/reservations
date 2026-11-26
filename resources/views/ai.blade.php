@extends('layouts.app')

@section('title', 'Rezervace přes AI asistenta — Maraka rental')
@section('meta_description', 'Maraka rental nabízí MCP server — AI asistenti (Claude, ChatGPT) mohou procházet techniku, ověřit dostupnost na termín a vytvořit rezervaci automaticky.')

@section('content')
    <div class="mx-auto max-w-3xl">
        <div class="kicker">Pro vývojáře &amp; AI asistenty</div>
        <h1 class="mt-3 text-display-md font-extrabold text-ink">Rezervace přes AI asistenta</h1>
        <p class="mt-4 max-w-2xl text-lg leading-relaxed text-muted">
            Náš katalog je dostupný přes <a href="https://modelcontextprotocol.io" class="font-semibold text-accent-ink underline dark:text-accent">Model Context Protocol</a> (MCP).
            AI asistent jako Claude nebo ChatGPT umí projít nabídku techniky, ověřit dostupnost na váš termín a založit rezervaci — bez ručního klikání.
        </p>

        <div class="mt-8 grid gap-4 sm:grid-cols-3">
            @foreach([
                ['Procházení', 'AI vypíše dostupnou techniku podle kategorie i ceny.'],
                ['Dostupnost', 'Zkontroluje volné kusy na konkrétní termín.'],
                ['Rezervace', 'Založí rezervaci se jménem, e-mailem a termínem.'],
            ] as [$t, $d])
                <div class="card"><div class="card-body">
                    <div class="font-display text-lg font-bold text-ink">{{ $t }}</div>
                    <p class="mt-1 text-sm text-muted">{{ $d }}</p>
                </div></div>
            @endforeach
        </div>

        <h2 class="mt-12 font-display text-xl font-bold text-ink">Připojení</h2>
        <p class="mt-2 text-muted">Veřejný rezervační MCP server (HTTP, bez autentizace):</p>
        <pre class="mt-3 overflow-x-auto rounded-xl border border-line bg-surface p-4 text-sm text-ink"><code>POST {{ url('/mcp/reservations') }}</code></pre>

        <p class="mt-4 text-muted">Konfigurace pro klienty typu Claude (<code>.mcp.json</code>):</p>
        <pre class="mt-3 overflow-x-auto rounded-xl border border-line bg-surface p-4 text-xs leading-relaxed text-ink"><code>{
  "mcpServers": {
    "maraka-rental": {
      "type": "http",
      "url": "{{ url('/mcp/reservations') }}"
    }
  }
}</code></pre>

        <h2 class="mt-10 font-display text-xl font-bold text-ink">Nástroje</h2>
        <ul class="mt-3 space-y-2 text-muted">
            <li><code class="text-ink">list-products</code> — výpis dostupné techniky</li>
            <li><code class="text-ink">check-product-availability</code> — dostupnost na termín</li>
            <li><code class="text-ink">create-reservation</code> — založení rezervace včetně položek; cenu spočítá server</li>
        </ul>

        <h2 class="mt-10 font-display text-xl font-bold text-ink">Hotové sety</h2>
        <p class="mt-2 text-muted">Pro typické akce doporučte set: v <code>list-products</code> mají <code>is_bundle: true</code> a v <code>components</code> vidíte, co obsahují. Do rezervace se zadávají jako jedna položka.</p>
        <ul class="mt-3 space-y-1 text-muted">
            <li><a href="{{ url('/products/set-svatebni-obrad') }}" class="font-semibold text-ink hover:underline">Svatební obřad</a> — 1 300 Kč / den</li>
            <li><a href="{{ url('/products/set-proslovy-a-hudba') }}" class="font-semibold text-ink hover:underline">Proslovy a hudba</a> — 950 Kč / den</li>
            <li><a href="{{ url('/products/set-dj-party') }}" class="font-semibold text-ink hover:underline">DJ párty</a> — 1 390 Kč / den</li>
            <li><a href="{{ url('/products/set-ziva-kapela') }}" class="font-semibold text-ink hover:underline">Živá kapela</a> — 6 390 Kč / den</li>
            <li><a href="{{ url('/products/set-velky-zvuk-nexo') }}" class="font-semibold text-ink hover:underline">Velký zvuk Nexo</a> — 6 490 Kč / den</li>
        </ul>

        <h2 class="mt-10 font-display text-xl font-bold text-ink">Ukázka kompletní rezervace</h2>
        <p class="mt-2 text-muted">Každý krok je samostatný <code>POST {{ url('/mcp/reservations') }}</code> s JSON-RPC 2.0. Rezervace se uloží jen tehdy, když jsou dostupné všechny položky. Je nepotvrzená a nezaplacená, dokud ji nepotvrdíme.</p>
        <pre class="mt-3 overflow-x-auto rounded-xl border border-line bg-surface p-4 text-xs leading-relaxed text-ink"><code>// 1. initialize
{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2025-06-18","capabilities":{},"clientInfo":{"name":"my-agent","version":"1.0"}}}

// 2. katalog: id, cena za den, sety (is_bundle)
{"jsonrpc":"2.0","id":2,"method":"tools/call","params":{"name":"list-products","arguments":{}}}

// 3. dostupnost na termín (pro každou položku)
{"jsonrpc":"2.0","id":3,"method":"tools/call","params":{"name":"check-product-availability","arguments":{"product_id":12,"start_date":"2027-07-06 10:00","end_date":"2027-07-07 10:00"}}}

// 4. rezervace se všemi položkami najednou
{"jsonrpc":"2.0","id":4,"method":"tools/call","params":{"name":"create-reservation","arguments":{
  "name":"Jana Nováková","phone":"+420 777 123 456","email":"jana@example.com",
  "start_date":"2027-07-06 10:00","end_date":"2027-07-07 10:00",
  "event_type":"Svatba","event_place":"Olomouc","delivery_zone":"olomouc",
  "items":[{"product_id":12,"quantity":2},{"product_id":15,"quantity":2}]
}}}</code></pre>
        <p class="mt-3 text-sm text-muted">Stačí telefon, nebo e-mail. Cena v odpovědi je za techniku a dopravu podle zvoleného pásma (<code>delivery_zone</code>); instalaci nebo obsluhu (<code>service</code>) naceníme zvlášť. Popis serveru: <a href="{{ url('/mcp/server-card.json') }}" class="font-semibold text-accent-ink underline dark:text-accent">server-card.json</a>.</p>

        <div class="mt-12 rounded-2xl px-6 py-6" style="background:var(--ink)">
            <p class="font-display text-lg font-bold" style="color:var(--paper)">Raději napřímo?</p>
            <p class="mt-1 text-sm" style="color:color-mix(in oklab, var(--paper) 72%, transparent)">Zavolejte {{ config('contact.phone') }} nebo napište {{ config('contact.email') }}.</p>
        </div>
    </div>
@endsection
