/**
 * First-visit intro (once per browser session): the hinomaru sun grows,
 * "ようこそ" is stamped on it, the brand's name types in, a 出発 hanko lands,
 * then the washi sheet lifts away. Click anywhere to skip.
 *
 * The layout's head script adds html.pj-intro (a plain washi cover) before
 * first paint; this swaps it for the animated layer. While it runs,
 * html[data-pj-intro="running"] is set and `pj:intro-done` fires at the end
 * so the homepage hero entrance can wait for it.
 */
import { gsap } from 'gsap';

export function runIntro() {
    const html = document.documentElement;
    if (!html.classList.contains('pj-intro')) return;

    const layer = document.createElement('div');
    layer.className = 'pj-intro-layer';
    layer.setAttribute('aria-hidden', 'true');
    layer.innerHTML = `
        <div class="pj-intro-stage">
            <div class="pj-intro-sun"></div>
            <div class="pj-intro-jp">ようこそ</div>
            <div class="pj-intro-hanko">出発</div>
            <div class="pj-intro-name">${(document.documentElement.dataset.brand || 'FOXREAM').split('').map((ch) => (ch === ' ' ? '<span class="gap"></span>' : `<span>${ch}</span>`)).join('')}</div>
        </div>
        <span class="pj-intro-skip">Skip ›</span>
        <div class="edge"></div>`;
    document.body.appendChild(layer);
    html.dataset.pjIntro = 'running';
    html.classList.remove('pj-intro');
    try { sessionStorage.setItem('pj-intro-seen', '1'); } catch { /* private mode */ }

    const finish = () => {
        layer.remove();
        delete html.dataset.pjIntro;
        document.dispatchEvent(new CustomEvent('pj:intro-done'));
    };

    const tl = gsap.timeline({ onComplete: finish });
    tl.from('.pj-intro-sun', { scale: 0, duration: 0.7, ease: 'back.out(1.6)' })
        .from('.pj-intro-jp', { scale: 2.4, opacity: 0, rotate: -12, duration: 0.45, ease: 'power4.in' }, '-=0.15')
        .to('.pj-intro-stage', { x: 6, y: 6, duration: 0.06, yoyo: true, repeat: 1 })
        .from('.pj-intro-name span', { y: 30, opacity: 0, duration: 0.35, stagger: 0.03, ease: 'back.out(2)' }, '-=0.1')
        .from('.pj-intro-hanko', { scale: 2.2, opacity: 0, rotate: 30, duration: 0.3, ease: 'power4.in' }, '-=0.2')
        .to('.pj-intro-hanko', { rotate: -14, duration: 0.15 })
        .add(() => document.dispatchEvent(new CustomEvent('pj:intro-exit')), '+=0.35')
        .to(layer, { yPercent: -110, duration: 0.75, ease: 'power4.inOut' }, '<');

    // Skip: jump to the exit.
    layer.addEventListener('click', () => {
        // suppressEvents=false so the pj:intro-exit callback still fires.
        if (tl.progress() < 0.8) tl.seek(tl.duration() - 0.75, false);
    }, { once: true });

    // If animation frames are throttled (background tab), don't block the page.
    setTimeout(() => { if (layer.isConnected) { tl.kill(); finish(); } }, 6000);
}
