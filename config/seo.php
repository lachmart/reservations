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

        // Newer sections and subcategories (Zvuk › …).
        'sety' => [
            'title' => 'Hotové sety ozvučení a světel k pronájmu — svatba, DJ, kapela | Maraka rental',
            'h1' => 'Hotové sety ozvučení a světel',
            'meta_description' => 'Ozvučení na svatbu, oslavu nebo pro kapelu v jednom balíčku: bedny, stativy, mikrofony, kabely a u větších setů i světla. Olomouc a okolí.',
            'intro' => 'Set obsahuje všechno, co k akci potřebujete, včetně stativů a kabelů, a vyjde levněji než jednotlivé kusy. Vyberte podle typu akce a počtu hostů.',
        ],
        'reproboxy-aktivni' => [
            'title' => 'Pronájem aktivních reproboxů Olomouc — dB Technologies, JBL | Maraka rental',
            'h1' => 'Pronájem aktivních reproboxů',
            'meta_description' => 'Aktivní reproboxy dB Technologies Opera a JBL Eon Compact na baterii k pronájmu. Bez zesilovače, stačí zapojit. Na oslavy, proslovy i DJ v Olomouci a okolí.',
            'intro' => 'Aktivní bedny mají zesilovač v sobě: zapojíte je do zásuvky a připojíte mixpult, mobil nebo mikrofon. JBL Eon Compact hraje i na baterii, třeba na venkovní obřad.',
        ],
        'reproboxy-pasivni' => [
            'title' => 'Pronájem reproboxů Nexo PS10 a PS15 Olomouc | Maraka rental',
            'h1' => 'Pronájem pasivních reproboxů Nexo',
            'meta_description' => 'Výkonné pasivní topy Nexo PS10 a PS15 k pronájmu pro kapely, plesy a větší sály. Se zesilovačovým rackem a subwoofery. Olomouc a okolí.',
            'intro' => 'Pasivní topy Nexo potřebují zesilovač. Pro kapelu je půjčujeme se zesilovačovým rackem a subwoofery, nejjednodušeji v setu Živá kapela.',
        ],
        'subwoofery' => [
            'title' => 'Pronájem subwooferů Olomouc | Maraka rental',
            'h1' => 'Pronájem subwooferů',
            'meta_description' => 'Subwoofery MT18 k pronájmu pro kapely, DJ a taneční parket. Se zesilovačovým rackem a topy Nexo. Olomouc a okolí.',
            'intro' => 'Bez subwooferu chybí basy a hudba na parketu zní ploše. Pasivní subwoofery MT18 půjčujeme se zesilovačovým rackem.',
        ],
        'mixpulty' => [
            'title' => 'Pronájem mixpultů Olomouc — Allen&Heath SQ5, CQ-20B, XR18 | Maraka rental',
            'h1' => 'Pronájem mixpultů',
            'meta_description' => 'Digitální mixpulty Allen&Heath SQ5 a CQ-20B, XR18 ovládaný z tabletu, malý ZEDi-8 a stagebox k pronájmu. Pro kapely, konference i proslovy v Olomouci.',
            'intro' => 'Od malého ZEDi-8 na proslovy po digitální SQ5 pro kapelu. CQ-20B a XR18 se ovládají z tabletu nebo notebooku, stagebox zjednoduší kabeláž od pódia.',
        ],
        'mikrofony' => [
            'title' => 'Pronájem mikrofonů Olomouc — Shure, Sennheiser, bezdrátové | Maraka rental',
            'h1' => 'Pronájem mikrofonů',
            'meta_description' => 'Mikrofony k pronájmu: Shure SM58 a Beta58 na zpěv, bezdrátové Sennheiser a AKG, Audix na bicí a kondenzátorové mikrofony na nástroje. Olomouc a okolí.',
            'intro' => 'Na zpěv a proslovy Shure SM58 a Beta58, bezdrátové Sennheiser a AKG na moderování, Audix na bicí a kondenzátorové AKG C1000 a sE8 na nástroje. Stativy a kabely najdete také v sekci Zvuk.',
        ],
        'pocitace' => [
            'title' => 'Pronájem notebooků se softwarem pro zvukaře | Maraka rental',
            'h1' => 'Pronájem notebooků',
            'meta_description' => 'Notebooky s Windows, macOS a Ubuntu s nainstalovaným softwarem pro ovládání zvukové techniky: NEXO Dory a NeMo, Allen&Heath CQ a SQ, Rane Drag Net.',
            'intro' => 'Notebooky připravené k nastavení a ovládání zvukové techniky na akci. U každého najdete seznam nainstalovaného softwaru. Hodí se k našim mixpultům Allen&Heath a reprosoustavám NEXO.',
        ],
    ],

    // Shown on the homepage as an FAQ section and emitted as FAQPage schema.
    'faq' => [
        [
            'q' => 'Kolik stojí pronájem ozvučení na svatbu nebo oslavu?',
            'a' => 'Cena se počítá za den. Hotové sety stojí: proslovy a hudba z mobilu 950 Kč, DJ párty s mixpultem a světly 1 390 Kč, svatební obřad (2 bedny na baterii, klopové mikrofony DJI a drátový mikrofon) 1 300 Kč a živá kapela (2 topy, 2 subwoofery, mixpult, bezdrátový mikrofon a 8 světel) 6 390 Kč, velký zvuk Nexo (2 topy PS15, 4 subwoofery) 6 490 Kč a digitální mixpult SQ5 se stageboxem 3 000 Kč za den. Dovoz a odvoz v Olomouci je za 600 Kč, do 25 km za 1 000 Kč. Instalaci, zapojení nebo obsluhu technikem naceníme zvlášť. Košík vám spočítá celkovou cenu za celý termín včetně dopravy.',
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
