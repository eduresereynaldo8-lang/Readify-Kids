from flask import Flask, request, jsonify
import whisper
import difflib
import os
import tempfile

app = Flask(__name__)

# Load Whisper small model once at startup
print("Loading Whisper model...")
model = whisper.load_model("small")
print("Whisper model loaded successfully!")

def compute_score(transcript: str, expected: str) -> float:
    """
    Compare the Whisper transcript against the expected text.
    Returns a score from 0 to 100.
    """
    # Normalize both strings — lowercase, strip whitespace
    transcript_clean = transcript.lower().strip()
    expected_clean   = expected.lower().strip()

    if not expected_clean:
        return 0.0

    # Use SequenceMatcher for similarity ratio
    similarity = difflib.SequenceMatcher(
        None,
        expected_clean,
        transcript_clean
    ).ratio()

    # Convert to 0-100 score
    score = round(similarity * 100, 2)
    return score


def compute_word_accuracy(transcript: str, expected: str) -> dict:
    """
    Break down accuracy word by word.
    Returns which words were correct, missed, or wrong.
    """
    expected_words   = expected.lower().strip().split()
    transcript_words = transcript.lower().strip().split()

    correct  = []
    missed   = []
    wrong    = []

    for word in expected_words:
        if word in transcript_words:
            correct.append(word)
        else:
            missed.append(word)

    for word in transcript_words:
        if word not in expected_words:
            wrong.append(word)

    return {
        "correct" : correct,
        "missed"  : missed,
        "wrong"   : wrong,
        "accuracy": round(len(correct) / len(expected_words) * 100, 2) if expected_words else 0
    }


@app.route('/health', methods=['GET'])
def health():
    """Health check endpoint — Laravel pings this to check if Flask is running."""
    return jsonify({
        "status" : "ok",
        "model"  : "whisper-small",
        "message": "Readify Kids ML API is running!"
    })


@app.route('/score', methods=['POST'])
def score():
    """
    Main scoring endpoint.
    Receives: recording_path (str) + expected_text (str)
    Returns:  score (float), transcript (str), word breakdown
    """
    data = request.get_json()

    if not data:
        return jsonify({"error": "No data received"}), 400

    recording_path = data.get('recording_path')
    expected_text  = data.get('expected_text', '')

    # Validate inputs
    if not recording_path:
        return jsonify({"error": "recording_path is required"}), 400

    if not os.path.exists(recording_path):
        return jsonify({"error": f"Recording file not found: {recording_path}"}), 404

    if not expected_text:
        return jsonify({"error": "expected_text is required"}), 400

    try:
        print(f"Transcribing: {recording_path}")
        print(f"Expected: {expected_text}")

        # Transcribe audio using Whisper
        result = model.transcribe(
            recording_path,
            language="en",       # force English
            fp16=False,          # use fp32 for CPU compatibility
            task="transcribe"
        )

        transcript = result["text"].strip()
        print(f"Transcript: {transcript}")

        # Compute overall similarity score
        score = compute_score(transcript, expected_text)

        # Compute word-level accuracy
        word_breakdown = compute_word_accuracy(transcript, expected_text)

        print(f"Score: {score}")

        return jsonify({
            "score"          : score,
            "transcript"     : transcript,
            "expected"       : expected_text,
            "word_breakdown" : word_breakdown,
            "message"        : "Scored successfully"
        })

    except Exception as e:
        print(f"Error during transcription: {str(e)}")
        return jsonify({
            "error"  : str(e),
            "score"  : None,
            "message": "Transcription failed"
        }), 500


@app.route('/transcribe-only', methods=['POST'])
def transcribe_only():
    """
    Just transcribe without scoring.
    Useful for debugging.
    """
    data = request.get_json()

    if not data:
        return jsonify({"error": "No data received"}), 400

    recording_path = data.get('recording_path')

    if not recording_path or not os.path.exists(recording_path):
        return jsonify({"error": "Recording file not found"}), 404

    try:
        result = model.transcribe(recording_path, language="en", fp16=False)
        return jsonify({
            "transcript": result["text"].strip(),
            "language"  : result.get("language", "en")
        })
    except Exception as e:
        return jsonify({"error": str(e)}), 500


if __name__ == '__main__':
    print("Starting Readify Kids ML API...")
    print("Endpoints:")
    print("  GET  /health          — check if API is running")
    print("  POST /score           — transcribe + score recording")
    print("  POST /transcribe-only — transcribe only (debug)")
    app.run(host='127.0.0.1', port=5000, debug=True)