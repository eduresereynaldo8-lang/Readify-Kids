import requests
import json
import os

# Test 1: Health check
print("=" * 50)
print("TEST 1: Health check")
res = requests.get('http://127.0.0.1:5000/health')
print(f"Status code: {res.status_code}")
print(f"Response: {res.json()}")
print()

# Test 2: Score endpoint
print("=" * 50)
print("TEST 2: Score endpoint")

test_audio = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'test_audio.wav')
print(f"Looking for audio at: {test_audio}")
print(f"File exists: {os.path.exists(test_audio)}")
print()

if os.path.exists(test_audio):
    payload = {
        'recording_path': test_audio,
        'expected_text' : 'the big red dog'
    }
    print(f"Sending payload: {json.dumps(payload, indent=2)}")
    print()

    res = requests.post(
        'http://127.0.0.1:5000/score',
        json=payload,
        headers={'Content-Type': 'application/json'}
    )

    print(f"Status code: {res.status_code}")
    print(f"Raw response: {res.text}")
    print()

    try:
        data = res.json()
        print(f"Score:      {data.get('score')}%")
        print(f"Transcript: {data.get('transcript')}")
        print(f"Expected:   {data.get('expected')}")
        print(f"Error:      {data.get('error')}")
        print(f"Breakdown:  {json.dumps(data.get('word_breakdown'), indent=2)}")
    except Exception as e:
        print(f"Failed to parse response: {e}")
else:
    print("❌ Audio file not found!")
    print()
    print("Please do the following:")
    print("1. Open Windows Voice Recorder")
    print("2. Record yourself saying: 'the big red dog'")
    print("3. Right click the recording → Open file location")
    print("4. Copy the .m4a file to ml_api folder")
    print("5. Rename it to test_audio.wav")
    print("   (or test_audio.m4a — we will handle both)")
    print()

    # Try to find any audio file in the ml_api folder
    print("Looking for any audio files in ml_api folder...")
    ml_api_dir = os.path.dirname(os.path.abspath(__file__))
    audio_extensions = ['.wav', '.mp3', '.m4a', '.webm', '.ogg', '.mp4']
    found = []
    for f in os.listdir(ml_api_dir):
        if any(f.endswith(ext) for ext in audio_extensions):
            found.append(f)

    if found:
        print(f"Found audio files: {found}")
        print(f"Testing with first file: {found[0]}")
        test_audio = os.path.join(ml_api_dir, found[0])

        res = requests.post(
            'http://127.0.0.1:5000/score',
            json={
                'recording_path': test_audio,
                'expected_text' : 'the big red dog'
            },
            headers={'Content-Type': 'application/json'}
        )
        print(f"Status code: {res.status_code}")
        print(f"Raw response: {res.text}")
    else:
        print("No audio files found in ml_api folder.")
        print("Please add a test audio file and try again.")