const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const LOADER_MIN_VISIBLE_MS = 680;
const MAX_REVEAL_TARGETS = 140;

function createNoopLoaderController() {
    return {
        show() {},
        hide() {},
    };
}

function initPageLoader() {
    const loader = document.getElementById('ss-page-loader');
    if (!loader) {
        return createNoopLoaderController();
    }

    const now = () => (typeof performance !== 'undefined' ? performance.now() : Date.now());
    let visibleSince = now();
    let hideTimer = null;

    const show = () => {
        if (hideTimer) {
            window.clearTimeout(hideTimer);
            hideTimer = null;
        }

        visibleSince = now();
        loader.classList.add('is-active');
        loader.setAttribute('aria-hidden', 'false');
        document.body.classList.add('ss-loader-active');
    };

    const hide = (immediate = false) => {
        if (hideTimer) {
            window.clearTimeout(hideTimer);
        }

        const elapsed = now() - visibleSince;
        const waitFor = immediate ? 0 : Math.max(0, LOADER_MIN_VISIBLE_MS - elapsed);

        hideTimer = window.setTimeout(() => {
            loader.classList.remove('is-active');
            loader.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('ss-loader-active');
            hideTimer = null;
        }, waitFor);
    };

    show();

    if (document.readyState === 'complete') {
        hide(true);
    } else {
        window.addEventListener('load', () => hide(false), { once: true });
    }

    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            hide(true);
        }
    });

    return { show, hide };
}

function shouldSkipLink(link, event) {
    if (!link) {
        return true;
    }

    if (link.dataset.noTransition !== undefined) {
        return true;
    }

    if (event.defaultPrevented || event.button !== 0) {
        return true;
    }

    if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
        return true;
    }

    const href = (link.getAttribute('href') || '').trim();
    if (!href || href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) {
        return true;
    }

    if (link.hasAttribute('download')) {
        return true;
    }

    const target = (link.getAttribute('target') || '').toLowerCase();
    if (target && target !== '_self') {
        return true;
    }

    const url = new URL(link.href, window.location.href);
    if (url.origin !== window.location.origin) {
        return true;
    }

    const current = new URL(window.location.href);
    if (url.pathname === current.pathname && url.search === current.search && url.hash) {
        return true;
    }

    return false;
}

function initNavigationFluidity(loaderController) {
    document.addEventListener('click', (event) => {
        const link = event.target.closest('a');
        if (shouldSkipLink(link, event)) {
            return;
        }

        loaderController.show();
        document.body.classList.add('ss-page-transitioning');
    }, true);

    window.addEventListener('pageshow', () => {
        document.body.classList.remove('ss-page-transitioning');
        loaderController.hide(true);
    });
}

function initFormFluidity(loaderController) {
    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        if (form.dataset.noLoader !== undefined || form.dataset.noTransition !== undefined) {
            return;
        }

        const target = (form.getAttribute('target') || '').toLowerCase();
        if (target && target !== '_self') {
            return;
        }

        window.setTimeout(() => {
            if (event.defaultPrevented) {
                return;
            }

            loaderController.show();
            document.body.classList.add('ss-page-transitioning');
        }, 0);
    }, true);
}

function isCardLikeCandidate(element) {
    if (!(element instanceof HTMLElement)) {
        return false;
    }

    if (element.classList.contains('report-reveal')) {
        return false;
    }

    const className = typeof element.className === 'string' ? element.className : '';
    if (!/(rounded-xl|rounded-2xl|rounded-3xl)/.test(className)) {
        return false;
    }

    return /(shadow-|border|bg-white|backdrop-blur|bg-slate-|bg-indigo-|bg-emerald-|bg-amber-|bg-rose-)/.test(className);
}

function hasRevealSize(element) {
    if (element.matches('table, .modal-box, .login-neon-card, .report-nav-shell')) {
        return true;
    }

    return element.offsetWidth >= 120 && element.offsetHeight >= 60;
}

