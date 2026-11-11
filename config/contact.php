<?php

return [
    // Public contact details (footer + homepage CTA). Override via .env.
    'name' => env('CONTACT_NAME', 'Martin Lach'),
    'phone' => env('CONTACT_PHONE', '+420 734 530 330'),
    'email' => env('CONTACT_EMAIL', 'info@marakarental.cz'),
    'ico' => env('CONTACT_ICO', '07402767'),
    'address' => env('CONTACT_ADDRESS', 'Černá cesta 96/31, 779 00 Olomouc'),

    // The owner's inbox(es) for new reservations and enquiries. Comma-separated
    // for several inboxes. Keep it different from MAIL_FROM_ADDRESS: a message sent
    // from an address to itself is often filed as "sent" or dropped as a duplicate.
    'owner_notify' => array_values(array_filter(array_map('trim', explode(',',
        (string) env('RESERVATION_NOTIFY_EMAIL', env('CONTACT_EMAIL', 'info@marakarental.cz'))
    )))),
];
