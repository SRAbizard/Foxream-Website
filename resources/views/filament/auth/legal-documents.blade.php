{{-- The Terms and the Privacy Policy in the registration pop-up, one after
     the other; opens at the one whose link was clicked. Links between the
     two jump within the pop-up; other links open in a new tab so the form
     is not lost. --}}
<div class="pj-legal-modal"
    x-data="{ go(id, smooth = true) { document.getElementById(id)?.scrollIntoView({ block: 'start', behavior: smooth ? 'smooth' : 'auto' }) } }"
    @if($doc === 'privacy') x-init="setTimeout(() => go('pj-legal-privacy', false), 60)" @endif
    x-on:click="
        const a = $event.target.closest('.fi-prose a[href]'); if (! a) return;
        const path = new URL(a.href, location.href).pathname;
        if (path === @js(parse_url(route('legal.terms'), PHP_URL_PATH))) { $event.preventDefault(); go('pj-legal-terms') }
        else if (path === @js(parse_url(route('legal.privacy'), PHP_URL_PATH))) { $event.preventDefault(); go('pj-legal-privacy') }
        else { a.target = '_blank'; a.rel = 'noopener' }
    ">
    <nav class="pj-legal-nav">
        <button type="button" x-on:click="go('pj-legal-terms')">{{ __('Terms & Conditions') }}</button>
        <button type="button" x-on:click="go('pj-legal-privacy')">{{ __('Privacy Policy') }}</button>
    </nav>

    @foreach(['terms' => __('Terms & Conditions'), 'privacy' => __('Privacy Policy')] as $key => $title)
        <section id="pj-legal-{{ $key }}" class="pj-legal-doc">
            <h2>{{ $title }}</h2>
            <p class="pj-legal-date">{{ __('Last updated: :date', ['date' => \Illuminate\Support\Carbon::parse(\App\Support\Legal::updatedAt($key))->translatedFormat('j F Y')]) }}</p>
            <div class="fi-prose">{{ \App\Support\Legal::document($key) }}</div>
        </section>
    @endforeach
</div>
