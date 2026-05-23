(() => {
  const root = document.documentElement;
  const startedAt = window.performance?.now?.() ?? Date.now();
  const now = Date.now();
  const cooldownMs = 5 * 60 * 1000;
  const storageKey = 'chemrank:last-loader-shown-at';
  const logoPreload = document.querySelector('link[rel="preload"][as="image"][href*="chem-rank-loader"]');
  const logoUrl = logoPreload?.href || '/assets/brand/chem-rank-loader.webp';
  const reducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  const readLastShownAt = () => {
    try {
      return Number(window.localStorage.getItem(storageKey) || 0);
    } catch {
      return 0;
    }
  };

  const markShown = () => {
    try {
      window.localStorage.setItem(storageKey, String(now));
    } catch {
      // Storage can be unavailable in private or locked-down browser contexts.
    }
  };

  if (now - readLastShownAt() < cooldownMs) {
    root.classList.add('page-loader-complete', 'page-loader-cooldown');
    return;
  }

  let hidden = false;
  let visible = false;
  let hideRequested = false;
  let visibleAt = startedAt;

  const waitForLogo = () => new Promise((resolve) => {
    if (!logoUrl) {
      resolve();
      return;
    }

    const image = new Image();
    let settled = false;

    const finish = () => {
      if (settled) return;
      settled = true;
      window.clearTimeout(timeout);
      resolve();
    };

    const timeout = window.setTimeout(finish, 1200);
    image.decoding = 'sync';
    image.fetchPriority = 'high';
    image.onload = finish;
    image.onerror = finish;
    image.src = logoUrl;

    if (image.complete && image.naturalWidth > 0) {
      finish();
    }
  });

  const showLoader = () => {
    if (hidden || visible) return;
    visible = true;
    visibleAt = window.performance?.now?.() ?? Date.now();
    markShown();
    root.classList.add('has-page-loader');

    if (hideRequested) {
      queueHide();
    }
  };

  const hideLoader = () => {
    if (hidden) return;
    hidden = true;

    const loader = document.querySelector('[data-page-loader]');

    if (!loader) {
      root.classList.remove('has-page-loader');
      root.classList.add('page-loader-complete');
      return;
    }

    loader.classList.add('is-leaving');

    window.setTimeout(() => {
      loader.classList.add('is-hidden');
      loader.setAttribute('aria-hidden', 'true');
      root.classList.remove('has-page-loader');
      root.classList.add('page-loader-complete');
    }, reducedMotion() ? 80 : 520);
  };

  const queueHide = () => {
    hideRequested = true;
    if (!visible) return;

    const now = window.performance?.now?.() ?? Date.now();
    const minVisibleMs = reducedMotion() ? 120 : 760;
    const wait = Math.max(0, minVisibleMs - (now - visibleAt));

    window.setTimeout(hideLoader, wait);
  };

  waitForLogo().then(showLoader);

  if (document.readyState === 'complete') {
    queueHide();
  } else {
    window.addEventListener('load', queueHide, { once: true });
  }

  window.setTimeout(queueHide, 3600);
})();
