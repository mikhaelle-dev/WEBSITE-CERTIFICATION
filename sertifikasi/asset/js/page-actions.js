document.addEventListener('click', async (event) => {
    const target = event.target;
    if (!(target instanceof Element)) {
        return;
    }

    const backTrigger = target.closest('[data-history-back]');
    if (backTrigger) {
        const previousUrl = document.referrer;
        if (previousUrl && new URL(previousUrl).origin === window.location.origin && window.history.length > 1) {
            event.preventDefault();
            window.history.back();
        }
        return;
    }

    const passwordToggle = target.closest('[data-toggle-password]');
    if (passwordToggle) {
        const input = document.getElementById(passwordToggle.getAttribute('aria-controls'));
        if (!(input instanceof HTMLInputElement)) {
            return;
        }

        const visible = input.type === 'password';
        input.type = visible ? 'text' : 'password';
        passwordToggle.setAttribute('aria-pressed', String(visible));
        passwordToggle.setAttribute('aria-label', visible ? 'Sembunyikan password' : 'Tampilkan password');
        const icon = passwordToggle.firstElementChild;
        if (icon) {
            icon.className = visible ? 'bi bi-eye-slash' : 'bi bi-eye';
        }
        return;
    }

    const detailsTrigger = target.closest('[data-open-details]');
    if (detailsTrigger) {
        const details = document.getElementById(detailsTrigger.dataset.openDetails);
        if (details instanceof HTMLDetailsElement) {
            details.open = true;
        }
        return;
    }

    const copyTrigger = target.closest('[data-copy-target]');
    if (copyTrigger) {
        const input = document.getElementById(copyTrigger.dataset.copyTarget);
        if (!(input instanceof HTMLInputElement)) {
            return;
        }

        try {
            await navigator.clipboard.writeText(input.value);
            copyTrigger.textContent = 'Tersalin';
        } catch (error) {
            console.error('Tidak dapat menyalin kata sandi ke clipboard.', error);
            window.alert('Kata sandi tidak dapat disalin. Silakan salin secara manual.');
        }
        return;
    }

    if (target.closest('[data-print-page]')) {
        window.print();
    }
});

document.addEventListener('change', (event) => {
    const target = event.target;
    if (target instanceof HTMLSelectElement && target.matches('[data-submit-on-change]')) {
        target.form?.requestSubmit();
    }
});

document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    const message = form.dataset.confirm;
    if (message && !window.confirm(message)) {
        event.preventDefault();
    }
});

document.querySelectorAll('[data-progress]').forEach((progress) => {
    const value = Number(progress.dataset.progress);
    if (Number.isFinite(value)) {
        progress.style.width = `${Math.min(100, Math.max(0, value))}%`;
    }
});
