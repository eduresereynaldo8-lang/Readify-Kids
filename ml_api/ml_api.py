"""
ml_api.py
==========
Readify Kids — ML Scoring API
Model: Local Whisper Small (runs on your PC)
No API key needed — zero cost!
"""

from flask import Flask, request, jsonify
from flask_cors import CORS
from dotenv import load_dotenv
import re
import unicodedata
import os
import subprocess
import sys

load_dotenv()

app = Flask(__name__)
CORS(app)

# ── Add local ffmpeg to PATH ───────────────────────────────────
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
os.environ['PATH'] = BASE_DIR + os.pathsep + os.environ.get('PATH', '')

# ── Load Whisper Small at startup ──────────────────────────────
print('Loading Whisper Small model...')
import whisper
model = whisper.load_model('small')
print('Whisper Small loaded successfully!')

# ─────────────────────────────────────────────────────────────
def convert_to_wav(input_path: str) -> str:
    """Convert any audio to 16kHz mono WAV for Whisper."""
    output_path = input_path.rsplit('.', 1)[0] + '_converted.wav'
    ffmpeg_path = os.path.join(BASE_DIR, 'ffmpeg.exe')
    if not os.path.exists(ffmpeg_path):
        ffmpeg_path = 'ffmpeg'

    try:
        result = subprocess.run([
            ffmpeg_path,
            '-i', input_path,
            '-ar', '16000',
            '-ac', '1',
            '-y',
            output_path
        ], capture_output=True, text=True)

        if result.returncode == 0 and os.path.exists(output_path):
            print(f'Converted : {output_path}')
            return output_path
        else:
            print(f'FFmpeg error: {result.stderr}')
            return input_path
    except Exception as e:
        print(f'FFmpeg exception: {e}')
        return input_path


def normalize_words(text: str) -> list[str]:
    """Ignore case/punctuation; retain order, repeats and internal apostrophes."""
    text = unicodedata.normalize('NFKC', text).lower().strip()
    text = text.replace('’', "'").replace('‘', "'")
    # Hyphens and other punctuation separate words; punctuation is never a token.
    return re.findall(r"[^\W_]+(?:'[^\W_]+)*", text)


def compute_oral_reading(transcript: str, expected: str) -> dict:
    """Minimum word edit distance with deterministic alignment/backtracking.

    Equal-cost paths prefer matches, substitutions, deletions, then insertions.
    Every repeated word occupies its own position in the alignment.
    """
    expected_words = normalize_words(expected)
    transcript_words = normalize_words(transcript)
    n, m = len(expected_words), len(transcript_words)
    if not n:
        raise ValueError('Expected text must contain at least one word.')

    distance = [list(range(m + 1))]
    for i in range(1, n + 1):
        row = [i] + [0] * m
        for j in range(1, m + 1):
            row[j] = min(
                distance[i - 1][j - 1] + (expected_words[i - 1] != transcript_words[j - 1]),
                distance[i - 1][j] + 1,
                row[j - 1] + 1,
            )
        distance.append(row)

    substitutions = deletions = insertions = 0
    correct, missed, wrong = [], [], []
    i, j = n, m
    while i or j:
        if i and j and expected_words[i - 1] == transcript_words[j - 1]:
            correct.append(expected_words[i - 1])
            i, j = i - 1, j - 1
        elif i and j and distance[i][j] == distance[i - 1][j - 1] + 1:
            substitutions += 1
            missed.append(expected_words[i - 1])
            wrong.append(transcript_words[j - 1])
            i, j = i - 1, j - 1
        elif i and distance[i][j] == distance[i - 1][j] + 1:
            deletions += 1
            missed.append(expected_words[i - 1])
            i -= 1
        else:
            insertions += 1
            wrong.append(transcript_words[j - 1])
            j -= 1

    miscues = substitutions + deletions + insertions
    oral_score = round(max(0.0, min(100.0, ((n - miscues) / n) * 100)), 2)
    return {
        'score': oral_score,
        'oral_reading_score': oral_score,
        'expected_word_count': n,
        'miscues': miscues,
        'substitutions': substitutions,
        'deletions': deletions,
        'insertions': insertions,
        'correct_words': len(correct),
        # Preserve the existing breakdown shape, now derived from alignment.
        'word_breakdown': {
            'correct': correct[::-1],
            'missed': missed[::-1],
            'wrong': wrong[::-1],
            'accuracy': oral_score,
        },
    }


