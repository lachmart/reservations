<?php

/*
| Landing pages for the searches people actually type ("ozvučení svatby Olomouc"…).
| Each page is served at /{slug}. Sets are matched by slug, products by exact name;
| anything missing from the catalog is simply skipped.
*/

return [
    'pages' => [
        'ozvuceni-svatby-olomouc' => [
            'title' => 'Ozvučení svatby Olomouc: obřad, hostina, kapela i DJ | Maraka rental',
            'meta_description' => 'Pronájem ozvučení na svatbu v Olomouci a okolí. Hotové sety pro obřad, proslovy, kapelu i DJ s cenou předem, dovoz i zapojení. Rezervace online.',
            'kicker' => 'Svatby',
            'nav_label' => 'Ozvučení svatby',
            'h1' => 'Ozvučení svatby v Olomouci a okolí',
            'intro' => 'Půjčíme zvuk na celý svatební den: obřad s mikrofonem pro oddávajícího, hudbu a proslovy na hostině a pořádný zvuk pro kapelu nebo DJ večer. Ceny vidíte předem, termín si ověříte a rezervujete online.',
            'sets_heading' => 'Hotové sety pro svatbu',
            'sets' => ['set-svatebni-obrad', 'set-proslovy-a-hudba', 'set-dj-party', 'set-ziva-kapela'],
            'sections' => [
                ['Obřad', 'Set Svatební obřad má dvě bedny JBL na baterii, dva bezdrátové klopové mikrofony DJI pro oddávající a drátový mikrofon pro svědky. Nepotřebujete zásuvku, takže funguje na zahradě, u rybníka i v obřadní síni. Hudbu na nástup pustíte z mobilu.'],
                ['Hostina a proslovy', 'Na přípitek, proslovy a hudbu k jídlu stačí menší set s bezdrátovým mikrofonem. Pokud je obřad i hostina na jednom místě, vystačíte si s jedním setem po celý den.'],
                ['Kapela', 'Set Živá kapela je kompletní: dva topy Nexo PS10, dva subwoofery, digitální mixpult, bezdrátový mikrofon na proslovy a osm LED světel, z toho dva bílé reflektory na kapelu. Utáhne sál do zhruba 200 hostů. Bez subwooferů by basa a bicí na tanečním parketu zněly slabě.'],
                ['DJ', 'DJ zapojí svůj kontrolér rovnou do aktivních beden. Set DJ párty přidává dvě LED světla, aby parket nebyl po setmění potmě.'],
                ['Jak to probíhá', 'Vyberte termín a set a odešlete rezervaci, nebo nám jen napište poptávku. Ozveme se s potvrzením. V den svatby techniku dovezeme a po akci odvezeme. Instalaci a zapojení, případně obsluhu po celou akci, vám rádi naceníme zvlášť.'],
            ],
            'products_heading' => 'Doplňky na svatbu',
            'products' => [
                'Sennheiser XSW 2-835 B',
                'AKG WMS40 Mini Dual',
                'dB Technologies Opera 12',
                'Subwoofer MT18',
                'Fractal Lights 9x10W',
                'Cameo Thunderwash 600 RGBW',
                'Chauvet Proffesional Amhaze Whisper 2000W',
                'Allen&Heath ZEDi-8',
            ],
            'faq' => [
                ['Potřebuju na svatbu subwoofer?', 'Na obřad a proslovy ne. Na kapelu nebo DJ, kde se tančí, ano: bez subwooferu chybí basy a hudba zní plochá. Proto ho mají set Živá kapela i větší sestavy.'],
                ['Kolik stojí ozvučení svatby?', 'Set na obřad nebo proslovy vyjde od 950 Kč za den, DJ párty s mixpultem a světly na 1 390 Kč a zvuk a světla pro kapelu (2 topy, 2 subwoofery, mixpult a 8 LED světel) na 6 390 Kč za den. K tomu dovoz a odvoz: v Olomouci 600 Kč, do 25 km 1 000 Kč. Instalace a obsluha za příplatek.'],
                ['Jak dlouho dopředu rezervovat?', 'Svatební sobota v sezóně bývá obsazená i rok dopředu. Termín si můžete zarezervovat online hned, jakmile znáte datum.'],
                ['Zapojíte techniku i na zahradě nebo ve stanu?', 'Ano, potřebujeme jen zásuvku 230 V poblíž. Prodlužovací kabely a rozbočovače přivezeme.'],
            ],
        ],

        'ozvuceni-kapely' => [
            'title' => 'Ozvučení kapely: pronájem PA, mixpultů a mikrofonů Olomouc | Maraka rental',
            'meta_description' => 'Pronájem ozvučení pro kapelu v Olomouci: PA Nexo se subwoofery, digitální mixpulty Allen&Heath, mikrofony na zpěv i bicí a kabeláž. Ceny online, dovoz, instalace i obsluha.',
            'kicker' => 'Kapely',
            'nav_label' => 'Ozvučení kapely',
            'h1' => 'Ozvučení pro kapelu: PA, mixpult, mikrofony',
            'intro' => 'Hrajete na svatbě, plese, v klubu nebo na menším festivalu a potřebujete reproduktory, mixpult nebo celý zvuk? Půjčíme PA systém Nexo se subwoofery, digitální mixpulty Allen&Heath, mikrofony na zpěv i bicí a kabeláž. Ceny jsou online, termín ověříte hned.',
            'sets_heading' => 'Hotové sety pro kapelu',
            'sets' => ['set-ziva-kapela', 'set-velky-zvuk-nexo', 'set-mixpult-sq5-stagebox'],
            'sections' => [
                ['Kompletní zvuk a světla', 'Set Živá kapela má dva topy Nexo PS10, dva subwoofery, zesilovačový rack, digitální mixpult Allen&Heath CQ-20B, bezdrátový mikrofon, šest LED světel Fractal a dva reflektory Varytec na stativech. Pokryje sál nebo stan zhruba do 200 lidí. Máte vlastní mixpult? Půjčíme i jen reproduktory.'],
                ['Mixpult a stagebox', 'Na větší sestavy půjčujeme digitální mixpulty Allen&Heath SQ5 a CQ-20B, kompaktní XR18 ovládané z tabletu a stagebox, aby od pódia vedl jen jeden kabel.'],
                ['Mikrofony na zpěv a bicí', 'Shure SM58 a Beta58 na zpěv, SM57 na kytary a virbl, Audix D6 na kopák a další mikrofony na bicí. Stojany a XLR kabely v různých délkách.'],
                ['Kolik výkonu potřebujete', 'Do zhruba 100 lidí bez bicích stačí dvě aktivní bedny. Kapela s bicími v sále pro 150 až 250 lidí potřebuje dva topy a dva subwoofery. Na větší akci nám napište, sestavu navrhneme.'],
            ],
            'products_heading' => 'Technika pro kapely',
            'products' => [
                'Nexo PS10R2',
                'Nexo PS15R2 (2000W, 1x15"+1x3")',
                'Subwoofer MT18',
                'dB Technologies Opera Reevo 212',
                'Allen&Heath SQ5 + case',
                'Allen&Heath CQ-20B (rack)',
                'XR18',
                'Allen&Heath Stagebox',
                'Shure SM58',
                'Shure SM57',
                'Audix D6',
                'Sennheiser e609',
            ],
            'faq' => [
                ['Dovezete a zapojíte PA i pro kapelu?', 'Ano. Dovoz a odvoz stojí v Olomouci 600 Kč, do 25 km 1 000 Kč. Postavení a zapojení PA, případně zvukaře na celou akci, naceníme zvlášť.'],
                ['Půjčíte i odposlechy?', 'Jako odposlechy fungují aktivní bedny dB Technologies Opera. Kolik jich potřebujete, záleží na sestavě, napište nám.'],
                ['Můžeme si techniku vyzvednout sami?', 'Ano, v Olomouci. V košíku zvolte vyzvednutí osobně.'],
            ],
        ],

        'pronajem-reproduktoru-olomouc' => [
            'title' => 'Pronájem reproduktorů Olomouc: aktivní bedny a subwoofery | Maraka rental',
            'meta_description' => 'Půjčovna reproduktorů v Olomouci: aktivní bedny na oslavu a proslovy, bedna na baterii, výkonné topy a subwoofery. Stativy a kabely, ceny za den online.',
            'kicker' => 'Reproduktory',
            'nav_label' => 'Pronájem reproduktorů',
            'h1' => 'Pronájem reproduktorů v Olomouci',
            'intro' => 'Aktivní bedny na oslavu, proslovy nebo DJ, bedna na baterii ven a výkonné topy se subwoofery pro kapelu. Ke každé bedně přidáme stativ a kabely, cenu za den vidíte u každé položky.',
            'sets_heading' => 'Sety s reproduktory',
            'sets' => ['set-proslovy-a-hudba', 'set-dj-party', 'set-ziva-kapela', 'set-velky-zvuk-nexo'],
            'sections' => [
                ['Na proslovy a hudbu z mobilu', 'Na raut, oslavu nebo prezentaci do zhruba 60 lidí stačí jedna aktivní bedna dB Technologies Opera 12 na stativu. Zapojíte do ní mobil, notebook nebo mikrofon.'],
                ['Ven bez zásuvky', 'JBL EON ONE Compact hraje na baterii, takže se hodí na zahradu, k ohni nebo na krátký proslov venku.'],
                ['Na tanec a DJ', 'Dvě větší bedny Opera 15 nebo Opera Reevo 212 utáhnou oslavu do zhruba 100 lidí. Na tanec s pořádnými basy přidejte subwoofer.'],
                ['Pro kapelu a větší sál', 'Topy Nexo PS10 se dvěma subwoofery MT18 půjčujeme v setu Živá kapela, pro sál nebo stan zhruba do 200 lidí. Na větší akci je set Velký zvuk Nexo: dva topy PS15 a čtyři subwoofery MT18.'],
            ],
            'products_heading' => 'Reproduktory a stativy',
            'products' => [
                'dB Technologies Opera 12',
                'dB Technologies Opera 15',
                'dB Technologies Opera Reevo 212',
                'JBL Eon Compact One',
                'AKG WMS40 Mini Dual',
                'Nexo PS10R2',
                'Nexo PS15R2 (2000W, 1x15"+1x3")',
                'Subwoofer MT18',
                'Konig&Meyer 21450 black',
                'Konig&Meyer 213',
            ],
            'faq' => [
                ['Kolik stojí pronájem reproduktoru?', 'Aktivní bedna dB Technologies Opera 12 stojí 500 Kč za den, set s bednou, stativem, bezdrátovým mikrofonem a mixpultem 950 Kč za den.'],
                ['Potřebuju k bedně zesilovač?', 'K aktivním bednám dB Technologies a JBL ne, zesilovač mají v sobě. Topy Nexo potřebují zesilovač, proto je půjčujeme v setu s ním.'],
                ['Jak se počítá den?', 'Každých započatých 24 hodin je jeden den. Vyzvednutí v pátek a vrácení v neděli ve stejnou hodinu jsou dva dny.'],
            ],
        ],
    ],
];
