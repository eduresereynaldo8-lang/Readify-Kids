"""Run the scoring test and print results."""
import requests
import json
import time
import os
import sys

BASE_URL = "http://127.0.0.1:5000"

# 1. Health check
print("=" * 50)
print("TEST 1: Health check")
try:
    r = requests.get(f"{BASE_URL}/health", timeout=10)
    print(f"Status code: {r.status_code}")
    print(f"Response: {r.json()}")
except Exception as e:
    print(f"FAILED: {e}")
    sys.exit(1)

# 2. Score endpoint
print("\n" + "=" * 50)
print("TEST 2: Score endpoint")

test_audio = os.path.join(os.path.dirname(os.path.abspath(__file__)), "test_audio.wav")
print(f"Looking for audio at: {test_audio}")
print(f"File exists: {os.path.exists(test_audio)}")

if not os.path.exists(test_audio):
    print("ERROR: test_audio.wav not found!")
    sys.exit(1)

payload = {
    "recording_path": test_audio,
    "expected_text": "the big red dog"
}

print(f"\nSending payload: {json.dumps(payload, indent=2)}")
print("\n⏳ Processing... (Whisper model loading + transcription may take a minute)")
print("   This is normal for first run. Subsequent calls will be faster!")
sys.stdout.flush()

start = time.time()
try:
    res = requests.post(
        f"{BASE_URL}/score",
        json=payload,
        headers={"Content-Type": "application/json"},
        timeout=300  # 5 minute timeout for first model load
    )
    elapsed = time.time() - start
    print(f"\n✅ Response received after {elapsed:.1f} seconds")
    print(f"Status code: {res.status_code}")
    data = res.json()
    print(f"Response:\n{json.dumps(data, indent=2)}")
    
    if "error" in data:
        print(f"\n❌ Error from server: {data['error']}")
    elif "score" in data:
        print(f"\n🎯 SCORE: {data['score']}/100")
        print("   The ML API is working correctly!")
    elif "transcription" in data:
        print(f"\n📝 Transcription: {data['transcription']}")
        print(f"🎯 Score: {data.get('score', 'N/A')}/100")
    
except Exception as e:
    elapsed = time.time() - start
    print(f"\n❌ Request failed after {elapsed:.1f}s: {e}")
    print("\nPossible issues:")
    print("  1. Flask server not running (run: python ml_api.py)")
    print("  2. First model load taking longer - try increasing timeout")
    print("  3. Audio file format issue")
    sys.exit(1)

print("\n" + "=" * 50)
print("✅ ALL TESTS COMPLETED")

