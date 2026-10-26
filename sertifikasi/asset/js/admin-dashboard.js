(() => {
    const panel = document.querySelector('.admin-users-panel');
    const modeButton = document.getElementById('toggleBulkTrashMode');
    const cancelButton = document.getElementById('cancelBulkTrashMode');
    const form = document.getElementById('bulkTrashForm');
    const submitButton = document.getElementById('bulkTrashButton');
    const status = document.getElementById('bulkTrashSelectionStatus');
    const limitNotice = document.getElementById('bulkTrashLimitNotice');
    const checkboxes = Array.from(document.querySelectorAll('.admin-account-select:not(:disabled)'));

    if (panel && modeButton && cancelButton && form && submitButton && status && limitNotice) {
        const updateSelection = () => {
            const count = checkboxes.filter((checkbox) => checkbox.checked).length;
            status.textContent = `${count} dari 5 akun dipilih`;
            submitButton.disabled = count === 0;
        };

        const setSelectionMode = (enabled) => {
            panel.classList.toggle('is-selecting', enabled);
            form.hidden = !enabled;
            modeButton.setAttribute('aria-expanded', String(enabled));
            modeButton.innerHTML = enabled
                ? '<i class="bi bi-x-lg me-1"></i>Batal pilih'
                : '<i class="bi bi-ui-checks me-1"></i>Pilih beberapa';
            if (!enabled) {
                checkboxes.forEach((checkbox) => { checkbox.checked = false; });
                limitNotice.hidden = true;
            }
            updateSelection();
        };

        modeButton.addEventListener('click', () => {
            setSelectionMode(modeButton.getAttribute('aria-expanded') !== 'true');
        });
        cancelButton.addEventListener('click', () => setSelectionMode(false));

        checkboxes.forEach((checkbox) => {
            checkbox.addEventListener('change', () => {
                const count = checkboxes.filter((item) => item.checked).length;
                if (count > 5) {
                    checkbox.checked = false;
                    limitNotice.hidden = false;
                } else {
                    limitNotice.hidden = true;
                }
                updateSelection();
            });
        });

        form.addEventListener('submit', (event) => {
            const selectedCount = checkboxes.filter((checkbox) => checkbox.checked).length;
            if (selectedCount < 1 || selectedCount > 5) {
                event.preventDefault();
                return;
            }
            const noun = selectedCount === 1 ? 'akun ini' : `${selectedCount} akun ini`;
            if (!window.confirm(`Pindahkan ${noun} ke sampah? Akun akan langsung dinonaktifkan dan dihapus permanen dalam 5 menit jika tidak dipulihkan.`)) {
                event.preventDefault();
            }
        });
    }

    document.querySelectorAll('[data-dialog-target]').forEach((trigger) => {
        const dialog = document.getElementById(trigger.dataset.dialogTarget);
        if (!dialog || typeof dialog.showModal !== 'function') {
            return;
        }

        trigger.addEventListener('click', () => dialog.showModal());
        dialog.querySelector('.admin-dialog-close')?.addEventListener('click', () => dialog.close());
        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) {
                dialog.close();
            }
        });
    });

    const countdownItems = Array.from(document.querySelectorAll('.admin-trash-countdown[data-expire-at]'));
    const expirations = Array.from(document.querySelectorAll('[data-expire-at]'))
        .map((item) => Number(item.dataset.expireAt));
    const cleanupForm = document.getElementById('purgeExpiredTrashForm');

    if (!expirations.length || !cleanupForm) {
        return;
    }

    const updateCountdowns = () => {
        const nextExpiry = Math.min(...expirations);
        const now = Date.now() / 1000;
        countdownItems.forEach((item) => {
            const seconds = Number(item.dataset.expireAt) - now;
            const remaining = Math.ceil(seconds);
            item.textContent = seconds <= 0
                ? 'Menunggu penghapusan...'
                : `Dihapus dalam ${Math.floor(remaining / 60)}:${String(remaining % 60).padStart(2, '0')}`;
        });
        const secondsUntilExpiry = nextExpiry - now;
        if (secondsUntilExpiry <= 0) {
            cleanupForm.requestSubmit();
        } else {
            window.setTimeout(updateCountdowns, Math.min(1000, secondsUntilExpiry * 1000));
        }
    };

    updateCountdowns();
})();
