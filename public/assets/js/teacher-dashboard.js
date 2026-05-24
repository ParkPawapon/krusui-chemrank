import { animateDropNear, animateSparklesNear } from './motion.js';
import { showLevelModal, showToast } from './ui.js';

export function initTeacherDashboard() {
  initTeacherFilters();
  initImportPreview();
  bindDropForms(document);
}

let activeTeacherFilterRequest = null;
let teacherPopstateBound = false;

function bindDropForms(root) {
  root.querySelectorAll('[data-drop-form]').forEach((form) => {
    if (form.dataset.dropBound === 'true') return;
    form.dataset.dropBound = 'true';

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

function initTeacherFilters() {
  const form = document.querySelector('[data-teacher-filter-form]');
  if (!form) return;
  if (form.dataset.filterBound === 'true') return;
  form.dataset.filterBound = 'true';

  const searchInput = form.querySelector('[data-student-search]');
  const classSelect = form.querySelector('[data-class-filter]');
  let timer = null;

  const submitFilter = () => {
    const url = buildFilterUrl(form);
    updateTeacherExportLink(url);
    loadTeacherResults(url, true);
  };

  form.addEventListener('submit', (event) => {
    event.preventDefault();
    submitFilter();
  });

  searchInput?.addEventListener('input', () => {
    window.clearTimeout(timer);
    timer = window.setTimeout(submitFilter, 360);
  });

  classSelect?.addEventListener('change', submitFilter);

  if (!teacherPopstateBound) {
    teacherPopstateBound = true;
    window.addEventListener('popstate', () => {
      if (document.querySelector('[data-teacher-results]')) {
        loadTeacherResults(new URL(window.location.href), false);
      }
    });
  }
}

function buildFilterUrl(form) {
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

function updateTeacherExportLink(filterUrl) {
  const exportLink = document.querySelector('[data-teacher-export-link]');
  if (!exportLink) return;

  const url = new URL('/teacher/students/export', window.location.origin);
  filterUrl.searchParams.forEach((value, key) => {
    if (['q', 'class'].includes(key) && String(value).trim() !== '') {
      url.searchParams.set(key, value);
    }
  });

  exportLink.href = url.toString();
}

async function loadTeacherResults(url, updateHistory) {
  const currentResults = document.querySelector('[data-teacher-results]');
  if (!currentResults) return;

  const nextUrl = url.toString();
  if (nextUrl === window.location.href && updateHistory) {
    return;
  }

  activeTeacherFilterRequest?.abort();
  const controller = new AbortController();
  activeTeacherFilterRequest = controller;
  currentResults.setAttribute('aria-busy', 'true');

  try {
    const response = await fetch(nextUrl, {
      headers: {
        Accept: 'text/html',
        'X-Requested-With': 'fetch',
      },
      signal: controller.signal,
    });

    if (!response.ok) {
      throw new Error('Unable to load teacher results.');
    }

    const html = await response.text();
    const nextDocument = new DOMParser().parseFromString(html, 'text/html');
    const nextResults = nextDocument.querySelector('[data-teacher-results]');

    if (!nextResults) {
      throw new Error('Teacher results were not found.');
    }

    currentResults.replaceWith(nextResults);
    bindDropForms(nextResults);
    initTeacherFilters();

    if (updateHistory) {
      window.history.pushState({}, '', nextUrl);
    }
  } catch (error) {
    if (error.name === 'AbortError') return;
    window.location.assign(nextUrl);
  } finally {
    if (activeTeacherFilterRequest === controller) {
      activeTeacherFilterRequest = null;
    }

    document.querySelector('[data-teacher-results]')?.removeAttribute('aria-busy');
  }
}

function initImportPreview() {
  const form = document.querySelector('[data-import-form]');
  const fileInput = form?.querySelector('[data-import-file]');
  const modal = document.querySelector('[data-import-modal]');
  const preview = modal?.querySelector('[data-import-preview]');
  const body = modal?.querySelector('[data-import-preview-body]');
  const loading = modal?.querySelector('[data-import-loading]');
  const errorBox = modal?.querySelector('[data-import-error]');
  const summary = modal?.querySelector('[data-import-modal-summary]');
  const countBox = modal?.querySelector('[data-import-modal-count]');
  const submitButton = modal?.querySelector('[data-import-submit]');
  const resetButton = modal?.querySelector('[data-import-reset]');

  if (!form || !fileInput || !modal || !preview || !body || !loading || !errorBox || !summary || !countBox || !submitButton || !resetButton) {
    return;
  }

  const closeModal = () => {
    if (typeof modal.close === 'function') {
      modal.close();
    } else {
      modal.removeAttribute('open');
    }
  };

  modal.querySelectorAll('[data-import-modal-close]').forEach((button) => {
    button.addEventListener('click', closeModal);
  });

  resetButton.addEventListener('click', () => {
    fileInput.value = '';
    closeModal();
    window.requestAnimationFrame(() => {
      fileInput.focus({ preventScroll: false });
      fileInput.click();
    });
  });

  submitButton.addEventListener('click', () => {
    submitButton.disabled = true;
    form.requestSubmit();
  });

  fileInput.addEventListener('change', async () => {
    const file = fileInput.files?.[0];
    if (!file) return;

    openModal(modal);
    setImportModalState({ loading, errorBox, preview, submitButton, summary, countBox });

    try {
      const response = await fetch(form.dataset.importPreviewUrl, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'fetch',
        },
        body: new FormData(form),
      });
      const payload = await response.json();

      if (!response.ok || !payload.ok) {
        throw new Error(payload.message || 'ไม่สามารถอ่านไฟล์รายชื่อได้');
      }

      renderImportRows(body, payload.rows || []);
      loading.hidden = true;
      preview.hidden = false;
      submitButton.disabled = (payload.rows || []).length === 0;
      summary.textContent = `พบ ${payload.rows.length} รายชื่อ ตรวจเลขประจำตัว ชื่อ และห้องเรียนก่อนนำเข้า`;
      updateImportCount(countBox, payload.rows.length);
    } catch (error) {
      loading.hidden = true;
      errorBox.hidden = false;
      errorBox.textContent = error.message || 'ไม่สามารถอ่านไฟล์รายชื่อได้';
      summary.textContent = 'เลือกไฟล์รายชื่อใหม่อีกครั้ง';
      updateImportCount(countBox, 0);
      submitButton.disabled = true;
    }
  });
}

function openModal(modal) {
  if (typeof modal.showModal === 'function') {
    modal.showModal();
  } else {
    modal.setAttribute('open', 'open');
  }
}

function setImportModalState({ loading, errorBox, preview, submitButton, summary, countBox }) {
  loading.hidden = false;
  errorBox.hidden = true;
  errorBox.textContent = '';
  preview.hidden = true;
  preview.scrollTop = 0;
  submitButton.disabled = true;
  summary.textContent = 'กำลังตรวจรายชื่อในไฟล์ที่เลือก';
  updateImportCount(countBox, 0);
}

function renderImportRows(body, rows) {
  body.innerHTML = rows.map((row) => `
    <tr>
      <td><span class="import-row-index">${escapeHtml(row.row)}</span></td>
      <td><span class="soft-pill">${escapeHtml(row.studentNumber)}</span></td>
      <td>${escapeHtml(row.fullName)}</td>
      <td><span class="classroom-pill">${escapeHtml(row.classLevel)}/${escapeHtml(row.room)}</span></td>
      <td><span class="import-ready-chip">พร้อมนำเข้า</span></td>
    </tr>
  `).join('');
}

function updateImportCount(countBox, value) {
  const countNode = countBox.querySelector('strong');
  if (countNode) countNode.textContent = String(value);
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
