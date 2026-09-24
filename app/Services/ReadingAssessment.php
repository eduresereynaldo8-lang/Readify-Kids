<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ReadingAssessment
{
    public const OBSERVATIONS = [
        1 => 'Reads word by word or lower',
        2 => 'Reads words in chunks',
        3 => 'Reads fluently but not observing punctuation marks',
        4 => 'Reads fluently with proper expression',
    ];

    public static function passageText(?string $content): string
    {
        // Keep block boundaries as spaces; inline markup must not split a word.
        $text = preg_replace('/<\/?(?:p|div|br|li|h[1-6]|section|article|blockquote|tr)\b[^>]*>/iu', ' ', $content ?? '');
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/[\s\p{Z}]+/u', ' ', $text));
    }

    public static function wordCount(?string $content): int
    {
        $text = self::passageText($content);
        if ($text === '') {
            return 0;
        }

        // Unicode whitespace-delimited words; apostrophes/hyphens stay within words.
        // Ignore standalone punctuation, and count only passage content (not its title).
        return count(array_filter(preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY),
            fn ($word) => preg_match('/[\p{L}\p{N}]/u', $word)));
    }

    public static function calculate(array $input, ?string $content): array
    {
        $totalWords = self::wordCount($content);
        if ($totalWords === 0) {
            throw ValidationException::withMessages([
                'total_words' => 'This activity has no readable passage. Add passage text before evaluating.',
            ]);
        }

        // Blank or omitted comprehension fields mean this activity has no questions.
        foreach (['correct_answers', 'total_questions'] as $field) {
            if (!isset($input[$field]) || (is_string($input[$field]) && trim($input[$field]) === '')) {
                $input[$field] = null;
            }
        }

        $data = Validator::make($input, [
            // Upper bounds protect the unsigned integer storage range.
            'reading_minutes' => 'required|integer|min:0|max:71582788',
            'reading_seconds' => 'required|integer|min:0|max:59',
            'miscues' => 'required|integer|min:0|max:'.$totalWords,
            'correct_answers' => 'nullable|required_with:total_questions|integer|min:0|lte:total_questions',
            'total_questions' => 'nullable|required_with:correct_answers|integer|min:1|max:4294967295',
            'observation_level' => 'required|integer|between:1,4',
            'learner_experience' => 'required|integer|between:1,5',
            'feedback' => 'nullable|string|max:2000',
        ])->validate();

        $totalSeconds = (int) $data['reading_minutes'] * 60 + (int) $data['reading_seconds'];
        if ($totalSeconds > 4294967295) {
            throw ValidationException::withMessages(['reading_minutes' => 'Reading time is too large to store.']);
        }

        return array_merge($data, [
            'feedback' => $data['feedback'] ?? null,
            'total_reading_seconds' => $totalSeconds,
            'total_words' => $totalWords,
            'oral_reading_score' => round(($totalWords - $data['miscues']) / $totalWords * 100, 2),
            'comprehension_percentage' => $data['total_questions'] === null
                ? null : round($data['correct_answers'] / $data['total_questions'] * 100, 2),
        ]);
    }

    public static function finalScore(array $scores): float
    {
        // Without questions, use oral reading alone. A real comprehension zero
        // still participates in the average. Observation and experience are excluded.
        return $scores['comprehension_percentage'] === null
            ? round((float) $scores['oral_reading_score'], 2)
            : round(($scores['oral_reading_score'] + $scores['comprehension_percentage']) / 2, 2);
    }

    public static function formatTime(int|float|null $seconds): string
    {
        if ($seconds === null) {
            return '—';
        }
        $seconds = (int) round($seconds);

        return intdiv($seconds, 60).'m '.($seconds % 60).'s';
    }
}