function isHiddenElement(element) {
    if (element.classList.contains('modal-box')) {
        return false;
    }

    if (element.offsetParent === null) {
        return true;
    }

    const computed = window.getComputedStyle(element);
    return computed.display === 'none' || computed.visibility === 'hidden';
}

function collectRevealTargets() {
    const selectors = [
        'main .rounded-2xl.shadow-sm',
        'main .rounded-2xl.shadow-md',
        'main .rounded-2xl.shadow-lg',
        'main .rounded-xl.shadow-sm',
        'main .rounded-xl.shadow-md',
        'main .rounded-xl.shadow-lg',
        'main .bg-white.rounded-2xl',
        'main .bg-white.rounded-xl',
        'main .rounded-2xl.border',
        'main .rounded-xl.border',
        'main [class*="rounded-2xl"][class*="border"]',
        'main [class*="rounded-xl"][class*="border"]',
        'main table',
        'main .report-nav-shell',
        'main [data-fluid]',
        '.modal-box',
        '.login-neon-card',
    ];

    const broadCardCandidates = document.querySelectorAll('main [class*="rounded-xl"], main [class*="rounded-2xl"], main [class*="rounded-3xl"]');

    const seen = new Set();
    const targets = [];

    const pushTarget = (element) => {
        if (!(element instanceof HTMLElement)) {
            return;
        }

        if (element.dataset.noFluid !== undefined || element.closest('[data-no-fluid]')) {
            return;
        }

        if (element.classList.contains('report-reveal')) {
            return;
        }

        if (seen.has(element)) {
            return;
        }

        if (isHiddenElement(element)) {
            return;
        }

        if (!hasRevealSize(element)) {
            return;
        }

        seen.add(element);
        targets.push(element);
    };

    for (const selector of selectors) {
        const elements = document.querySelectorAll(selector);
        for (const element of elements) {
            pushTarget(element);
        }
    }

    for (const element of broadCardCandidates) {
        if (!isCardLikeCandidate(element)) {
            continue;
        }

        pushTarget(element);
    }

    return targets.slice(0, MAX_REVEAL_TARGETS);
}

function initRevealAnimations() {
    const targets = collectRevealTargets();
    if (targets.length === 0) {
        return;
    }

    targets.forEach((element, index) => {
        element.classList.add('ss-fluid-reveal');
        element.style.setProperty('--ss-fluid-delay', `${Math.min(index * 34, 520)}ms`);
    });

    if (!('IntersectionObserver' in window)) {
        targets.forEach((element) => element.classList.add('ss-fluid-visible'));
        return;
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            entry.target.classList.toggle('ss-fluid-visible', entry.isIntersecting);
        });
    }, {
        threshold: 0.08,
        rootMargin: '0px 0px -40px 0px',
    });

    targets.forEach((element) => observer.observe(element));
}

function initMobileEnhancements() {
    const setMobileState = () => {
        const isMobileViewport = window.matchMedia('(max-width: 1024px)').matches;
        document.documentElement.classList.toggle('ss-mobile-ui', isMobileViewport);
    };

    setMobileState();
    window.addEventListener('resize', setMobileState, { passive: true });

    const tables = document.querySelectorAll('main table');
    tables.forEach((table) => {
        if (!(table instanceof HTMLTableElement)) {
            return;
        }

        if (table.dataset.noMobileWrap !== undefined) {
            return;
        }

        if (table.closest('.fc') || table.closest('.ss-table-scroll') || table.closest('.overflow-x-auto') || table.closest('.overflow-auto')) {
            return;
        }

        const parent = table.parentElement;
        if (!parent) {
            return;
        }

        const wrapper = document.createElement('div');
        wrapper.className = 'ss-table-scroll';
        parent.insertBefore(wrapper, table);
        wrapper.appendChild(table);
    });
}

function initFluidity() {
    document.documentElement.classList.add('ss-fluid-ready');

    const loaderController = initPageLoader();
    initNavigationFluidity(loaderController);
    initFormFluidity(loaderController);
    initMobileEnhancements();

    if (reducedMotion) {
        return;
    }

    initRevealAnimations();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initFluidity);
} else {
    initFluidity();
}
