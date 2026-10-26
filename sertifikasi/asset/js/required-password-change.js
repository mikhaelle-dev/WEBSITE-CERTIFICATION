document.addEventListener('DOMContentLoaded', function () {
    var modalElement = document.getElementById('requiredPasswordChangeModal');
    if (modalElement) {
        bootstrap.Modal.getOrCreateInstance(modalElement).show();
    }
});
