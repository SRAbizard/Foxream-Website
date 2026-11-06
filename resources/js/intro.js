/**
 * First-visit intro (once per browser session): the brand's intro video,
 * the portrait cut on a portrait screen and the landscape cut otherwise,
 * shown whole on black (both videos are on black, so any screen shape
 * looks right). Click anywhere to skip.
 *
 * The layout's head script adds html.site-intro (a black cover) before first
 * paint and puts the videos' URLs on <html data-intro-desktop/-mobile>;
 * this swaps the cover for the video layer. While it runs,
 * html[data-site-intro="running"] is set; `site:intro-exit` fires as the layer
 * starts to leave and `site:intro-done` once it is gone, so the homepage
 * hero entrance can wait for it.
 */
import { gsap } from 'gsap';

export function runIntro() {
    const html = document.documentElement;
    if (!html.classList.contains('site-intro')) return;

    const portrait = window.matchMedia('(orientation: portrait)').matches;
    const src = portrait ? html.dataset.introMobile : html.dataset.introDesktop;

    const layer = document.createElement('div');
    layer.className = 'site-intro-layer';
    layer.setAttribute('aria-hidden', 'true');
    layer.innerHTML = `
        <video class="site-intro-video" muted playsinline autoplay preload="auto" disablepictureinpicture></video>
        <span class="site-intro-skip">Skip ›</span>`;
    document.body.appendChild(layer);
    html.dataset.siteIntro = 'running';
    html.classList.remove('site-intro');
    try { sessionStorage.setItem('site-intro-seen', '1'); } catch { /* private mode */ }

    let leaving = false;
    const finish = () => {
        layer.remove();
        delete html.dataset.siteIntro;
        document.dispatchEvent(new CustomEvent('site:intro-done'));
    };
    const leave = () => {
        if (leaving) return;
        leaving = true;
        document.dispatchEvent(new CustomEvent('site:intro-exit'));
        gsap.to(layer, { opacity: 0, duration: 0.6, ease: 'power2.out', onComplete: finish });
    };

    const video = layer.querySelector('video');
    video.addEventListener('ended', leave);
    video.addEventListener('error', leave);
    if (!src) return leave();
    video.src = src;
    video.play()?.catch(leave);   // autoplay refused: go straight to the page

    // Skip by clicking; and never hold the page: if it has not started
    // within 3 s (slow connection), or runs past 8 s, leave.
    layer.addEventListener('click', leave, { once: true });
    setTimeout(() => { if (video.paused || video.readyState < 3) leave(); }, 3000);
    setTimeout(leave, 8000);
}