def compute_score(transcript: str, expected: str) -> float:
    """Backward-compatible entry point for the oral reading score."""
    return compute_oral_reading(transcript, expected)['score']


def compute_word_accuracy(transcript: str, expected: str) -> dict:
    """Backward-compatible breakdown derived from ordered word alignment."""
    return compute_oral_reading(transcript, expected)['word_breakdown']


# ─────────────────────────────────────────────────────────────
#  ROUTES
# ─────────────────────────────────────────────────────────────
@app.route('/health', methods=['GET'])
def health():
    return jsonify({
        'status'  : 'ok',
        'model'   : 'local-whisper-small',
        'message' : 'Readify Kids ML API is running!',
    })


@app.route('/score', methods=['POST'])
def score():
    data = request.get_json(force=True)

    if not data:
        return jsonify({'error': 'No JSON data received'}), 400

    recording_path = data.get('recording_path', '').strip()
    expected_text  = data.get('expected_text',  '').strip()

    print(f'\n{"="*50}')
    print(f'Recording  : {recording_path}')
    print(f'Expected   : {expected_text}')
    print(f'File exists: {os.path.exists(recording_path)}')

    if not recording_path:
        return jsonify({'error': 'recording_path is required'}), 400
    if not os.path.exists(recording_path):
        return jsonify({'error': f'File not found: {recording_path}'}), 404
    if not normalize_words(expected_text):
        return jsonify({'error': 'expected_text must contain at least one word',
                        'score': None, 'oral_reading_score': None}), 400

    try:
        # Convert to WAV
        wav_path = convert_to_wav(recording_path)
        print(f'Processing : {wav_path}')

        # Transcribe with local Whisper Small
        result     = model.transcribe(
            wav_path,
            language='en',
            fp16=False,
            task='transcribe'
        )
        transcript = result['text'].strip()
        print(f'Transcript : {transcript}')

        # Cleanup converted WAV
        if wav_path != recording_path and os.path.exists(wav_path):
            os.remove(wav_path)

        # Whisper transcribes only; ordered word alignment supplies the score.
        reading_score = compute_oral_reading(transcript, expected_text)
        score_val = reading_score['score']

        print(f'Score      : {score_val}%')
        print(f'{"="*50}\n')

        return jsonify({
            **reading_score,
            'transcript'    : transcript,
            'expected'      : expected_text,
            'model'         : 'local-whisper-small',
            'message'       : 'Scored successfully',
        })

    except Exception as e:
        print(f'Error: {str(e)}')
        import traceback
        traceback.print_exc()
        return jsonify({
            'error'  : str(e),
            'score'  : None,
            'oral_reading_score': None,
            'message': 'Transcription failed',
        }), 500


@app.route('/transcribe-only', methods=['POST'])
def transcribe_only():
    data = request.get_json(force=True)
    if not data:
        return jsonify({'error': 'No data received'}), 400

    recording_path = data.get('recording_path', '').strip()
    if not recording_path or not os.path.exists(recording_path):
        return jsonify({'error': 'Recording file not found'}), 404

    try:
        wav_path   = convert_to_wav(recording_path)
        result     = model.transcribe(wav_path, language='en', fp16=False)
        transcript = result['text'].strip()
        if wav_path != recording_path and os.path.exists(wav_path):
            os.remove(wav_path)
        return jsonify({
            'transcript': transcript,
            'model'     : 'local-whisper-small',
        })
    except Exception as e:
        return jsonify({'error': str(e)}), 500


# ─────────────────────────────────────────────────────────────
if __name__ == '__main__':
    port  = int(os.environ.get('PORT', 5000))
    print('=' * 50)
    print('Readify Kids ML API — Local Server Mode')
    print('Model  : Whisper Small (local, zero cost)')
    print(f'Port   : {port}')
    print('=' * 50)
    print('Endpoints:')
    print('  GET  /health')
    print('  POST /score')
    print('  POST /transcribe-only')
    print('=' * 50)
    app.run(host='0.0.0.0', port=port, debug=False)