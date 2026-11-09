document.addEventListener('DOMContentLoaded', function () {
  var storageKey = 'dashboard-filter-scroll';
  var savedPosition = null;
  try {
    savedPosition = JSON.parse(window.sessionStorage.getItem(storageKey) || 'null');
    window.sessionStorage.removeItem(storageKey);
  } catch (error) {
    return;
  }

  if (savedPosition && savedPosition.path === window.location.pathname) {
    window.requestAnimationFrame(function () {
      window.requestAnimationFrame(function () {
        window.scrollTo(0, savedPosition.y);
      });
    });
  }

  function rememberPosition() {
    try {
      window.sessionStorage.setItem(storageKey, JSON.stringify({
        path: window.location.pathname,
        y: window.scrollY
      }));
    } catch (error) {
      // Scroll restoration is an enhancement; navigation should still proceed.
    }
  }

  document.querySelectorAll('[data-preserve-dashboard-scroll]').forEach(function (element) {
    if (element.tagName === 'FORM') {
      element.addEventListener('submit', rememberPosition);
    } else {
      element.addEventListener('click', rememberPosition);
    }
  });
});
