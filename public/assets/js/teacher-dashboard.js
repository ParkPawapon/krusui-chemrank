import { animateDropNear, animateSparklesNear } from './motion.js';
import { showLevelModal, showToast } from './ui.js';

export function initTeacherDashboard() {
  document.querySelectorAll('[data-drop-form]').forEach((form) => {
    form.addEventListener('submit', async (event) => {
      event.preventDefault();

      const submitter = event.submitter;
      const formData = new FormData(form, submitter);
      const row = form.closest('[data-student-row]');
      const amount = Number(formData.get('amount'));

      if (!Number.isFinite(amount) || amount < 1) {
        showToast('จำนวนหยดต้องมากกว่า 0', 'error');
        return;
      }

      setFormBusy(form, true);

      try {
        const response = await fetch(form.action, {
          method: 'POST',
          headers: {
            Accept: 'application/json',
            'X-Requested-With': 'fetch'
          },
          body: formData
        });
        const payload = await response.json();

        if (!response.ok || !payload.ok) {
          showToast(payload.message || 'ไม่สามารถปรับหยดสารได้', 'error');
          return;
        }

        updateStudentRow(row, payload);

        if (formData.get('type') === 'add') {
          animateDropNear(row?.querySelector('[data-drops-value]'));
          animateSparklesNear(row?.querySelector('[data-rank-cell]'));
        }

        if (payload.rankChanged && payload.direction === 'up') {
          showLevelModal({
            title: `วิวัฒน์เป็น ${payload.rank.thaiName}`,
            message: payload.progress.message,
            icon: payload.rank.assetPath
          });
        } else {
          showToast(payload.message || 'ปรับคะแนนเรียบร้อย');
        }
      } catch (error) {
        showToast('เชื่อมต่อระบบไม่ได้ กรุณาลองใหม่', 'error');
      } finally {
        setFormBusy(form, false);
      }
    });
  });
}

function updateStudentRow(row, payload) {
  if (!row) return;

  row.dataset.rankLevel = String(payload.rank?.level || '');

  const dropsNode = row.querySelector('[data-drops-value]');
  if (dropsNode) dropsNode.textContent = payload.student.drops;

  const progressNode = row.querySelector('.mini-progress span');
  if (progressNode) progressNode.style.width = `${payload.progress.percentToNext}%`;

  const messageNode = row.querySelector('[data-progress-message]');
  if (messageNode) messageNode.textContent = payload.progress.message;

  const rankCell = row.querySelector('[data-rank-cell]');
  if (rankCell) {
    rankCell.innerHTML = rankBadgeHtml(payload.rank);
  }
}

function rankBadgeHtml(rank) {
  return `
    <span class="rank-badge rank-badge-sm" style="--rank-color: ${escapeAttr(rank.themeColor)}">
      <img class="pixel-art" src="${escapeAttr(rank.assetPath)}" width="96" height="96" alt="" decoding="async">
      <span>
        <strong>${escapeHtml(rank.thaiName)}</strong>
        <small>${escapeHtml(rank.englishSlug)}</small>
      </span>
    </span>
  `;
}

function setFormBusy(form, busy) {
  form.querySelectorAll('button, input').forEach((control) => {
    control.disabled = busy;
  });
}

function escapeHtml(value) {
  const element = document.createElement('span');
  element.textContent = String(value ?? '');
  return element.innerHTML;
}

function escapeAttr(value) {
  return String(value ?? '').replace(/"/g, '&quot;');
}
