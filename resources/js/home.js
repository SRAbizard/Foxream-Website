/**
 * Homepage interactions — "Misi ke Jepang".
 *
 * Boots whenever a page containing [data-pj-home] is shown (first load and
 * every wire:navigate swap) and tears everything down on the way out, so
 * ScrollTriggers, listeners and the petal loop never leak across pages.
 * All content is rendered server-side; this only adds behaviour.
 */
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { MotionPathPlugin } from 'gsap/MotionPathPlugin';

gsap.registerPlugin(ScrollTrigger, MotionPathPlugin);

let teardown = null;

function boot() {
    teardown?.();
    teardown = null;
    const root = document.querySelector('[data-pj-home]');
    const dataEl = document.getElementById('pj-home-data');
    if (!root || !dataEl) return;

    let data;
    try { data = JSON.parse(dataEl.textContent); } catch { return; }

    const ac = new AbortController();
    const on = (el, ev, fn, opts = {}) => el?.addEventListener(ev, fn, { signal: ac.signal, ...opts });
    const ctx = gsap.context(() => {}, root);
    const cleanups = [];
    const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
    const $ = (s, r = root) => r.querySelector(s);
    const $$ = (s, r = root) => [...r.querySelectorAll(s)];
    const t = data.i18n;
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const fmtYen = (n) => '¥' + Number(n).toLocaleString('id-ID');
    const fmtRp = (n) => 'Rp' + (n * data.rate / 1e6).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' ' + t.mil;
    const regionByKey = Object.fromEntries(data.regions.map((r) => [r.key, r]));

    /* ---------- finder + boarding pass ---------- */
    const finder = $('[data-pj-finder]');
    const fCat = $('#pj-f-category'), fVisa = $('#pj-f-visa'), fRegion = $('#pj-f-region');
    const countEl = $('[data-pj-count]');
    const matches = () => data.jobs.filter((j) =>
        (!fCat.value || j.category === fCat.value) &&
        (!fVisa.value || j.visa === fVisa.value) &&
        (!fRegion.value || j.region === fRegion.value));

    function bump(el, to) {
        if (!el) return;
        if (reduce) { el.textContent = to; return; }
        const o = { v: Number(el.textContent) || 0 };
        ctx.add(() => {
            gsap.to(o, { v: to, duration: 0.5, ease: 'power2.out', onUpdate: () => { el.textContent = Math.round(o.v); } });
            gsap.fromTo(el, { y: -6 }, { y: 0, duration: 0.35, ease: 'back.out(3)' });
        });
    }
    function updateFinder(wobble = true) {
        if (!finder) return;
        bump(countEl, matches().length);
        const region = regionByKey[fRegion.value] || regionByKey.kanto || data.regions[0];
        if (region) {
            $('[data-pj-bp-code]').textContent = region.airport[0];
            $('[data-pj-bp-city]').textContent = region.airport[1];
        }
        $('[data-pj-bp-seat]').textContent = fCat.value ? fCat.selectedOptions[0].text : t.allFields;
        $$('[data-pj-chip]').forEach((c) => c.setAttribute('aria-pressed', String(c.dataset.pjChip === fCat.value)));
        if (wobble && !reduce) ctx.add(() => gsap.fromTo('.pj-pass', { rotate: -7 }, { rotate: -4, duration: 0.6, ease: 'elastic.out(1, .4)' }));
    }
    [fCat, fVisa, fRegion].forEach((s) => on(s, 'change', () => updateFinder()));
    $$('[data-pj-chip]').forEach((c) => on(c, 'click', () => {
        fCat.value = fCat.value === c.dataset.pjChip ? '' : c.dataset.pjChip;
        updateFinder();
    }));
    // Drop empty params so /jobs gets a clean query string.
    on(finder, 'submit', () => [fCat, fVisa, fRegion].forEach((s) => { s.disabled = !s.value; }));
    on(window, 'pageshow', () => [fCat, fVisa, fRegion].forEach((s) => { if (s) s.disabled = false; }));

    /* ---------- hero slides (admin "Hero background slides") ---------- */
    // Re-queried each tick: images that fail to load remove themselves.
    if ($('[data-pj-slides]') && !reduce) {
        const id = setInterval(() => {
            const imgs = $$('[data-pj-slides] img');
            if (!imgs.length) return;
            const cur = imgs.findIndex((img) => img.classList.contains('is-on'));
            imgs[cur]?.classList.remove('is-on');
            imgs[(cur + 1) % imgs.length].classList.add('is-on');
        }, 4500);
        cleanups.push(() => clearInterval(id));
    }

    /* ---------- hero parallax ---------- */
    const scene = $('[data-pj-scene]');
    if (scene && !reduce && matchMedia('(pointer: fine)').matches) {
        const items = $$('[data-depth]', scene);
        on($('.pj-hero'), 'pointermove', (e) => {
            const x = e.clientX / innerWidth - 0.5, y = e.clientY / innerHeight - 0.5;
            // The individual `translate` property composes with each piece's
            // CSS rotate, so the tilted boarding pass keeps its angle.
            items.forEach((el) => {
                const d = Number(el.dataset.depth);
                el.style.translate = `${-x * d}px ${-y * d}px`;
            });
        });
    }

    /* ---------- sakura petals ---------- */
    const cv = $('[data-pj-petals]');
    if (cv && !reduce) {
        const c2d = cv.getContext('2d');
        const petalColor = getComputedStyle(document.documentElement).getPropertyValue('--pj-sakura').trim() || '#F5C3C8';
        let W = 0, H = 0, raf = 0, visible = true;
        const size = () => {
            const r = cv.getBoundingClientRect(), d = Math.min(devicePixelRatio || 1, 2);
            W = r.width; H = r.height; cv.width = W * d; cv.height = H * d; c2d.setTransform(d, 0, 0, d, 0, 0);
        };
        const spawn = (y) => ({ x: Math.random() * W, y: y ?? -20, s: 6 + Math.random() * 8, vx: 0.3 + Math.random() * 0.8, vy: 0.5 + Math.random(), r: Math.random() * 6.28, vr: (Math.random() - 0.5) * 0.04, sw: Math.random() * 6.28 });
        size();
        const petals = Array.from({ length: Math.round(Math.min(26, W / 40)) }, () => spawn(Math.random() * H));
        const tick = () => {
            if (!visible) { raf = 0; return; }
            c2d.clearRect(0, 0, W, H);
            for (const p of petals) {
                p.sw += 0.02; p.x += p.vx + Math.sin(p.sw) * 0.6; p.y += p.vy; p.r += p.vr;
                if (p.y > H + 20 || p.x > W + 20) Object.assign(p, spawn());
                c2d.save(); c2d.translate(p.x, p.y); c2d.rotate(p.r);
                c2d.beginPath(); c2d.moveTo(0, -p.s);
                c2d.bezierCurveTo(p.s * 0.9, -p.s * 0.5, p.s * 0.6, p.s * 0.7, 0, p.s);
                c2d.bezierCurveTo(-p.s * 0.6, p.s * 0.7, -p.s * 0.9, -p.s * 0.5, 0, -p.s);
                c2d.fillStyle = petalColor; c2d.globalAlpha = 0.9; c2d.fill(); c2d.globalAlpha = 1;
                c2d.lineWidth = 1.2; c2d.strokeStyle = 'rgba(29,23,48,.35)'; c2d.stroke(); c2d.restore();
            }
            raf = requestAnimationFrame(tick);
        };
        on(window, 'resize', size);
        const io = new IntersectionObserver(([e]) => { visible = e.isIntersecting; if (visible && !raf) tick(); });
        io.observe($('.pj-hero'));
        tick();
        cleanups.push(() => { io.disconnect(); cancelAnimationFrame(raf); visible = false; });
    }

    /* ---------- statement: split into words ---------- */
    const seg = typeof Intl !== 'undefined' && Intl.Segmenter ? new Intl.Segmenter(document.documentElement.lang || 'id', { granularity: 'word' }) : null;
    $$('[data-pj-statement] [data-words]').forEach((span) => {
        const hl = span.hasAttribute('data-hl');
        const parts = seg ? [...seg.segment(span.textContent)].map((s) => s.segment) : span.textContent.split(/(\s+)/);
        span.innerHTML = parts.map((p) => (/^\s+$/.test(p) ? p : `<span class="w${hl ? ' hl' : ''}">${esc(p)}</span>`)).join('');
    });
    const words = $$('[data-pj-statement] .w');

    /* ---------- workflow tabs ---------- */
    const tabs = $$('[data-pj-tab]');
    tabs.forEach((tab) => on(tab, 'click', () => {
        tabs.forEach((x) => x.setAttribute('aria-selected', String(x === tab)));
        $$('[data-pj-panel]').forEach((p) => { p.hidden = p.dataset.pjPanel !== tab.dataset.pjTab; });
    }));

    /* ---------- map ---------- */
    const cityCard = $('[data-pj-city]');
    const jobsUrl = (params) => data.jobsUrl + '?' + new URLSearchParams(Object.entries(params).filter(([, v]) => v)).toString();
    const miniJob = (j) => `<a class="pj-mini-job" href="${esc(j.url)}"><div><b>${esc(j.title)}</b><span>${esc(j.company)} · ${esc(j.location)}</span></div>${j.min && j.currency === 'JPY' ? `<span class="yen">${fmtYen(j.min)}+</span>` : ''}</a>`;
    function showRegion(key) {
        const r = regionByKey[key];
        if (!r || !cityCard) return;
        const list = data.jobs.filter((j) => j.region === key);
        $$('[data-pj-pin]').forEach((p) => p.classList.toggle('is-active', p.dataset.pjPin === key));
        cityCard.innerHTML = `
            <div class="pj-city-head"><h3>${esc(r.name)}</h3><span class="jp">${esc(r.jp)}</span></div>
            <p class="pj-city-count"><b>${list.length}</b> ${esc(t.jobsInRegion)}</p>
            ${list.length ? `<div class="pj-mini-jobs">${list.slice(0, 4).map(miniJob).join('')}</div>` : `<p class="pj-lede">${esc(t.noJobsRegion)}</p>`}
            <div><a class="pj-btn" href="${esc(jobsUrl({ region: key }))}">${esc(t.seeRegion.replace(':region', r.name))} →</a></div>`;
        if (!reduce) ctx.add(() => gsap.from(cityCard.children, { y: 12, opacity: 0, duration: 0.35, stagger: 0.05 }));
    }
    $$('[data-pj-pin]').forEach((pin) => {
        on(pin, 'click', () => showRegion(pin.dataset.pjPin));
        on(pin, 'keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); showRegion(pin.dataset.pjPin); } });
    });
    const busiest = [...data.regions].sort((a, b) => b.count - a.count)[0];
    if (busiest) showRegion(busiest.key);

    /* ---------- salary currency toggle ---------- */
    const curBtns = $$('[data-pj-cur]');
    curBtns.forEach((b) => on(b, 'click', () => {
        const cur = b.dataset.pjCur;
        curBtns.forEach((x) => x.setAttribute('aria-pressed', String(x === b)));
        $$('[data-pj-salary]').forEach((el) => {
            const min = Number(el.dataset.min), max = Number(el.dataset.max), per = el.dataset.period;
            const yen = max && max !== min ? `${fmtYen(min)} – ${fmtYen(max)}` : fmtYen(min);
            const rp = max && max !== min ? `${fmtRp(min)} – ${fmtRp(max)}` : fmtRp(min);
            el.innerHTML = cur === 'jpy'
                ? `<b>${yen}</b><small>≈ ${rp} ${esc(per)}</small>`
                : `<b>${rp}</b><small>${yen} ${esc(per)} · ${esc(t.estimate)}</small>`;
        });
    }));

    /* ---------- job card tilt ---------- */
    if (!reduce && matchMedia('(pointer: fine)').matches) {
        $$('[data-pj-tilt]').forEach((card) => {
            on(card, 'pointermove', (e) => {
                const r = card.getBoundingClientRect();
                const x = (e.clientX - r.left) / r.width - 0.5, y = (e.clientY - r.top) / r.height - 0.5;
                card.style.transform = `perspective(700px) rotateY(${x * 8}deg) rotateX(${-y * 8}deg) translate(-2px,-2px)`;
            });
            on(card, 'pointerleave', () => { card.style.transform = ''; });
        });
    }

    /* ---------- match quiz ---------- */
    const game = $('[data-pj-game]');
    const QS = data.quiz;
    let step = 0, answers = {};
    function setBar(n) {
        $('[data-pj-xp]').style.width = (n / QS.length) * 100 + '%';
        $('[data-pj-q-xp]').textContent = n * 25 + ' XP';
        $('[data-pj-q-label]').textContent = n < QS.length ? `${t.question} ${n + 1} / ${QS.length}` : t.done;
    }
    function renderQ() {
        setBar(step);
        const Q = QS[step];
        game.innerHTML = `<span class="pj-q-num">${esc(t.question)} ${step + 1}</span><p class="pj-q-title">${esc(Q.q)}</p>
            <div class="pj-opts">${Q.o.map((o, i) => `<button type="button" class="pj-opt" data-i="${i}">${esc(o[0])}${o[1] ? `<small>${esc(o[1])}</small>` : ''}</button>`).join('')}</div>`;
        bindOpts();
        if (!reduce) ctx.add(() => gsap.from($$('.pj-opt', game), { y: 14, opacity: 0, duration: 0.3, stagger: 0.05, ease: 'back.out(2)' }));
    }
    function bindOpts() {
        $$('.pj-opt', game).forEach((b, i) => on(b, 'click', () => {
            answers[QS[step].k] = QS[step].o[Number(b.dataset.i ?? i)][2];
            step++;
            step < QS.length ? renderQ() : renderResult();
        }));
    }
    function evaluate(a) {
        const cats = a.field || [];
        let route, why;
        // The fields are all SSW; an engineering/IT degree is what opens Gijinkoku.
        if (a.edu === 2) { route = 'engineer'; why = t.whyEngineer; }
        else if (a.lang >= 2) { route = 'ssw'; why = t.whySsw; }
        else if (a.age <= 2) { route = 'intern'; why = t.whyIntern; }
        else { route = 'ssw'; why = t.whySswPrep; }
        let pct = 58 + a.lang * 10 + (3 - Math.min(a.age, 3)) * 4 + (route === 'engineer' ? 8 : 0);
        pct = Math.max(45, Math.min(96, pct));
        const slug = data.routes[route]?.slug;
        const inRoute = data.jobs.filter((j) => !slug || j.visa === slug);
        let jobs = inRoute.filter((j) => cats.includes(j.category));
        if (!jobs.length) jobs = inRoute;
        return { route, why, pct, slug, cats, jobs: jobs.slice(0, 3), needsAcademy: a.lang < 2 };
    }
    function renderResult() {
        setBar(QS.length);
        const r = evaluate(answers);
        const C = 2 * Math.PI * 52;
        const params = { visa: r.slug, category: r.jobs.some((j) => r.cats.includes(j.category)) ? r.cats[0] : '' };
        game.innerHTML = `<div class="pj-result">
            <div class="pj-result-top">
                <div class="pj-ring"><svg viewBox="0 0 128 128"><circle class="bg" cx="64" cy="64" r="52"/><circle class="fg" cx="64" cy="64" r="52" stroke-dasharray="${C}" stroke-dashoffset="${C}"/></svg><b>0%</b></div>
                <div style="min-width:0;display:grid;gap:.4rem"><span class="pj-q-num">${esc(t.bestRoute)}</span><p class="pj-route-name">${esc(data.routes[r.route].name)}</p><p>${esc(r.why)}</p></div>
            </div>
            ${r.needsAcademy ? `<p class="pj-note">${esc(t.academyNote)}${data.academy ? ` <a href="${esc(data.academy)}" target="_blank" rel="noopener">${esc(t.academyLink)} ↗</a>` : ''}</p>` : ''}
            ${r.jobs.length ? `<div class="pj-mini-jobs">${r.jobs.map(miniJob).join('')}</div>` : `<p class="pj-lede">${esc(t.noMatch)}</p>`}
            <div style="display:flex;flex-wrap:wrap;gap:.6rem"><a class="pj-btn is-red" href="${esc(jobsUrl(params))}">${esc(t.seeJobs)} →</a><button type="button" class="pj-btn" data-pj-again>${esc(t.again)}</button></div>
        </div>`;
        const fg = $('.pj-ring .fg', game), num = $('.pj-ring b', game);
        requestAnimationFrame(() => { fg.style.strokeDashoffset = C * (1 - r.pct / 100); });
        if (reduce) num.textContent = r.pct + '%';
        else { const o = { v: 0 }; ctx.add(() => gsap.to(o, { v: r.pct, duration: 1.2, ease: 'power2.out', onUpdate: () => { num.textContent = Math.round(o.v) + '%'; } })); }
        on($('[data-pj-again]', game), 'click', () => { step = 0; answers = {}; renderQ(); });
    }
    if (game) bindOpts();

    /* ---------- scroll choreography ---------- */
    const steps = $$('[data-pj-step]');
    if (reduce) {
        steps.forEach((s) => s.classList.add('done'));
        words.forEach((w) => w.classList.add('on'));
    } else {
        ctx.add(() => {
            if (words.length) {
                ScrollTrigger.create({
                    trigger: '[data-pj-statement]', start: 'top 80%', end: 'bottom 45%', scrub: true,
                    onUpdate: (self) => { const n = Math.round(self.progress * words.length); words.forEach((w, i) => w.classList.toggle('on', i < n)); },
                });
            }

            $$('[data-pj-countup]').forEach((b) => {
                const raw = b.textContent.trim();
                const m = raw.match(/^([^\d]*)([\d.,]+)(.*)$/);
                if (!m) return;
                const to = Number(m[2].replace(/[.,]/g, ''));
                const o = { v: 0 };
                ScrollTrigger.create({ trigger: b, start: 'top 90%', once: true, onEnter: () => gsap.to(o, { v: to, duration: 1.4, ease: 'power2.out', onUpdate: () => { b.textContent = m[1] + Math.round(o.v).toLocaleString('id-ID') + m[3]; }, onComplete: () => { b.textContent = raw; } }) });
            });

            $$('.pj-head').forEach((h) => gsap.from(h.children, { scrollTrigger: { trigger: h, start: 'top 85%' }, y: 26, rotate: -1.5, duration: 0.6, stagger: 0.08, ease: 'back.out(1.7)', clearProps: 'transform' }));
            $$('.pj-polaroid').forEach((p, i) => gsap.from(p, { scrollTrigger: { trigger: '.pj-polaroids', start: 'top 85%' }, y: 60, rotate: i % 2 ? 10 : -10, duration: 0.7, delay: i * 0.08, ease: 'back.out(1.4)', clearProps: 'transform' }));
            $$('.pj-quote').forEach((q, i) => gsap.from(q, { scrollTrigger: { trigger: q, start: 'top 90%' }, y: 40, opacity: 0, duration: 0.6, delay: (i % 3) * 0.08, ease: 'back.out(1.4)', clearProps: 'transform,opacity' }));

            const mm = gsap.matchMedia();
            cleanups.push(() => mm.revert());
            mm.add('(min-width: 901px)', () => {
                const path = $('[data-pj-route-path]'), done = $('[data-pj-route-done]');
                if (!path || !done) return;
                const len = path.getTotalLength();
                done.style.strokeDasharray = len; done.style.strokeDashoffset = len;
                const marks = [0.06, 0.29, 0.52, 0.74, 0.96];
                gsap.timeline({
                    scrollTrigger: {
                        trigger: '[data-pj-route-stage]', start: 'center center', end: '+=1200', scrub: 0.6, pin: '#rute',
                        onUpdate: (self) => steps.forEach((s, i) => s.classList.toggle('done', self.progress >= marks[i])),
                    },
                })
                    .to('[data-pj-route-plane]', { motionPath: { path, align: path, alignOrigin: [0.5, 0.5], autoRotate: true }, ease: 'none', duration: 1 }, 0)
                    .to(done, { strokeDashoffset: 0, ease: 'none', duration: 1 }, 0);
            });
            mm.add('(max-width: 900px)', () => {
                steps.forEach((s) => ScrollTrigger.create({ trigger: s, start: 'top 75%', onEnter: () => s.classList.add('done'), onLeaveBack: () => s.classList.remove('done') }));
            });
        });
    }

    updateFinder(false);

    /* ---------- hero entrance (every homepage visit) ---------- */
    const html = document.documentElement;
    if (reduce || !$('.pj-hero')) {
        html.classList.add('pj-entered');
    } else {
        let entered = false;
        const enter = () => {
            if (entered) return;
            entered = true;
            ctx.add(() => {
                // The CSS keeps the hero hidden until .pj-entered; adding it in
                // the same task as the from() tweens means no frame shows the
                // hero before the tweens take over.
                html.classList.add('pj-entered');
                const tl = gsap.timeline({ defaults: { ease: 'back.out(1.7)' } });
                tl.from('.pj-hero .pj-sticker', { y: -30, rotate: -18, opacity: 0, duration: 0.35 }, 0)
                    .from('.pj-hero h1 .l', { yPercent: 70, opacity: 0, duration: 0.45, stagger: 0.07, ease: 'power3.out' }, 0.05)
                    .from('.pj-hero h1 .flag', { scale: 1.7, rotate: -14, opacity: 0, duration: 0.3, ease: 'power4.in' }, 0.3)
                    .from('.pj-hero .pj-lede', { y: 14, opacity: 0, duration: 0.3, ease: 'power2.out' }, 0.45)
                    .from('.pj-hero .pj-finder', { y: 40, opacity: 0, duration: 0.45 }, 0.5)
                    .from('.pj-scene .sun', { scale: 0, duration: 0.55 }, 0.05)
                    .from('.pj-scene .fuji', { yPercent: 35, opacity: 0, duration: 0.45, ease: 'power3.out' }, 0.2)
                    .from('.pj-scene .cloud.c1', { x: 100, opacity: 0, duration: 0.6, ease: 'power2.out' }, 0.3)
                    .from('.pj-scene .cloud.c2', { x: -100, opacity: 0, duration: 0.6, ease: 'power2.out' }, 0.32)
                    .from('.pj-scene .crane', { x: -140, y: 70, rotate: -25, opacity: 0, duration: 0.7, ease: 'power2.out' }, 0.35)
                    .from('.pj-pass', { x: 200, rotate: 14, opacity: 0, duration: 0.55 }, 0.45);
                // Hand the inline transforms back to the stylesheet afterwards.
                tl.eventCallback('onComplete', () => gsap.set('.pj-hero .pj-sticker, .pj-hero h1 .l, .pj-hero h1 .flag, .pj-hero .pj-lede, .pj-hero .pj-finder, .pj-scene > *', { clearProps: 'transform,opacity' }));
            });
        };
        if (html.dataset.pjIntro === 'running' || html.classList.contains('pj-intro')) {
            // Start as the intro sheet begins to lift, not after it is gone.
            on(document, 'pj:intro-exit', enter, { once: true });
            on(document, 'pj:intro-done', enter, { once: true });
        } else {
            enter();
        }
    }

    teardown = () => {
        ac.abort();
        cleanups.forEach((fn) => fn());
        ctx.revert();
    };
}

// The module may run after DOMContentLoaded has already fired.
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
else boot();
document.addEventListener('livewire:navigated', boot);
document.addEventListener('livewire:navigating', () => { teardown?.(); teardown = null; });
