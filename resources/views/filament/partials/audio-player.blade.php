@php $themeAudioUrl = \App\Support\SiteSettings::themeAudioUrl(); @endphp
@if($themeAudioUrl)
    <audio id="site-theme-audio" src="{{ $themeAudioUrl }}" loop preload="auto" aria-hidden="true"></audio>

    {{-- Compact floating speaker — bottom-left, doesn't fight Filament's own UI --}}
    <button id="site-audio-toggle"
            type="button"
            title="{{ __('Toggle background music') }}"
            aria-label="{{ __('Toggle background music') }}"
            class="fixed bottom-6 left-6 z-50 inline-flex h-12 w-12 items-center justify-center rounded-full text-white shadow-lg transition"
            style="background-color: #b32510; box-shadow: 0 8px 24px -6px rgba(179,37,16,0.5);">
        <svg class="audio-icon-on" style="height:1.25rem;width:1.25rem;display:none;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/></svg>
        <svg class="audio-icon-off" style="height:1.25rem;width:1.25rem;display:none;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15zM17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"/></svg>
    </button>

    <style>
        #site-audio-toggle.is-playing .audio-icon-on { display: block !important; }
        #site-audio-toggle:not(.is-playing) .audio-icon-off { display: block !important; }
        #site-audio-toggle:not(.is-playing) {
            animation: site-audio-breathe 1.6s ease-in-out infinite;
        }
        @keyframes site-audio-breathe {
            0%, 100% { transform: scale(1); }
            50%      { transform: scale(1.08); }
        }
    </style>

    <script>
        (function () {
            var audio  = document.getElementById('site-theme-audio');
            var toggle = document.getElementById('site-audio-toggle');
            if (! audio || ! toggle) return;
            if (window.__pjAudioInit) return;
            window.__pjAudioInit = true;

            // Muted by default — only honour an explicit "unmuted" preference.
            var saved = null;
            try { saved = localStorage.getItem('site-audio-muted'); } catch (e) {}
            var startUnmuted = saved === '0';

            // Resume position across navigation (sessionStorage)
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
            audio.play().catch(function () {}).finally(updateUI);

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

            // Auto-unmute on first gesture removed — admin/student must
            // explicitly click the speaker button to enable music.

            toggle.addEventListener('click', function (ev) {
                ev.stopPropagation();
                audio.muted = ! audio.muted;
                if (! audio.muted) audio.play().catch(function () {});
                try { localStorage.setItem('site-audio-muted', audio.muted ? '1' : '0'); } catch (e) {}
                updateUI();
            });

            audio.addEventListener('play',  updateUI);
            audio.addEventListener('pause', updateUI);

            function updateUI () {
                toggle.classList.toggle('is-playing', ! audio.muted && ! audio.paused);
            }
        })();
    </script>
@endif
