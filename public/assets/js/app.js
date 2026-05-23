const assetVersion = new URL(import.meta.url).search;

Promise.all([
  import(`./leaderboard.js${assetVersion}`),
  import(`./teacher-dashboard.js${assetVersion}`),
  import(`./ui.js${assetVersion}`),
]).then(([leaderboard, teacherDashboard, ui]) => {
  ui.initConfirmForms();
  ui.initFlashMessages();
  teacherDashboard.initTeacherDashboard();
  leaderboard.initLeaderboard();
});
