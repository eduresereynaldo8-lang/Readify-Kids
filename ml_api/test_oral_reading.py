"""Deterministic oral-reading tests; Whisper/FFmpeg are mocked, no model download."""
import importlib.util
import io
from pathlib import Path
import sys
import unittest
from unittest.mock import Mock, patch

API_PATH = Path(__file__).with_name('ml_api.py')
whisper = Mock()
with patch.dict(sys.modules, {'whisper': whisper}), patch('sys.stdout', new_callable=io.StringIO):
    spec = importlib.util.spec_from_file_location('battle_scoring_api', API_PATH)
    api = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(api)


class OralReadingTests(unittest.TestCase):
    def test_requested_alignment_cases(self):
        cases = [
            ('the little cat sleeps', 'the little cat sleeps', (0, 0, 0), 100),
            ('the little cat sleeps', 'the little dog sleeps', (1, 0, 0), 75),
            ('the little cat sleeps', 'the little sleeps', (0, 1, 0), 75),
            ('the little cat sleeps', 'the very little cat sleeps', (0, 0, 1), 75),
            ('the cat and the dog', 'the dog and the cat', (2, 0, 0), 60),
        ]
        for expected, transcript, counts, score in cases:
            with self.subTest(transcript=transcript):
                result = api.compute_oral_reading(transcript, expected)
                self.assertEqual(counts, tuple(result[k] for k in ('substitutions', 'deletions', 'insertions')))
                self.assertEqual(sum(counts), result['miscues'])
                self.assertEqual(score, result['score'])
                self.assertEqual(score, result['oral_reading_score'])

    def test_repeated_words_are_counted_individually(self):
        result = api.compute_oral_reading('go home', 'go go home')
        self.assertEqual(1, result['deletions'])
        self.assertEqual(66.67, result['score'])
        self.assertEqual(['go', 'home'], result['word_breakdown']['correct'])
        result = api.compute_oral_reading('go go home', 'go home')
        self.assertEqual(1, result['insertions'])
        self.assertEqual(50, result['score'])

    def test_normalization(self):
        self.assertEqual(100, api.compute_score("  The\tLITTLE, cat... sleeps! ", 'the little cat sleeps'))
        self.assertEqual(100, api.compute_score("don't stop", 'Don’t stop!'))
        self.assertEqual(['well', 'known', 'cat'], api.normalize_words('well-known—cat'))
        self.assertEqual(100, api.compute_score('café', 'CAFE\u0301'))

    def test_silence_and_many_insertions_are_real_zero_scores(self):
        silent = api.compute_oral_reading('', 'the little cat sleeps')
        self.assertEqual(4, silent['deletions'])
        self.assertEqual(0, silent['score'])
        extras = api.compute_oral_reading('cat a b c d e', 'cat')
        self.assertEqual(5, extras['insertions'])
        self.assertEqual(0, extras['score'])

    def test_empty_expected_is_unscorable(self):
        for expected in ('', '  ', '...!?'):
            with self.subTest(expected=expected), self.assertRaises(ValueError):
                api.compute_score('cat', expected)

    def test_score_endpoint_contract_and_rounding(self):
        with patch.object(api.model, 'transcribe', return_value={'text': 'one two cat four five six'}), \
             patch.object(api, 'convert_to_wav', side_effect=lambda path: path), \
             patch('sys.stdout', new_callable=io.StringIO):
            response = api.app.test_client().post('/score', json={
                'recording_path': str(API_PATH), 'expected_text': 'one two three four five six seven'})
        self.assertEqual(200, response.status_code)
        data = response.get_json()
        self.assertEqual(71.43, data['score'])
        self.assertEqual(71.43, data['oral_reading_score'])
        self.assertEqual((7, 2, 1, 1, 0), tuple(data[k] for k in (
            'expected_word_count', 'miscues', 'substitutions', 'deletions', 'insertions')))
        self.assertEqual('one two cat four five six', data['transcript'])
        self.assertEqual('one two three four five six seven', data['expected'])
        whisper.load_model.assert_called_once_with('small')

    def test_transcription_failure_returns_null(self):
        with patch.object(api.model, 'transcribe', side_effect=RuntimeError('test failure')), \
             patch.object(api, 'convert_to_wav', side_effect=lambda path: path), \
             patch('sys.stdout', new_callable=io.StringIO), patch('sys.stderr', new_callable=io.StringIO):
            response = api.app.test_client().post('/score', json={
                'recording_path': str(API_PATH), 'expected_text': 'cat'})
        self.assertEqual(500, response.status_code)
        self.assertIsNone(response.get_json()['score'])
        self.assertIsNone(response.get_json()['oral_reading_score'])
        self.assertNotIn('miscues', response.get_json())

    def test_punctuation_only_expected_is_rejected_before_transcription(self):
        with patch.object(api.model, 'transcribe') as transcribe, patch('sys.stdout', new_callable=io.StringIO):
            response = api.app.test_client().post('/score', json={
                'recording_path': str(API_PATH), 'expected_text': '...!'})
        self.assertEqual(400, response.status_code)
        self.assertIsNone(response.get_json()['score'])
        transcribe.assert_not_called()


if __name__ == '__main__':
    unittest.main()
