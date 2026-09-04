import torch
from tqdm import tqdm
from datasets import load_dataset, Audio
from transformers import WhisperProcessor, WhisperForConditionalGeneration
import jiwer

# ============================================================
# WHISPER SMALL MODEL VALIDATION
# ============================================================

MODEL_ID = "openai/whisper-small"

# LibriSpeech English validation set
DATASET_ID = "openslr/librispeech_asr"
DATASET_CONFIG = "clean"

# Number of samples to evaluate
NUM_SAMPLES = 50

# Batch size
# CPU: use 4 or 8
# GPU: you can try 16
BATCH_SIZE = 4

# Device
DEVICE = "cuda" if torch.cuda.is_available() else "cpu"

print("=" * 60)
print("        WHISPER SMALL MODEL VALIDATION")
print("=" * 60)
print(f"Model: {MODEL_ID}")
print(f"Dataset: {DATASET_ID}")
print("Language: English")
print(f"Samples: {NUM_SAMPLES}")
print(f"Device: {DEVICE}")
print("=" * 60)


# ============================================================
# 1. LOAD MODEL
# ============================================================

print("\nLoading Whisper model...")

processor = WhisperProcessor.from_pretrained(
    MODEL_ID,
    language="english",
    task="transcribe"
)

model = WhisperForConditionalGeneration.from_pretrained(
    MODEL_ID
).to(DEVICE)

model.eval()

print("Whisper model loaded successfully!")


# ============================================================
# 2. LOAD DATASET
# ============================================================

print("\nLoading LibriSpeech validation dataset...")

dataset = load_dataset(
    DATASET_ID,
    DATASET_CONFIG,
    split="validation",
    streaming=True
)

# Force audio to 16 kHz
dataset = dataset.cast_column(
    "audio",
    Audio(sampling_rate=16000)
)

# Limit number of samples
eval_dataset = dataset.take(NUM_SAMPLES)

print("Dataset loaded successfully!")


# ============================================================
# 3. VALIDATION VARIABLES
# ============================================================

predictions = []
references = []

current_audio = []
current_text = []


# ============================================================
# 4. RUN VALIDATION
# ============================================================

print("\nRunning validation inference...\n")

for sample in tqdm(eval_dataset, total=NUM_SAMPLES):

    audio = sample["audio"]["array"]
    text = sample["text"]

    current_audio.append(audio)
    current_text.append(text)

    # Process batch
    if len(current_audio) == BATCH_SIZE:

        inputs = processor(
            current_audio,
            sampling_rate=16000,
            return_tensors="pt"
        )

        input_features = inputs.input_features.to(DEVICE)

        with torch.no_grad():

            predicted_ids = model.generate(
                input_features
            )

        transcripts = processor.batch_decode(
            predicted_ids,
            skip_special_tokens=True
        )

        predictions.extend(transcripts)
        references.extend(current_text)

        # Reset batch
        current_audio = []
        current_text = []


# ============================================================
# 5. PROCESS REMAINING BATCH
# ============================================================

if current_audio:

    inputs = processor(
        current_audio,
        sampling_rate=16000,
        return_tensors="pt"
    )

    input_features = inputs.input_features.to(DEVICE)

    with torch.no_grad():

        predicted_ids = model.generate(
            input_features
        )

    transcripts = processor.batch_decode(
        predicted_ids,
        skip_special_tokens=True
    )

    predictions.extend(transcripts)
    references.extend(current_text)


# ============================================================
# 6. NORMALIZE TEXT
# ============================================================

def normalize_text(text):
    """
    Basic normalization for WER calculation.
    Converts text to lowercase and removes punctuation.
    """

    text = text.lower()

    # Keep only letters, numbers and spaces
    cleaned = ""

    for char in text:

        if char.isalnum() or char.isspace():
            cleaned += char

    return " ".join(cleaned.split())


clean_predictions = [
    normalize_text(text)
    for text in predictions
]

clean_references = [
    normalize_text(text)
    for text in references
]


# ============================================================
# 7. CALCULATE WER
# ============================================================

wer_score = jiwer.wer(
    clean_references,
    clean_predictions
)


# ============================================================
# 8. DISPLAY RESULTS
# ============================================================

print("\n")
print("=" * 60)
print("              VALIDATION RESULTS")
print("=" * 60)

print(f"Model: {MODEL_ID}")
print(f"Dataset: {DATASET_ID}")
print(f"Total Samples Evaluated: {len(clean_references)}")

print(f"\nWord Error Rate (WER): {wer_score * 100:.2f}%")

print("=" * 60)


# ============================================================
# 9. SHOW SAMPLE PREDICTIONS
# ============================================================

print("\nSample Predictions:")
print("-" * 60)

for i in range(min(10, len(predictions))):

    print(f"\nSample {i + 1}")
    print(f"Reference : {references[i]}")
    print(f"Prediction: {predictions[i]}")

print("\nValidation completed successfully!")