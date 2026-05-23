const moduleVersion = new URL(import.meta.url).search;

Promise.all([
  import(`./teacher-dashboard.js${moduleVersion}`),
  import(`./ui.js${moduleVersion}`),
]).then(([teacherDashboard, ui]) => {
  ui.initConfirmForms();
  ui.initFlashMessages();
  teacherDashboard.initTeacherDashboard();
});
