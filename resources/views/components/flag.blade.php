{{-- Language flag as inline SVG: Windows shows flag emoji as bare letters ("ID", "GB"). --}}
@props(['code'])
@php($uid = 'f'.\Illuminate\Support\Str::random(6))
<svg {{ $attributes->merge(['viewBox' => '0 0 60 40', 'preserveAspectRatio' => 'xMidYMid slice', 'aria-hidden' => 'true',
    'style' => 'display:inline-block;flex:none;width:1.35em;height:.9em;vertical-align:-.1em;border-radius:2px;box-shadow:0 0 0 1px rgba(0,0,0,.18)']) }}>
    @switch($code)
        @case('id')
            <rect width="60" height="20" fill="#ce1126"/><rect y="20" width="60" height="20" fill="#fff"/>
            @break
        @case('ja')
            <rect width="60" height="40" fill="#fff"/><circle cx="30" cy="20" r="12" fill="#bc002d"/>
            @break
        @case('en')
            <clipPath id="{{ $uid }}t"><path d="M30,20h30v20zv20h-30zh-30v-20zv-20h30z"/></clipPath>
            <rect width="60" height="40" fill="#012169"/>
            <path d="M0,0L60,40M60,0L0,40" stroke="#fff" stroke-width="8"/>
            <path d="M0,0L60,40M60,0L0,40" clip-path="url(#{{ $uid }}t)" stroke="#c8102e" stroke-width="5"/>
            <path d="M30,0v40M0,20h60" stroke="#fff" stroke-width="12"/>
            <path d="M30,0v40M0,20h60" stroke="#c8102e" stroke-width="7"/>
            @break
        @default
            <rect width="60" height="40" fill="#9ca3af"/>
    @endswitch
</svg>
