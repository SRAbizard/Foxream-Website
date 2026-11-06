@props([
    'eyebrow'  => null,   // small sticker above the title (e.g. "Careers")
    'title'    => null,
    'subtitle' => null,
    'petals'   => 10,     // sakura petal density
    'minH'     => '',     // optional extra min-height utility
    'page'     => null,   // which page's background picture (SiteSettings::PAGE_HEROES)
])

@php $bg = $page ? \App\Support\SiteSettings::pageHeroUrl($page) : null; @endphp

{{--
    Reusable hero band for internal pages, in the "Misi ke Jepang" style:
    the page's picture (Admin → Website Settings → Page headers, or the
    bundled illustration), falling sakura and a chunky title. Pass eyebrow /
    title / subtitle as attributes; anything in the slot renders under the
    title.
--}}
<section {{ $attributes->class(['pj-page-hero', $minH, 'has-bg' => $bg]) }}>
    @if($bg)
        <div class="pj-page-hero-bg" style="--pj-page-hero-img: url('{{ $bg }}')" aria-hidden="true"></div>
    @endif

    <x-jp.sakura-petals :count="$petals" />

    <div class="relative mx-auto max-w-7xl px-6 pt-10 pb-14 sm:pt-16 sm:pb-20">
        @if($eyebrow)
            <span class="pj-sticker">{{ $eyebrow }}</span>
        @endif
        @if($title)
            <h1 class="mt-5 font-display text-4xl sm:text-6xl leading-[1.02] max-w-3xl text-balance">
                {{ $title }}
            </h1>
        @endif
        @if($subtitle)
            <p class="mt-5 max-w-2xl text-lg pj-lede">{{ $subtitle }}</p>
        @endif

        {{ $slot }}
    </div>
</section>
