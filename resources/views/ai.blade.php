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
            <li><code class="text-ink">create-reservation</code> — založení rezervace</li>
        </ul>

        <div class="mt-12 rounded-2xl px-6 py-6" style="background:var(--ink)">
            <p class="font-display text-lg font-bold" style="color:var(--paper)">Raději napřímo?</p>
            <p class="mt-1 text-sm" style="color:color-mix(in oklab, var(--paper) 72%, transparent)">Zavolejte {{ config('contact.phone') }} nebo napište {{ config('contact.email') }}.</p>
        </div>
    </div>
@endsection
