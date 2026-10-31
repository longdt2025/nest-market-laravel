@props(['name'=>'shopping-bag','size'=>20])
<svg xmlns="http://www.w3.org/2000/svg" width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" {{ $attributes }}>
@switch($name)
@case('upload')<path d="M7 18H6a4 4 0 0 1-.6-7.95 6.5 6.5 0 0 1 12.7-1.6A4.7 4.7 0 0 1 18 18h-1M12 21V11m-4 4 4-4 4 4"/>@break
@case('download')<path d="M4 16v5h16v-5M12 3v12m-4-4 4 4 4-4"/>@break
@case('columns')<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18M3 14h18M9 4v16M15 4v16"/>@break
@case('search')<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>@break
@case('shopping-bag')<path d="M4 8h16l-1 13H5L4 8Z"/><path d="M9 9V6a3 3 0 0 1 6 0v3"/>@break
@case('user')<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>@break
@case('arrow-right')<path d="M4 12h16m-7-7 7 7-7 7"/>@break
@case('chevron-right')<path d="m9 18 6-6-6-6"/>@break
@case('chevron-left')<path d="m15 18-6-6 6-6"/>@break
@case('menu')<path d="M3 6h18M3 12h18M3 18h18"/>@break
@case('x')<path d="M5 5l14 14M19 5 5 19"/>@break
@case('check')<path d="m4 12 5 5L20 6"/>@break
@case('truck')<path d="M3 5h11v12H3zM14 9h4l3 4v4h-7M7 20a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm11 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"/>@break
@case('package')<path d="m12 2 9 5v10l-9 5-9-5V7l9-5ZM3 7l9 5 9-5M12 12v10"/>@break
@case('shield')<path d="m12 2 9 4v6c0 5-4 9-9 10-5-1-9-5-9-10V6z"/><path d="m8 12 3 3 5-6"/>@break
@case('clock')<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>@break
@case('minus')<path d="M5 12h14"/>@break
@case('plus')<path d="M5 12h14M12 5v14"/>@break
@case('trash')<path d="M4 7h16M9 7V4h6v3M7 7l1 14h8l1-14M10 11v6m4-6v6"/>@break
@case('edit')<path d="m15 5 4 4M4 20l5-.9L20 8a2.8 2.8 0 0 0-4-4L5 15z"/>@break
@case('grid')<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>@break
@case('list')<path d="M8 6h13M8 12h13M8 18h13M3 6h1M3 12h1M3 18h1"/>@break
@case('logout')<path d="M9 4H5v16h4M14 7l5 5-5 5m5-5H9"/>@break
@case('filter')<path d="M4 6h16M7 12h10m-7 6h4"/>@break
@case('mail')<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>@break
@case('map-pin')<path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2"/>@break
@case('lock')<rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>@break
@default<circle cx="12" cy="12" r="9"/>@endswitch
</svg>
