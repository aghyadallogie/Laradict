# Laradict Project

A modern Laravel personal dictionary project featuring user authentication, multi-language text translation, and DeepL API integration.

## Overview

This project provides a robust Laravel applications with user registration, authentication, and personalized text translation features. Users can register, select their preferred languages, and translate words or phrases using the DeepL API. All translations are stored and managed per user.

## Features

- User registration and login (with session management)
- User profile with language preferences
- Add and store words/phrases for translation
- Automatic source language detection
- Translates to all user-selected languages (always includes English)
- DeepL API integration for high-quality translations
- View and manage your translated texts

## Getting Started

### Prerequisites
- PHP >= 8.2
- Composer
- Node.js & npm (for frontend assets)
- A DeepL API key ([get one here](https://www.deepl.com/pro-api))

### Installation

1. **Clone the repository:**
   ```bash
   git clone <your-repo-url>
   cd laravel-auth-starter-project
   ```

2. **Install PHP dependencies:**
   ```bash
   composer install
   ```

3. **Install frontend dependencies:**
   ```bash
   npm install && npm run dev
   ```

4. **Copy and configure your environment:**
   ```bash
   cp .env.example .env
   # Edit .env and set your DB credentials and DEEPL_AUTH_KEY
   ```

5. **Generate application key:**
   ```bash
   php artisan key:generate
   ```

6. **Run migrations:**
   ```bash
   php artisan migrate
   ```

7. **Start the development server:**
   ```bash
   php artisan serve
   ```

## Environment Configuration

- Set your DeepL API key in `.env`:
  ```env
  DEEPL_AUTH_KEY=your_deepl_api_key_here
  ```
- Configure your database connection as needed.

## Usage

- Register a new user and select your preferred languages.
- Add words or phrases to translate. The app will detect the source language and translate to all your selected languages (always including English).
- View your translations on your profile or the words page.
- Manage your language preferences at any time.

## Translation Logic

- The app uses a dedicated `DeepLService` to handle all translation logic and API calls.
- Source language is auto-detected using DeepL.
- Translations are stored per user, and always include English.

## Project Structure

- `app/Http/Controllers/` — Application controllers (auth, words)
- `app/Services/DeepLService.php` — DeepL translation logic
- `app/Models/` — Eloquent models (User, Word)
- `resources/views/` — Blade templates
- `routes/web.php` — Web routes
- `config/languages.php` — Supported languages

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/YourFeature`)
3. Commit your changes (`git commit -am 'Add some feature'`)
4. Push to the branch (`git push origin feature/YourFeature`)
5. Open a pull request

## License

This project is open-source and available under the [MIT license](LICENSE).
