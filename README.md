# 📚 Readify Kids

**Readify Kids** is a web-based **Gamified Reading Assistance System** designed to improve the reading skills of Grade 2 learners. The system combines interactive reading activities, a battle mode, teacher evaluation, and a machine learning speech assessment powered by OpenAI Whisper.

---

# Features

### Teacher

* Teacher authentication
* Dashboard with activity statistics
* Reading material management
* Activity management
* Battle Mode activity creation
* Student progress monitoring
* Manual reading evaluation
* Achievement and reward management

### Student

* Student authentication
* Interactive Read Aloud activities
* Battle Mode
* Earn points and rewards
* Achievement system
* Leaderboard
* Reading history
* Voice recording submission

### Machine Learning

* Speech-to-text using OpenAI Whisper
* Automatic pronunciation/reading analysis
* Integration between Laravel and Python Flask API

---

# System Requirements

## Software

* PHP 8.2 or later
* Composer
* XAMPP (Apache + MySQL)
* Node.js & npm
* Python 3.14 or later
* Git

---

# Installation Guide

## 1. Clone the Repository

```bash
git clone https://github.com/YOUR_USERNAME/Readify-Kids.git
cd Readify-Kids
```

---

## 2. Install PHP Dependencies

```bash
composer install
```

---

## 3. Install JavaScript Dependencies

```bash
npm install
```

(Optional)

```bash
npm run build
```

or

```bash
npm run dev
```

---

## 4. Create Environment File

Windows

```cmd
copy .env.example .env
```

Linux / macOS

```bash
cp .env.example .env
```

---

## 5. Generate Application Key

```bash
php artisan key:generate
```

---

## 6. Configure Database

Open phpMyAdmin and create a database named

```
readify_kids_db
```

Edit the `.env` file.

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=readify_kids_db
DB_USERNAME=root
DB_PASSWORD=
```

---

## 7. Run Database Migrations

```bash
php artisan migrate
```

If seeders are available:

```bash
php artisan db:seed
```

or

```bash
php artisan migrate:fresh --seed
```

---

## 8. Install Python Dependencies

Navigate to the ML API folder.

```bash
cd ml_api
```

Install all required packages.

```bash
python -m pip install -r requirements.txt
```

---

## 9. Start the Whisper Flask API

```bash
python ml_api.py
```

The API should remain running while using the system.

---

## 10. Run Laravel

Return to the project folder.

```bash
cd ..
```

Run Laravel.

```bash
php artisan serve
```

or access through XAMPP:

```
http://localhost/Readify-Kids/public
```

---

# Default Project Structure

```
Readify-Kids
│
├── app/
├── bootstrap/
├── config/
├── database/
│   ├── migrations/
│   ├── seeders/
│   └── factories/
│
├── ml_api/
│   ├── ml_api.py
│   ├── requirements.txt
│   └── ...
│
├── public/
├── resources/
├── routes/
├── storage/
├── vendor/
├── composer.json
├── package.json
├── README.md
└── .env.example
```

---

# Machine Learning

The system uses **OpenAI Whisper** to transcribe students' voice recordings during reading activities.

Workflow:

1. Student records voice.
2. Laravel uploads the audio.
3. Python Flask API receives the audio.
4. Whisper transcribes the speech.
5. Results are returned to Laravel.
6. Teacher reviews and confirms the score.

---

# Technologies Used

### Backend

* Laravel 12
* PHP 8.2

### Frontend

* Blade Templates
* Bootstrap 5
* JavaScript

### Database

* MySQL

### Machine Learning

* Python
* Flask
* OpenAI Whisper
* PyTorch

---

# Troubleshooting

## Missing APP_KEY

Run:

```bash
php artisan key:generate
```

---

## Missing Vendor Folder

Run:

```bash
composer install
```

---

## Missing Python Packages

Run:

```bash
python -m pip install -r requirements.txt
```

---

## Database Errors

Ensure:

* MySQL is running.
* Database `readify_kids_db` exists.
* `.env` database settings are correct.
* Run:

```bash
php artisan migrate
```

---

## Battle Mode Errors

If Battle Mode reports missing columns, ensure all migrations have been executed:

```bash
php artisan migrate
```

---

# Contributors

* Reynaldo Edurese
* BSIT Capstone Team

---

# License

This project was developed as a Bachelor of Science in Information Technology (BSIT) Capstone Project for educational purposes.
