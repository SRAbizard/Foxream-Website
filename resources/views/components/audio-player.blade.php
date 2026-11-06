@php $themeAudioUrl = \App\Support\SiteSettings::themeAudioUrl(); @endphp
@if($themeAudioUrl)
    <audio id="site-theme-audio" src="{{ $themeAudioUrl }}" loop preload="auto" aria-hidden="true"></audio>

    {{-- Prominent floating speaker — pulses while muted to advertise the music --}}
    <div class="fixed bottom-6 left-6 z-50 inline-flex items-center gap-3" id="site-audio-wrap">
        <button id="site-audio-toggle"
                type="button"
                title="{{ __('Toggle background music') }}"
                aria-label="{{ __('Toggle background music') }}"
                class="relative inline-flex h-14 w-14 items-center justify-center rounded-full bg-brand-600 shadow-xl shadow-brand-600/40 hover:bg-brand-700 hover:shadow-brand-500/60 transition">
            <svg class="audio-icon-on h-6 w-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/></svg>
            <svg class="audio-icon-off h-6 w-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15zM17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"/></svg>
            <span class="audio-pulse absolute inset-0 rounded-full ring-2 ring-brand-500/70"></span>
            <span class="audio-pulse audio-pulse-2 absolute inset-0 rounded-full ring-2 ring-brand-500/70"></span>
        </button>
        <span id="site-audio-hint"
              class="hidden md:inline-flex items-center gap-1.5 px-3 py-2 rounded-full text-xs font-semibold text-white bg-surface-900/90 backdrop-blur border border-brand-500/40 shadow-lg whitespace-nowrap">
            🎵 {{ __('Tap to enable music') }}
        </span>
    </div>

    <style>
        #site-audio-toggle .audio-icon-on, #site-audio-toggle .audio-icon-off { display: none; }
        #site-audio-toggle.is-playing .audio-icon-on  { display: block; }
        #site-audio-toggle:not(.is-playing) .audio-icon-off { display: block; }
        #site-audio-toggle:not(.is-playing) {
            animation: site-audio-breathe 1.6s ease-in-out infinite;
        }
        @keyframes site-audio-breathe {
            0%, 100% { transform: scale(1); }
            50%      { transform: scale(1.1); }
        }
        #site-audio-toggle.is-playing .audio-pulse {
            animation: site-audio-ripple 1.8s ease-out infinite;
        }
        #site-audio-toggle.is-playing .audio-pulse-2 { animation-delay: 0.6s; }
        #site-audio-toggle:not(.is-playing) .audio-pulse { display: none; }
        @keyframes site-audio-ripple {
            0%   { transform: scale(1);    opacity: 0.7; }
            100% { transform: scale(1.7);  opacity: 0; }
        }
        #site-audio-hint {
            opacity: 0;
            transform: translateX(-8px);
            transition: opacity 0.3s ease, transform 0.3s ease;
            pointer-events: none;
        }
        #site-audio-hint.is-visible { opacity: 1; transform: none; }
    </style>

    <script>
        (function () {
            var audio  = document.getElementById('site-theme-audio');
            var toggle = document.getElementById('site-audio-toggle');
            var hint   = document.getElementById('site-audio-hint');
            if (! audio || ! toggle) return;
            if (window.__pjAudioInit) return;
            window.__pjAudioInit = true;

            // Muted by default — only honour an explicit "unmuted" preference
            // from a previous session. New visitors land in silence and choose
            // to turn the music on themselves via the floating button.
            var saved = null;
            try { saved = localStorage.getItem('site-audio-muted'); } catch (e) {}
            var startUnmuted = saved === '0';

            // Resume position across navigation (sessionStorage, expires after 60s)
            var resumeAt = 0;
            try {
                var rawTime  = sessionStorage.getItem('site-audio-time');
                var rawSaved = sessionStorage.getItem('site-audio-time-at');
                if (rawTime && rawSaved) {
                    var age = (Date.now() - parseInt(rawSaved, 10)) / 1000;
                    if (age < 60) resumeAt = parseFloat(rawTime) || 0;
                }
            } catch (e) {}
            var seekToResume = function () {
                if (resumeAt > 0 && resumeAt < (audio.duration || Infinity)) {
                    try { audio.currentTime = resumeAt; } catch (e) {}
                }
            };
            if (audio.readyState >= 1) seekToResume();
            else audio.addEventListener('loadedmetadata', seekToResume, { once: true });

            audio.muted = ! startUnmuted;
            // Always attempt to play muted — keeps audio context warm so the
            // unmute click takes effect instantly. If autoplay fails (Safari
            // strictness etc.) just stay paused; the toggle still works.
            audio.play().catch(function () {}).finally(updateUI);

            // Show a one-shot hint to first-time visitors that there IS music.
            if (saved === null) showHint();

            // Save current position so the next page picks up where this left off
            var savePos = function () {
                if (audio.currentTime > 0 && ! audio.paused) {
                    try {
                        sessionStorage.setItem('site-audio-time',    String(audio.currentTime));
                        sessionStorage.setItem('site-audio-time-at', String(Date.now()));
                    } catch (e) {}
                }
            };
            setInterval(savePos, 2000);
            window.addEventListener('pagehide',     savePos);
            window.addEventListener('beforeunload', savePos);

            // Removed the "auto-unmute on first gesture" listener: users
            // explicitly enabled muted-by-default, so we honour silence
            // until they tap the speaker button themselves.

            toggle.addEventListener('click', function (ev) {
                ev.stopPropagation();
                audio.muted = ! audio.muted;
                if (! audio.muted) audio.play().catch(function () {});
                try { localStorage.setItem('site-audio-muted', audio.muted ? '1' : '0'); } catch (e) {}
                updateUI();
                hideHint();
            });

            audio.addEventListener('play',  updateUI);
            audio.addEventListener('pause', updateUI);

            function updateUI () {
                toggle.classList.toggle('is-playing', ! audio.muted && ! audio.paused);
            }
            function showHint () { if (hint) { hint.classList.add('is-visible'); setTimeout(hideHint, 6000); } }
            function hideHint () { if (hint) hint.classList.remove('is-visible'); }
        })();
    </script>
@endif
