const assetVersion = new URL(import.meta.url).search;
const moduleVersion = assetVersion;

Promise.all([
  import(`./leaderboard.js${moduleVersion}`),
  import(`./teacher-dashboard.js${moduleVersion}`),
  import(`./ui.js${moduleVersion}`),
]).then(([leaderboard, teacherDashboard, ui]) => {
  ui.initConfirmForms();
  ui.initFlashMessages();
  teacherDashboard.initTeacherDashboard();
  leaderboard.initLeaderboard();
});
