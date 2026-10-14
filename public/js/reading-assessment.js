(() => {
    'use strict';
    const form = document.getElementById('reading-assessment-form');
    if (!form) return;
    const input = id => document.getElementById(id);
    const miscueFields = Array.from(form.querySelectorAll('[data-miscue-type]'));
    const legacyToggle = input('replace_legacy_miscues');
    const legacyTotal = Number(form.dataset.legacyMiscues || 0);
    const maxCount = 4294967295;
    const integer = id => {
        const field = input(id);
        if (field.validity.badInput || field.value.trim() === '') return null;
        const value = Number(field.value);
        return Number.isSafeInteger(value) && value >= 0 ? value : null;
    };
    const rounded = value => Math.round((value + Number.EPSILON) * 100) / 100;
    const percent = value => value === null ? 'N/A' : value.toFixed(2) + '%';
    function calculateMiscues() {
        let total = 0;
        let valid = true;
        for (const field of miscueFields) {
            const value = field.value.trim() === '' && !field.validity.badInput ? 0 : integer(field.id);
            const invalid = value === null || value > maxCount;
            field.setCustomValidity(invalid ? 'Enter a whole number from 0 to 4294967295.' : '');
            if (invalid) valid = false;
            else total += value;
        }
        if (total > maxCount) {
            miscueFields[0].setCustomValidity('Total miscues is too large to store.');
            valid = false;
        }
        const useLegacy = valid && total === 0 && legacyTotal > 0 && legacyToggle && !legacyToggle.checked;
        if (useLegacy) total = legacyTotal;
        input('miscues').value = valid ? String(total) : '';
        const legacyStatus = input('legacy-miscue-status');
        if (legacyStatus) {
            legacyStatus.textContent = useLegacy
                ? 'Keeping the saved total until you edit the breakdown or select the option above.'
                : 'Saving will replace the legacy total with the calculated breakdown.';
        }
        return valid ? total : null;
    }
    function calculateOralScore(miscues) {
        const words = integer('total_words');
        return words > 0 && miscues !== null
            ? rounded(Math.max(0, Math.min(100, (words - miscues) / words * 100))) : null;
    }
    function calculateComprehensionScore() {
        const correct = integer('correct_answers');
        const questions = integer('total_questions');
        return questions > 0 && correct !== null && correct <= questions
            ? rounded(correct / questions * 100) : null;
    }
    function updateSummary(event) {
        if (legacyToggle && event && miscueFields.includes(event.target)) {
            legacyToggle.checked = true;
        }
        const oral = calculateOralScore(calculateMiscues());
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
