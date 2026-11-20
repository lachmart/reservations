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
            'meta_description' => 'Pronájem ozvučení na svatbu v Olomouci a okolí. Hotové sety pro obřad, proslovy, kapelu i DJ s cenou předem, dovoz a zapojení. Rezervace online.',
            'kicker' => 'Svatby',
            'nav_label' => 'Ozvučení svatby',
            'h1' => 'Ozvučení svatby v Olomouci a okolí',
            'intro' => 'Půjčíme a zapojíme zvuk na celý svatební den: obřad s mikrofonem pro oddávajícího, hudbu a proslovy na hostině a pořádný zvuk pro kapelu nebo DJ večer. Ceny vidíte předem, termín si ověříte a rezervujete online.',
            'sets_heading' => 'Hotové sety pro svatbu',
            'sets' => ['svatebmi-balicek-obrad', 'set-proslovy-a-hudba', 'set-dj-party', 'set-svatba-s-kapelou'],
            'sections' => [
                ['Obřad', 'Na obřad stačí jedna až dvě bedny a bezdrátový mikrofon pro oddávajícího a svědky. Hudbu na nástup pustíte z mobilu nebo notebooku. Venku na zahradě i v obřadní síni.'],
                ['Hostina a proslovy', 'Na přípitek, proslovy a hudbu k jídlu stačí menší set s bezdrátovým mikrofonem. Pokud je obřad i hostina na jednom místě, vystačíte si s jedním setem po celý den.'],
                ['Kapela', 'Většina svatebních kapel vozí vlastní mixpult a potřebuje jen reproduktory. Set Svatba s kapelou má dva topy Nexo a dva subwoofery a utáhne sál do zhruba 250 hostů. Bez subwooferů by basa a bicí na tanečním parketu zněly slabě.'],
                ['DJ', 'DJ zapojí svůj kontrolér rovnou do aktivních beden. Set DJ párty přidává dvě LED světla, aby parket nebyl po setmění potmě.'],
                ['Jak to probíhá', 'Vyberte termín a set a odešlete rezervaci, nebo nám jen napište poptávku. Ozveme se s potvrzením. V den svatby techniku dovezeme, zapojíme, vyzkoušíme a po akci zase odvezeme.'],
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
                'Allen&Heath ZED6',
            ],
            'faq' => [
                ['Potřebuju na svatbu subwoofer?', 'Na obřad a proslovy ne. Na kapelu nebo DJ, kde se tančí, ano: bez subwooferu chybí basy a hudba zní plochá. Proto ho mají sety Svatba s kapelou i větší sestavy.'],
                ['Kolik stojí ozvučení svatby?', 'Set na obřad nebo proslovy vyjde od 950 Kč za den, DJ párty se světly na 1 290 Kč a zvuk pro kapelu (2 topy + 2 subwoofery) na 4 990 Kč za den. K tomu dovoz, zapojení a odvoz: v Olomouci 600 Kč, do 25 km 1 000 Kč.'],
                ['Jak dlouho dopředu rezervovat?', 'Svatební sobota v sezóně bývá obsazená i rok dopředu. Termín si můžete zarezervovat online hned, jakmile znáte datum.'],
                ['Zapojíte techniku i na zahradě nebo ve stanu?', 'Ano, potřebujeme jen zásuvku 230 V poblíž. Prodlužovací kabely a rozbočovače přivezeme.'],
            ],
        ],

        'ozvuceni-kapely' => [
            'title' => 'Ozvučení kapely: pronájem PA, mixpultů a mikrofonů Olomouc | Maraka rental',
            'meta_description' => 'Pronájem ozvučení pro kapelu v Olomouci: PA Nexo se subwoofery, digitální mixpulty Allen&Heath, mikrofony na zpěv i bicí a kabeláž. Ceny online, dovoz a zapojení.',
            'kicker' => 'Kapely',
            'nav_label' => 'Ozvučení kapely',
            'h1' => 'Ozvučení pro kapelu: PA, mixpult, mikrofony',
            'intro' => 'Hrajete na svatbě, plese, v klubu nebo na menším festivalu a potřebujete reproduktory, mixpult nebo celý zvuk? Půjčíme PA systém Nexo se subwoofery, digitální mixpulty Allen&Heath, mikrofony na zpěv i bicí a kabeláž. Ceny jsou online, termín ověříte hned.',
            'sets_heading' => 'Hotový set pro kapelu',
            'sets' => ['set-svatba-s-kapelou'],
            'sections' => [
                ['Jen reproduktory', 'Máte vlastní mixpult? Set se dvěma topy Nexo PS15, dvěma subwoofery, zesilovačovým rackem, stojnami a kabely pokryje sál nebo stan zhruba do 250 lidí.'],
                ['Mixpult a stagebox', 'Na větší sestavy půjčujeme digitální mixpulty Allen&Heath SQ5 a CQ-20B, kompaktní XR18 ovládané z tabletu a stagebox, aby od pódia vedl jen jeden kabel.'],
                ['Mikrofony na zpěv a bicí', 'Shure SM58 a Beta58 na zpěv, SM57 na kytary a virbl, Audix D6 na kopák a další mikrofony na bicí. Stojany a XLR kabely v různých délkách.'],
                ['Kolik výkonu potřebujete', 'Do zhruba 100 lidí bez bicích stačí dvě aktivní bedny. Kapela s bicími v sále pro 150 až 250 lidí potřebuje dva topy a dva subwoofery. Na větší akci nám napište, sestavu navrhneme.'],
            ],
            'products_heading' => 'Technika pro kapely',
            'products' => [
                'Nexo PS15R2 (2000W, 1x15"+1x3")',
                'Nexo PS10R2',
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
                ['Dovezete a zapojíte PA i pro kapelu?', 'Ano. Techniku dovezeme, postavíme a zapojíme, kapela si pak připojí svůj mixpult. V Olomouci za 600 Kč, do 25 km za 1 000 Kč.'],
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
            'sets' => ['set-proslovy-a-hudba', 'set-dj-party', 'set-svatba-s-kapelou'],
            'sections' => [
                ['Na proslovy a hudbu z mobilu', 'Na raut, oslavu nebo prezentaci do zhruba 60 lidí stačí jedna aktivní bedna dB Technologies Opera 12 na stativu. Zapojíte do ní mobil, notebook nebo mikrofon.'],
                ['Ven bez zásuvky', 'JBL EON ONE Compact hraje na baterii, takže se hodí na zahradu, k ohni nebo na krátký proslov venku.'],
                ['Na tanec a DJ', 'Dvě větší bedny Opera 15 nebo Opera Reevo 212 utáhnou oslavu do zhruba 100 lidí. Na tanec s pořádnými basy přidejte subwoofer.'],
                ['Pro kapelu a větší sál', 'Topy Nexo PS10 a PS15 se subwoofery MT18 půjčujeme v setu se zesilovačovým rackem. Pokryjí sál nebo stan pro 150 až 250 lidí.'],
            ],
            'products_heading' => 'Reproduktory a stativy',
            'products' => [
                'dB Technologies Opera 12',
                'dB Technologies Opera 15',
                'dB Technologies Opera Reevo 212',
                'JBL Eon Compact One',
                'Nexo PS10R2',
                'Nexo PS15R2 (2000W, 1x15"+1x3")',
                'Subwoofer MT18',
                'Konig&Meyer 21450 black',
                'Gravity SP 5322 B',
            ],
            'faq' => [
                ['Kolik stojí pronájem reproduktoru?', 'Aktivní bedna dB Technologies Opera 12 stojí 500 Kč za den, set s bednou, stativem, bezdrátovým mikrofonem a mixpultem 950 Kč za den.'],
                ['Potřebuju k bedně zesilovač?', 'K aktivním bednám dB Technologies a JBL ne, zesilovač mají v sobě. Topy Nexo potřebují zesilovač, proto je půjčujeme v setu s ním.'],
                ['Jak se počítá den?', 'Každých započatých 24 hodin je jeden den. Vyzvednutí v pátek a vrácení v neděli ve stejnou hodinu jsou dva dny.'],
            ],
        ],
    ],
];
