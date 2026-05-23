export function animateDropNear(element) {
  if (reducedMotion() || !element) return;

  const rect = element.getBoundingClientRect();
  const drop = document.createElement('span');
  drop.className = 'drop-particle';
  drop.style.left = `${rect.left + rect.width / 2}px`;
  drop.style.top = `${rect.top - 8}px`;
  document.body.append(drop);
  drop.addEventListener('animationend', () => drop.remove(), { once: true });
}

export function animateSparklesNear(element) {
  if (reducedMotion() || !element) return;

  const rect = element.getBoundingClientRect();
  const vectors = [
    [-20, -18],
    [18, -22],
    [-26, 12],
    [22, 14],
    [0, -30]
  ];

  vectors.forEach(([x, y], index) => {
    const sparkle = document.createElement('span');
    sparkle.className = 'sparkle-particle';
    sparkle.style.left = `${rect.left + rect.width / 2}px`;
    sparkle.style.top = `${rect.top + rect.height / 2}px`;
    sparkle.style.setProperty('--spark-x', `${x}px`);
    sparkle.style.setProperty('--spark-y', `${y}px`);
    sparkle.style.animationDelay = `${index * 35}ms`;
    document.body.append(sparkle);
    sparkle.addEventListener('animationend', () => sparkle.remove(), { once: true });
  });
}

function reducedMotion() {
  return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

