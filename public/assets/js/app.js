const assetVersion = new URL(import.meta.url).search;

Promise.all([
  import(`./teacher-dashboard.js${assetVersion}`),
  import(`./ui.js${assetVersion}`),
]).then(([teacherDashboard, ui]) => {
  ui.initConfirmForms();
  ui.initFlashMessages();
  teacherDashboard.initTeacherDashboard();
});
