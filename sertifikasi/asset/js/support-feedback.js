(() => {
    const form = document.getElementById('feedbackForm');
    if (!form) {
        return;
    }

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        if (!form.reportValidity()) {
            return;
        }

        const email = form.dataset.email;
        const rating = form.querySelector('input[name="rating"]:checked');
        const type = form.querySelector('[name="type"]').value;
        const message = form.querySelector('[name="message"]').value.trim();
        if (!email || !rating || !message) {
            return;
        }

        const subject = encodeURIComponent('Feedback Pengguna');
        const body = encodeURIComponent(`Penilaian: ${rating.value}/5\nJenis masukan: ${type}\n\n${message}`);
        window.location.href = `mailto:${email}?subject=${subject}&body=${body}`;
    });
})();
