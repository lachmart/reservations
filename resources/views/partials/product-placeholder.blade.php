{{--
    Icon shown in place of a product photo that hasn't been uploaded yet.
    Params: $kind (from Product::placeholderKind()), $size (Tailwind size class), $label (optional caption).
--}}
@php($kind = $kind ?? 'generic')
@php($size = $size ?? 'size-14')
<div class="absolute inset-0 flex flex-col items-center justify-center gap-2" role="img" aria-label="{{ $label ?? 'Fotka bude doplněna' }}">
    <svg class="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" style="color:color-mix(in oklab, #3a3530 45%, var(--media))">
        @switch($kind)
            @case('mic')
                <rect x="9" y="2.5" width="6" height="11" rx="3"/><path d="M5.5 11a6.5 6.5 0 0 0 13 0"/><path d="M12 17.5v4M8.5 21.5h7"/><path d="M9 6.5h6M9 9h6"/>
                @break
            @case('cable')
                <path d="M3 7h3v4H3zM18 13h3v4h-3z"/><path d="M6 9h4a3 3 0 0 1 3 3v0a3 3 0 0 0 3 3h2"/><path d="M3.5 7V5.5M5.5 7V5.5M18.5 17v1.5M20.5 17v1.5"/>
                @break
            @case('reel')
                <circle cx="12" cy="12" r="8.5"/><circle cx="12" cy="12" r="5.5"/><circle cx="12" cy="12" r="1.5"/><path d="M20.5 12h2M12 3.5V2"/>
                @break
            @case('power')
                <path d="M9 2.5v4.5M15 2.5v4.5"/><path d="M6.5 7h11v4a5.5 5.5 0 0 1-11 0z"/><path d="M12 16.5v5"/>
                @break
            @case('stand')
                <path d="M12 3v11"/><path d="M12 14l-6 7M12 14l6 7M12 14v7"/><path d="M8 3h8"/>
                @break
            @case('light')
                <path d="M8 4h8l1.5 9h-11z"/><path d="M6.5 13h11"/><path d="M12 13v3M9 21h6M12 16v5"/><path d="M5 3l1.5 1.5M19 3l-1.5 1.5"/>
                @break
            @case('haze')
                <rect x="3" y="11" width="10" height="7" rx="1.5"/><path d="M13 14.5h2"/><path d="M17 12c1.5 0 1.5 1.5 3 1.5M17 15c1.5 0 1.5 1.5 3 1.5M17 9c1.5 0 1.5 1.5 3 1.5"/><path d="M5 18v2M11 18v2"/>
                @break
            @case('speaker')
                <rect x="5" y="2.5" width="14" height="19" rx="2"/><circle cx="12" cy="14.5" r="3.5"/><circle cx="12" cy="7" r="1.5"/>
                @break
            @case('stagebox')
                <rect x="3" y="6" width="18" height="12" rx="1.5"/><circle cx="7" cy="10" r="1.2"/><circle cx="12" cy="10" r="1.2"/><circle cx="17" cy="10" r="1.2"/><circle cx="7" cy="14.5" r="1.2"/><circle cx="12" cy="14.5" r="1.2"/><circle cx="17" cy="14.5" r="1.2"/>
                @break
            @case('package')
                <path d="M3.5 8 12 3.5 20.5 8v8L12 20.5 3.5 16z"/><path d="M3.5 8 12 12.5 20.5 8M12 12.5v8"/>
                @break
            @default
                <rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/>
        @endswitch
    </svg>
    @isset($label)
        <span class="text-xs font-medium" style="color:color-mix(in oklab, #3a3530 60%, var(--media))">{{ $label }}</span>
    @endisset
</div>
