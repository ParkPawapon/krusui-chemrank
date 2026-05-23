export function initLeaderboard() {
  bindLeaderboardFilter();
  bindLeaderboardPopstate();
}

let activeLeaderboardRequest = null;
let leaderboardPopstateBound = false;

function bindLeaderboardFilter() {
  const form = document.querySelector('[data-leaderboard-filter-form]');
  if (!form || form.dataset.leaderboardFilterBound === 'true') return;

  form.dataset.leaderboardFilterBound = 'true';
  form.addEventListener('submit', (event) => {
    event.preventDefault();
    loadLeaderboard(buildLeaderboardUrl(form), true);
  });

  form.querySelector('[data-leaderboard-class-filter]')?.addEventListener('change', () => {
    loadLeaderboard(buildLeaderboardUrl(form), true);
  });
}

function bindLeaderboardPopstate() {
  if (leaderboardPopstateBound) return;

  leaderboardPopstateBound = true;
  window.addEventListener('popstate', () => {
    if (document.querySelector('[data-leaderboard-frame]')) {
      loadLeaderboard(new URL(window.location.href), false);
    }
  });
}

function buildLeaderboardUrl(form) {
  const url = new URL(form.action, window.location.origin);
  const params = new URLSearchParams(new FormData(form));

  [...params.entries()].forEach(([key, value]) => {
    if (String(value).trim() === '') {
      params.delete(key);
    }
  });

  url.search = params.toString();

  return url;
}

async function loadLeaderboard(url, updateHistory) {
  const frame = document.querySelector('[data-leaderboard-frame]');
  if (!frame) return;

  const nextUrl = url.toString();
  if (nextUrl === window.location.href && updateHistory) return;

  activeLeaderboardRequest?.abort();
  const controller = new AbortController();
  activeLeaderboardRequest = controller;
  frame.setAttribute('aria-busy', 'true');

  try {
    const response = await fetch(nextUrl, {
      headers: {
        Accept: 'text/html',
        'X-Requested-With': 'fetch',
      },
      signal: controller.signal,
    });

    if (!response.ok) {
      throw new Error('Unable to load leaderboard.');
    }

    const html = await response.text();
    const nextDocument = new DOMParser().parseFromString(html, 'text/html');
    const nextFrame = nextDocument.querySelector('[data-leaderboard-frame]');

    if (!nextFrame) {
      throw new Error('Leaderboard frame was not found.');
    }

    frame.replaceWith(nextFrame);
    bindLeaderboardFilter();

    if (updateHistory) {
      window.history.pushState({}, '', nextUrl);
    }
  } catch (error) {
    if (error.name === 'AbortError') return;
    window.location.assign(nextUrl);
  } finally {
    if (activeLeaderboardRequest === controller) {
      activeLeaderboardRequest = null;
    }

    document.querySelector('[data-leaderboard-frame]')?.removeAttribute('aria-busy');
  }
}
