import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const source = readFileSync(new URL('../../public/js/reading-assessment.js', import.meta.url), 'utf8');
const types = ['mispronunciation', 'omission', 'substitution', 'insertion', 'repetition', 'transposition', 'reversal'];

function harness({ words = 65, counts = [], legacy = 0, replaceLegacy = false, correct = 4, questions = 7 } = {}) {
    const elements = new Map();
    const element = id => {
        if (!elements.has(id)) {
            elements.set(id, {
                id, value: '', textContent: '', checked: false, required: false,
                validity: { badInput: false }, validationMessage: '',
                setCustomValidity(message) { this.validationMessage = message; },
            });
        }
        return elements.get(id);
    };
    for (const [index, type] of types.entries()) element(type).value = String(counts[index] ?? 0);
    element('total_words').value = String(words);
    element('correct_answers').value = String(correct);
    element('total_questions').value = String(questions);
    const toggle = legacy > 0 ? element('replace_legacy_miscues') : null;
    if (toggle) toggle.checked = replaceLegacy;
    const handlers = new Map();
    const form = {
        dataset: { legacyMiscues: String(legacy) },
        querySelectorAll: selector => selector === '[data-miscue-type]' ? types.map(element) : [],
        querySelector: () => null,
        addEventListener: (name, handler) => handlers.set(name, handler),
    };
    const document = {
        getElementById: id => id === 'reading-assessment-form' ? form
            : id === 'replace_legacy_miscues' ? toggle
            : id === 'legacy-miscue-status' && !legacy ? null : element(id),
    };
    vm.runInNewContext(source, { document });
    return {
        element, toggle,
        change(id, value, type = 'input') {
            element(id).value = String(value);
            handlers.get(type)({ target: element(id) });
        },
        emit(id, type = 'change') { handlers.get(type)({ target: element(id) }); },
        total: () => element('miscues').value,
        oral: () => element('oral-score').textContent,
        comprehension: () => element('comprehension-score').textContent,
        final: () => element('summary-final').textContent,
    };
}

for (const [name, words, counts, total, expected] of [
    ['100 words and 10 miscues', 100, [2, 3, 4, 1, 0, 0, 0], '10', '90.00%'],
    ['65 words and 15 miscues', 65, [4, 3, 3, 2, 1, 1, 1], '15', '76.92%'],
    ['no miscues', 13, [0, 0, 0, 0, 0, 0, 0], '0', '100.00%'],
    ['miscues exceed passage length', 10, [2, 3, 4, 1, 1, 1, 0], '12', '0.00%'],
    ['zero total words', 0, [0, 0, 0, 0, 0, 0, 0], '0', 'N/A'],
]) {
    test('live calculation: ' + name, () => {
        const h = harness({ words, counts });
        assert.equal(h.total(), total);
        assert.equal(h.oral(), expected);
        assert.equal(h.element('summary-oral').textContent, expected);
        assert.equal(h.comprehension(), '57.14%');
        if (words === 0) assert.equal(h.final(), 'N/A');
    });
}

test('each miscue input and passage word count update the total and score immediately', () => {
    const h = harness({ words: 100, correct: '', questions: '' });
    for (const [index, field] of types.entries()) {
        h.change(field, 1, index % 2 === 0 ? 'input' : 'change');
        assert.equal(h.total(), String(index + 1));
        assert.equal(h.oral(), (99 - index).toFixed(2) + '%');
    }
    h.change('total_words', 10);
    assert.equal(h.oral(), '30.00%');
    assert.equal(h.final(), '30.00%');
    h.change('total_words', 0);
    assert.equal(h.oral(), 'N/A');
    h.change('total_words', 100);
    assert.equal(h.oral(), '93.00%');
    h.change('miscues', 999);
    assert.equal(h.total(), '7');
});

test('blank fields count as zero and a substitution change updates the score', () => {
    const h = harness({ words: 100, counts: [2, '', 2, '', 0, '', 0] });
    assert.equal(h.total(), '4');
    h.change('substitution', 3);
    assert.equal(h.total(), '5');
    assert.equal(h.oral(), '95.00%');
    h.change('mispronunciation', '');
    assert.equal(h.total(), '3');
    assert.equal(h.oral(), '97.00%');
});

for (const value of [-1, 1.5, 'invalid', 4294967296]) {
    test('invalid miscue input is blocked: ' + value, () => {
        const h = harness();
        h.change('reversal', value);
        assert.equal(h.total(), '');
        assert.equal(h.oral(), 'N/A');
        assert.ok(h.element('reversal').validationMessage);
        h.change('reversal', 1);
        assert.equal(h.total(), '1');
        assert.equal(h.element('reversal').validationMessage, '');
    });
}

test('browser badInput is not mistaken for a blank zero', () => {
    const h = harness();
    h.element('omission').validity.badInput = true;
    h.change('omission', '');
    assert.equal(h.oral(), 'N/A');
    assert.ok(h.element('omission').validationMessage);
});

test('sum overflow blocks submission and recovers after correction', () => {
    const h = harness({ counts: [4294967295, 1] });
    assert.equal(h.oral(), 'N/A');
    assert.match(h.element('mispronunciation').validationMessage, /Total miscues/);
    h.change('omission', 0);
    assert.equal(h.total(), '4294967295');
    assert.equal(h.oral(), '0.00%');
    assert.equal(h.element('mispronunciation').validationMessage, '');
});

test('comprehension stays optional and a real zero still participates in the final score', () => {
    const h = harness({ counts: [15], correct: '', questions: '' });
    assert.equal(h.comprehension(), 'N/A');
    assert.equal(h.final(), '76.92%');
    assert.equal(h.element('correct_answers').required, false);
    h.change('correct_answers', 0);
    assert.equal(h.element('total_questions').required, true);
    assert.equal(h.final(), 'N/A');
    h.change('total_questions', 7);
    assert.equal(h.comprehension(), '0.00%');
    assert.equal(h.final(), '38.46%');
    h.change('correct_answers', 8);
    assert.equal(h.comprehension(), 'N/A');
    assert.ok(h.element('correct_answers').validationMessage);
});

test('legacy total survives initialization and unrelated edits until the breakdown changes', () => {
    const h = harness({ legacy: 15 });
    assert.equal(h.total(), '15');
    assert.equal(h.oral(), '76.92%');
    h.change('feedback', 'Updated notes');
    h.change('correct_answers', 5);
    assert.equal(h.total(), '15');
    assert.equal(h.toggle.checked, false);
    h.change('substitution', 3);
    assert.equal(h.toggle.checked, true);
    assert.equal(h.total(), '3');
    h.change('substitution', 0);
    assert.equal(h.total(), '0');
    assert.equal(h.oral(), '100.00%');
});

test('an explicit legacy replacement with zeros persists across validation retries', () => {
    const h = harness({ legacy: 15, replaceLegacy: true });
    assert.equal(h.total(), '0');
    assert.equal(h.oral(), '100.00%');
    h.toggle.checked = false;
    h.emit('replace_legacy_miscues');
    assert.equal(h.total(), '15');
    h.toggle.checked = true;
    h.emit('replace_legacy_miscues');
    assert.equal(h.total(), '0');
});

test('old input containing a positive breakdown takes precedence over a legacy total', () => {
    const h = harness({ legacy: 15, counts: [2, 1, 3, 1, 0, 1, 0] });
    assert.equal(h.total(), '8');
    assert.equal(h.oral(), '87.69%');
});

test('the script safely exits on pages without the evaluation form', () => {
    vm.runInNewContext(source, { document: { getElementById: () => null } });
});
