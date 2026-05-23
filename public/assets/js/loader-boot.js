(() => {
  const root = document.documentElement;
  const startedAt = window.performance?.now?.() ?? Date.now();
  const now = Date.now();
  const cooldownMs = 5 * 60 * 1000;
  const storageKey = 'chemrank:last-loader-shown-at';
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

  markShown();
  root.classList.add('has-page-loader');

  let hidden = false;

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
    const now = window.performance?.now?.() ?? Date.now();
    const minVisibleMs = reducedMotion() ? 120 : 760;
    const wait = Math.max(0, minVisibleMs - (now - startedAt));

    window.setTimeout(hideLoader, wait);
  };

  if (document.readyState === 'complete') {
    queueHide();
  } else {
    window.addEventListener('load', queueHide, { once: true });
  }

  window.setTimeout(queueHide, 3600);
})();
