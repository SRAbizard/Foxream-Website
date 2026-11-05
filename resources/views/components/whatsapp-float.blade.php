@props(['number' => \App\Support\SiteSettings::contact('whatsapp'), 'message' => 'Halo '.\App\Support\Brand::name().', saya ingin bertanya tentang program Anda.'])

<a href="https://wa.me/{{ $number }}?text={{ urlencode($message) }}"
   target="_blank" rel="noopener"
   aria-label="Chat WhatsApp"
   class="fixed bottom-6 right-6 z-50 group">
    <span class="absolute inset-0 -m-1 rounded-full bg-emerald-500/40 animate-ping group-hover:hidden"></span>
    <span class="relative inline-flex h-14 w-14 items-center justify-center rounded-full bg-emerald-500 shadow-lg shadow-emerald-500/40 ring-4 ring-emerald-500/20 hover:bg-emerald-400 transition">
        <svg class="h-7 w-7 text-white" viewBox="0 0 32 32" fill="currentColor" aria-hidden="true">
            <path d="M19.11 17.205c-.372 0-1.088 1.39-1.518 1.39a.63.63 0 01-.315-.1c-.802-.402-1.504-.817-2.163-1.447-.545-.516-1.146-1.29-1.46-1.963a.426.426 0 01-.073-.215c0-.33.99-.945.99-1.49 0-.143-.73-2.09-.832-2.335-.143-.372-.214-.487-.6-.487-.187 0-.36-.043-.53-.043-.302 0-.53.115-.746.315-.688.645-1.032 1.318-1.06 2.264v.114c-.015.99.472 1.977 1.017 2.78 1.23 1.82 2.506 3.41 4.554 4.34.616.287 2.035.658 2.522.658.46 0 1.005-.043 1.435-.244.617-.287 1.477-.945 1.39-1.605-.043-.244-.187-.402-.387-.516-.516-.244-.99-.43-1.49-.717-.214-.13-.46-.215-.733-.215v.001zM16.515 27.32a11.16 11.16 0 01-5.74-1.59l-4.118 1.32 1.36-4.02a11.13 11.13 0 01-1.74-5.96c0-6.14 5-11.14 11.14-11.14a11.06 11.06 0 017.879 3.27 11.05 11.05 0 013.27 7.87c0 6.14-5 11.14-11.14 11.14h-.001zm0-23.81C8.873 3.51 2.67 9.715 2.67 17.354c0 2.451.65 4.85 1.87 6.978L2.5 30.66l6.469-2.066a13.86 13.86 0 007.546 2.241h.001c7.638 0 13.84-6.206 13.84-13.845 0-3.696-1.43-7.165-4.045-9.78a13.78 13.78 0 00-9.795-4.05z"/>
        </svg>
    </span>
    <span class="hidden md:block absolute right-full mr-3 top-1/2 -translate-y-1/2 whitespace-nowrap rounded-lg bg-surface-800 px-3 py-1.5 text-xs text-white opacity-0 group-hover:opacity-100 transition shadow-lg">
        WhatsApp · {{ \App\Support\SiteSettings::contact('phone') }}
    </span>
</a>
