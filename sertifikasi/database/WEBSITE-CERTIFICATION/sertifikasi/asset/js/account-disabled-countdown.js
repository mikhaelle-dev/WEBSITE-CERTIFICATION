(() => {
    const countdown = document.getElementById('disabled-countdown');
    if (!countdown) {
        return;
    }

    let remaining = Number(countdown.dataset.remainingSeconds);
    const update = () => {
        if (remaining <= 0) {
            countdown.textContent = 'segera';
            return;
        }

        const minutes = Math.floor(remaining / 60);
        const seconds = remaining % 60;
        countdown.textContent = minutes > 0
            ? `${minutes} menit ${seconds} detik`
            : `${seconds} detik`;
        remaining -= 1;
        window.setTimeout(update, 1000);
    };

    update();
})();
