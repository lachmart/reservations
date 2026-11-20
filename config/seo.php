<?php

/*
 * Search-focused copy for the public catalogue.
 *
 * Category pages are keyed by category slug. Each entry targets the phrases
 * people actually type when they want to rent (e.g. "pronájem ozvučení
 * Olomouc"), instead of the bare category name. Categories without an entry
 * fall back to generic copy built from the category name.
 */

return [
    // Towns we deliver to / serve; used in copy and LocalBusiness areaServed.
    'area_served' => ['Olomouc', 'Prostějov', 'Přerov', 'Šternberk', 'Litovel', 'Uničov', 'Hranice', 'Olomoucký kraj'],

    'home' => [
        'title' => 'Pronájem ozvučení a osvětlení Olomouc | Maraka rental',
        'meta_description' => 'Půjčovna ozvučení, světel a pódiové techniky v Olomouci. Aparatura pro svatby, koncerty, plesy i firemní akce až do 600 osob. Rezervace online nebo na telefonu.',
    ],

    'categories' => [
        'zvuk' => [
            'title' => 'Pronájem ozvučení Olomouc — reproboxy, mixpulty, mikrofony | Maraka rental',
            'h1' => 'Pronájem ozvučení',
            'meta_description' => 'Půjčte si ozvučení na svatbu, koncert, ples nebo firemní akci v Olomouci a okolí. Aktivní reproboxy, subwoofery, digitální mixpulty a mikrofony. Rezervace online.',
            'intro' => 'Půjčujeme kompletní ozvučení pro akce od malé oslavy po koncert pro 600 lidí — aktivní reproboxy dB Technologies a Nexo, digitální mixpulty Allen&Heath a Behringer, mikrofony i kabeláž. Nevíte, jaký výkon potřebujete? Zavolejte, set vám sestavíme podle prostoru a počtu hostů.',
        ],
        'svetla' => [
            'title' => 'Pronájem světel a osvětlení Olomouc — LED, wash, výrobníky mlhy | Maraka rental',
            'h1' => 'Pronájem světel a osvětlení',
            'meta_description' => 'Půjčovna světelné techniky v Olomouci: LED wash a spot světla, divadelní reflektory, výrobníky mlhy a hazeru, DMX pulty Avolites. Na svatby, plesy, koncerty i divadlo.',
            'intro' => 'Nasvítíme pódium, sál i venkovní akci. Půjčujeme LED wash a spot světla, divadelní reflektory, výrobníky hazeru a světelné pulty Avolites včetně DMX rozvodů. Pomůžeme s výběrem i nastavením.',
        ],
        'stage' => [
            'title' => 'Pronájem stage techniky — truss, stojany, konstrukce | Maraka rental',
            'h1' => 'Pronájem stage techniky a konstrukcí',
            'meta_description' => 'Půjčovna pódiové techniky v Olomouci: truss konstrukce, zdvižné stojany a příslušenství pro zavěšení světel a ozvučení na akcích.',
            'intro' => 'Truss konstrukce, zdvižné stojany a příslušenství, na které pověsíte světla i reprosoustavy. Ideální doplněk k ozvučení a osvětlení pro koncerty, plesy a firemní eventy.',
        ],
        'distro' => [
            'title' => 'Pronájem elektro rozvodů a kabelů — 32A, 16A, prodlužky | Maraka rental',
            'h1' => 'Pronájem elektro rozvodů a kabeláže',
            'meta_description' => 'Půjčte si silové kabely 32A a 16A, rozbočovače, powerboxy a prodlužovací bubny pro napájení techniky na akcích v Olomouci a okolí.',
            'intro' => 'Bezpečné napájení pro pódium i venkovní akci: silové kabely Titanex 32A a 16A, redukce, powerboxy a prodlužovací bubny v různých délkách.',
        ],
    ],

    // Shown on the homepage as an FAQ section and emitted as FAQPage schema.
    'faq' => [
        [
            'q' => 'Kolik stojí pronájem ozvučení na svatbu nebo oslavu?',
            'a' => 'Cena se počítá za den. Hotové sety stojí: proslovy a hudba z mobilu 950 Kč, DJ párty se světly 1 290 Kč, svatební obřad (2 bedny na baterii + mikrofony) 1 190 Kč a svatba s kapelou (2 topy + 2 subwoofery) 4 290 Kč za den. Dovoz a odvoz v Olomouci je za 600 Kč, do 25 km za 1 000 Kč. Instalaci, zapojení nebo obsluhu technikem naceníme zvlášť. Košík vám spočítá celkovou cenu za celý termín včetně dopravy.',
            'link' => ['url' => '/ozvuceni-svatby-olomouc', 'label' => 'Sety pro svatby a oslavy'],
        ],
        [
            'q' => 'Dovezete a zapojíte techniku?',
            'a' => 'Ano. Dovoz a odvoz stojí v Olomouci 600 Kč, do 25 km (Šternberk, Prostějov, Přerov, Litovel) 1 000 Kč, do 50 km 1 600 Kč, dál podle domluvy. Instalaci a zapojení, případně obsluhu technikem po celou akci, naceníme zvlášť podle rozsahu. Menší sety si můžete vyzvednout i sami.',
        ],
        [
            'q' => 'Pro kolik lidí ozvučení zvládne?',
            'a' => 'Máme techniku pro akce od pár desítek hostů až po koncerty a plesy do 600 osob.',
        ],
        [
            'q' => 'Jak rezervace funguje?',
            'a' => 'Vyberte termín, přidejte techniku do košíku a odešlete rezervaci, nebo nám jen pošlete poptávku. Ozveme se s potvrzením a cenou — nic se neplatí předem online.',
        ],
    ],
];
