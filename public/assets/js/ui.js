export function initConfirmForms() {
  document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      const message = form.getAttribute('data-confirm') || 'ยืนยันการทำรายการ?';

      if (!window.confirm(message)) {
        event.preventDefault();
      }
    });
  });
}

export function initFlashMessages() {
  document.querySelectorAll('[data-flash-message]').forEach((message) => {
    const dismiss = () => {
      message.style.opacity = '0';
      message.style.transform = 'translate3d(12px, -8px, 0) scale(0.98)';
      window.setTimeout(() => message.remove(), 180);
    };

    message.querySelector('[data-flash-close]')?.addEventListener('click', dismiss);
    window.setTimeout(dismiss, 6400);
  });
}

export function showToast(message, variant = 'success') {
  const root = document.querySelector('[data-toast-root]');
  if (!root) return;

  const toast = document.createElement('div');
  toast.className = `toast toast-${variant}`;
  toast.textContent = message;
  root.append(toast);

  window.setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateY(8px)';
    window.setTimeout(() => toast.remove(), 180);
  }, 2800);
}

export function showLevelModal({ title, message, icon }) {
  const modal = document.querySelector('[data-level-modal]');
  if (!modal) return;

  const titleNode = modal.querySelector('[data-level-modal-title]');
  const messageNode = modal.querySelector('[data-level-modal-message]');
  const iconNode = modal.querySelector('[data-level-modal-icon]');

  if (titleNode) titleNode.textContent = title;
  if (messageNode) messageNode.textContent = message;
  if (iconNode && icon) {
    iconNode.src = icon;
    iconNode.hidden = false;
  }

  if (typeof modal.showModal === 'function') {
    modal.showModal();
  } else {
    modal.setAttribute('open', 'open');
  }
}

document.addEventListener('click', (event) => {
  const closeButton = event.target.closest('[data-modal-close]');
  if (!closeButton) return;

  const modal = closeButton.closest('dialog');
  if (!modal) return;

  if (typeof modal.close === 'function') {
    modal.close();
  } else {
    modal.removeAttribute('open');
  }
});
