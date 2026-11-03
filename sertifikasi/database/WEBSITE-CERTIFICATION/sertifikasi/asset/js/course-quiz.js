document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('quizForm');
    if (!form) {
        return;
    }

    const questions = Array.from(form.querySelectorAll('.quiz-question'));
    const jumpButtons = Array.from(form.querySelectorAll('.quiz-question-jump'));
    const previousButton = document.getElementById('previousQuestion');
    const nextButton = document.getElementById('nextQuestion');
    const submitButton = document.getElementById('submitQuiz');
    const message = document.getElementById('quizNavigationMessage');
    let currentIndex = 0;

    const hasAnswer = (index) => Boolean(questions[index].querySelector('input[type="radio"]:checked'));

    const renderQuestion = (index) => {
        currentIndex = index;
        questions.forEach((question, questionIndex) => {
            const active = questionIndex === currentIndex;
            question.classList.toggle('is-active', active);
            question.querySelectorAll('input[type="radio"]').forEach((input) => {
                if (active) {
                    input.setAttribute('required', 'required');
                } else {
                    input.removeAttribute('required');
                }
            });
        });

        jumpButtons.forEach((button, buttonIndex) => {
            const current = buttonIndex === currentIndex;
            button.classList.toggle('is-current', current);
            button.classList.toggle('is-answered', hasAnswer(buttonIndex));
            button.setAttribute('aria-current', current ? 'step' : 'false');
        });

        previousButton.hidden = currentIndex === 0;
        previousButton.disabled = currentIndex === 0;
        nextButton.hidden = currentIndex === questions.length - 1;
        submitButton.hidden = currentIndex !== questions.length - 1;
        message.textContent = '';
    };

    form.classList.add('is-enhanced');
    renderQuestion(0);

    form.addEventListener('change', (event) => {
        if (event.target.matches('input[type="radio"]')) {
            renderQuestion(currentIndex);
        }
    });

    jumpButtons.forEach((button) => {
        button.addEventListener('click', () => {
            renderQuestion(Number(button.dataset.questionIndex));
        });
    });

    previousButton.addEventListener('click', () => {
        if (currentIndex > 0) {
            renderQuestion(currentIndex - 1);
        }
    });

    nextButton.addEventListener('click', () => {
        if (!hasAnswer(currentIndex)) {
            message.textContent = 'Pilih salah satu jawaban sebelum melanjutkan.';
            questions[currentIndex].querySelector('input[type="radio"]').focus();
            return;
        }
        renderQuestion(currentIndex + 1);
    });

    form.addEventListener('submit', (event) => {
        const unansweredIndex = questions.findIndex((question, index) => !hasAnswer(index));
        if (unansweredIndex !== -1) {
            event.preventDefault();
            renderQuestion(unansweredIndex);
            message.textContent = 'Jawab soal ini sebelum mengirim kuis.';
            questions[unansweredIndex].querySelector('input[type="radio"]').focus();
        }
    });
});
