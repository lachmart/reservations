<?php

return [
    // Public contact details (footer + homepage CTA). Override via .env.
    'name' => env('CONTACT_NAME', 'Martin Lach'),
    'phone' => env('CONTACT_PHONE', '+420 734 530 330'),
    'email' => env('CONTACT_EMAIL', 'info@marakarental.cz'),
    'ico' => env('CONTACT_ICO', '07402767'),
    'address' => env('CONTACT_ADDRESS', 'Černá cesta 96/31, 779 00 Olomouc'),
    // Shown as text; deliberately not in the structured data (Google reads
    // missing hours as "closed" rather than "by appointment").
    'hours' => env('CONTACT_HOURS', 'Výdej a vrácení techniky po domluvě'),

    // The owner's inbox(es) for new reservations and enquiries. Comma-separated
    // for several inboxes. Keep it different from MAIL_FROM_ADDRESS: a message sent
    // from an address to itself is often filed as "sent" or dropped as a duplicate.
    'owner_notify' => array_values(array_filter(array_map('trim', explode(',',
        (string) env('RESERVATION_NOTIFY_EMAIL', env('CONTACT_EMAIL', 'info@marakarental.cz'))
    )))),

    // Transport, priced per zone around Olomouc (CZK per event: delivery and pick-up).
    // Installation, wiring and operating the gear are extra, quoted on request.
    // price null = priced individually and confirmed later.
    // The first zone is the default in the cart.
    'delivery_zones' => [
        'olomouc' => ['label' => 'Dovoz a odvoz: Olomouc', 'price' => 600],
        'okoli' => ['label' => 'Dovoz a odvoz: do 25 km (Šternberk, Prostějov, Přerov, Litovel…)', 'price' => 1000],
        'region' => ['label' => 'Dovoz a odvoz: 25–50 km (Uničov, Hranice, Kroměříž…)', 'price' => 1600],
        'dal' => ['label' => 'Dovoz a odvoz: dál, cenu potvrdíme', 'price' => null],
        'pickup' => ['label' => 'Vyzvednu si osobně v Olomouci', 'price' => 0],
    ],
];
