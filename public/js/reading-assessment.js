(() => {
    'use strict';
    const form = document.getElementById('reading-assessment-form');
    if (!form) return;
    const input = id => document.getElementById(id);
    const integer = id => {
        const field = input(id);
        if (field.value.trim() === '') return null;
        const value = Number(field.value);
        return Number.isSafeInteger(value) && value >= 0 ? value : null;
    };
    const rounded = value => Math.round((value + Number.EPSILON) * 100) / 100;
    const percent = value => value === null ? '—' : value.toFixed(2) + '%';
    function calculateOralScore() {
        const words = integer('total_words');
        const miscues = integer('miscues');
        return words > 0 && miscues !== null && miscues <= words
            ? rounded((words - miscues) / words * 100) : null;
    }
    function calculateComprehensionScore() {
        const correct = integer('correct_answers');
        const questions = integer('total_questions');
        return questions > 0 && correct !== null && correct <= questions
            ? rounded(correct / questions * 100) : null;
    }
    function updateSummary() {
        const oral = calculateOralScore();
        const comprehension = calculateComprehensionScore();
        const correctField = input('correct_answers');
        const questionsField = input('total_questions');
        const noQuestions = [correctField, questionsField].every(field => field.value.trim() === '' && !field.validity.badInput);
        correctField.required = questionsField.required = !noQuestions;
        const observation = form.querySelector('input[name="observation_level"]:checked');
        const experience = form.querySelector('input[name="learner_experience"]:checked');
        input('oral-score').textContent = input('summary-oral').textContent = percent(oral);
        input('comprehension-score').textContent = input('summary-comprehension').textContent = noQuestions ? 'N/A' : percent(comprehension);
        input('summary-observation').textContent = observation ? 'Level ' + observation.value : '—';
        input('summary-experience').textContent = experience ? experience.value + ' / 5' : '—';
        const finalScore = noQuestions ? oral
            : (oral === null || comprehension === null ? null : rounded((oral + comprehension) / 2));
        input('summary-final').textContent = percent(finalScore);
        const correct = integer('correct_answers');
        const questions = integer('total_questions');
        input('correct_answers').setCustomValidity(correct !== null && questions !== null && correct > questions
            ? 'Correct answers must not exceed total questions.' : '');
    }
    form.addEventListener('input', updateSummary);
    form.addEventListener('change', updateSummary);
    updateSummary();
})();
