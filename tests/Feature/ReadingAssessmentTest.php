<?php

namespace Tests\Feature;

use App\Models\Evaluation;
use App\Services\ReadingAssessment;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReadingAssessmentTest extends TestCase
{
    public static function examples(): array
    {
        return [
            'partial scores' => [65, 15, 4, 7, 76.92, 57.14, 67.03],
            'perfect scores' => [100, 0, 10, 10, 100.0, 100.0, 100.0],
            'zero scores' => [50, 50, 0, 5, 0.0, 0.0, 0.0],
        ];
    }

    #[DataProvider('examples')]
    public function test_authoritative_score_examples(int $words, int $miscues, int $correct, int $questions, float $oral, float $comprehension, float $final): void
    {
        $data = ReadingAssessment::calculate(array_replace($this->validInput(), [
            'miscues' => $miscues, 'correct_answers' => $correct, 'total_questions' => $questions,
            'total_words' => 9999, 'oral_reading_score' => 999, 'comprehension_percentage' => 999,
        ]), str_repeat('word ', $words));
        $this->assertSame($words, $data['total_words']);
        $this->assertSame($oral, $data['oral_reading_score']);
        $this->assertSame($comprehension, $data['comprehension_percentage']);
        $this->assertSame($final, ReadingAssessment::finalScore($data));
        $this->assertSame(101, $data['total_reading_seconds']);
    }

    public static function invalidInputs(): array
    {
        return [
            'too many miscues' => ['miscues', 66],
            'negative miscues' => ['miscues', -1],
            'fractional miscues' => ['miscues', 1.5],
            'too many answers' => ['correct_answers', 8],
            'negative answers' => ['correct_answers', -1],
            'zero questions' => ['total_questions', 0],
            'missing observation' => ['observation_level', null],
            'high observation' => ['observation_level', 5],
            'missing experience' => ['learner_experience', null],
            'high experience' => ['learner_experience', 6],
            'negative minutes' => ['reading_minutes', -1],
            'missing minutes' => ['reading_minutes', null],
            'seconds rollover' => ['reading_seconds', 60],
            'fractional seconds' => ['reading_seconds', 0.5],
            'excessive feedback' => ['feedback', str_repeat('a', 2001)],
            'overflow time' => ['reading_minutes', 71582789],
        ];
    }

    #[DataProvider('invalidInputs')]
    public function test_invalid_rubric_values_are_rejected(string $field, mixed $value): void
    {
        try {
            ReadingAssessment::calculate(array_replace($this->validInput(), [$field => $value]), str_repeat('word ', 65));
            $this->fail('Invalid '.$field.' was accepted.');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey($field, $error->errors());
        }
    }

    public function test_word_count_handles_html_unicode_whitespace_and_punctuation(): void
    {
        $passage = "<p>  Hello&nbsp;world! </p><p>don't well-known</p><div>naïve\tchild<br>reads</div> — ";
        $this->assertSame(7, ReadingAssessment::wordCount($passage));
        $this->assertSame(1, ReadingAssessment::wordCount('rea<em>ding</em>'));
        $this->assertSame(2, ReadingAssessment::wordCount("one\u{00A0}\u{2003}two"));
        $this->assertSame(0, ReadingAssessment::wordCount('<p>&nbsp; — !</p>'));
        $this->assertSame(0, ReadingAssessment::wordCount(null));
    }

    public function test_empty_passage_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        ReadingAssessment::calculate($this->validInput(), '<p> </p>');
    }

    public function test_observation_and_experience_do_not_change_scores(): void
    {
        $low = ReadingAssessment::calculate(array_replace($this->validInput(), [
            'observation_level' => 1, 'learner_experience' => 1,
        ]), str_repeat('word ', 65));
        $high = ReadingAssessment::calculate(array_replace($this->validInput(), [
            'observation_level' => 4, 'learner_experience' => 5,
        ]), str_repeat('word ', 65));
        $this->assertSame(ReadingAssessment::finalScore($low), ReadingAssessment::finalScore($high));
    }

    public function test_legacy_and_missing_values_do_not_become_zero(): void
    {
        $empty = new Evaluation;
        $this->assertNull($empty->final_score);
        $legacy = new Evaluation(['pronunciation_score' => 3, 'fluency_score' => 4,
            'accuracy_score' => 5, 'comprehension_score' => 2]);
        $this->assertTrue($legacy->is_legacy);
        $this->assertSame(70.0, $legacy->final_score);
        $current = new Evaluation(['oral_reading_score' => 0, 'comprehension_percentage' => 0]);
        $this->assertFalse($current->is_legacy);
        $this->assertSame(0.0, $current->final_score);
    }

    public static function noQuestionInputs(): array
    {
        return [
            'omitted' => [[]],
            'null' => [['correct_answers' => null, 'total_questions' => null]],
            'blank' => [['correct_answers' => '', 'total_questions' => '']],
            'whitespace' => [['correct_answers' => ' ', 'total_questions' => ' ']],
        ];
    }

    #[DataProvider('noQuestionInputs')]
    public function test_optional_comprehension_uses_oral_score_and_stores_nulls(array $optional): void
    {
        $input = $this->validInput();
        unset($input['correct_answers'], $input['total_questions']);
        $data = ReadingAssessment::calculate(array_replace($input, $optional), str_repeat('word ', 65));
        $this->assertNull($data['correct_answers']);
        $this->assertNull($data['total_questions']);
        $this->assertNull($data['comprehension_percentage']);
        $this->assertSame(76.92, ReadingAssessment::finalScore($data));
        $evaluation = new Evaluation($data);
        $this->assertFalse($evaluation->is_legacy);
        $this->assertSame(76.92, $evaluation->final_score);
    }

    public static function incompleteComprehension(): array
    {
        return [
            [null, 7, 'correct_answers'], [4, null, 'total_questions'],
            [0, null, 'total_questions'], ['', 7, 'correct_answers'],
            [4, '', 'total_questions'], [null, 0, 'total_questions'],
        ];
    }

    #[DataProvider('incompleteComprehension')]
    public function test_partial_comprehension_is_rejected(mixed $correct, mixed $questions, string $field): void
    {
        try {
            ReadingAssessment::calculate(array_replace($this->validInput(), [
                'correct_answers' => $correct, 'total_questions' => $questions,
            ]), str_repeat('word ', 65));
            $this->fail('Incomplete comprehension was accepted.');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey($field, $error->errors());
        }
    }

    public function test_zero_comprehension_counts_and_zero_oral_without_questions_stays_zero(): void
    {
        $data = ReadingAssessment::calculate(array_replace($this->validInput(), ['correct_answers' => 0]), str_repeat('word ', 65));
        $this->assertSame(0.0, $data['comprehension_percentage']);
        $this->assertSame(38.46, ReadingAssessment::finalScore($data));
        $this->assertSame(38.46, (new Evaluation($data))->final_score);
        $data = ReadingAssessment::calculate(array_replace($this->validInput(), [
            'miscues' => 65, 'correct_answers' => null, 'total_questions' => null,
        ]), str_repeat('word ', 65));
        $this->assertSame(0.0, ReadingAssessment::finalScore($data));
        $this->assertSame(0.0, (new Evaluation($data))->final_score);
    }

    private function validInput(): array
    {
        return [
            'reading_minutes' => 1, 'reading_seconds' => 41, 'miscues' => 15,
            'correct_answers' => 4, 'total_questions' => 7,
            'observation_level' => 3, 'learner_experience' => 4, 'feedback' => 'Keep reading.',
        ];
    }
}
