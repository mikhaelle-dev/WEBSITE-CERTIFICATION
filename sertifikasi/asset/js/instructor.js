document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.js-progress-bar').forEach(function (bar) {
    var value = Number(bar.dataset.progress || 0);
    if (Number.isNaN(value)) value = 0;
    bar.style.width = value + '%';
  });

  document.querySelectorAll('[data-confirm]').forEach(function (button) {
    button.addEventListener('click', function (event) {
      var message = button.dataset.confirm || 'Apakah Anda yakin?';
      if (!window.confirm(message)) {
        event.preventDefault();
      }
    });
  });
});
