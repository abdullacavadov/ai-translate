# AI Translate

Fast, responsive translation application powered by Google Cloud Translation NMT.

## Stack

- PHP
- Vanilla JavaScript
- AJAX / Fetch API
- Bootstrap 5
- Google Cloud Translation - Basic API (v2)

## Features

- Source and target language selection
- Automatic source language detection
- Language swap
- Google Neural Machine Translation (NMT)
- Automatic translation while typing
- Client-side translation cache
- Copy translation
- Text-to-speech
- Responsive interface
- Dark / Light mode

## Setup

1. Enable the Cloud Translation API in your Google Cloud project.
2. Create an API key for the project.
3. Restrict the API key to the Cloud Translation API.
4. Copy `config/config.example.php` to `config/config.php`.
5. Add your Google Cloud Translation API key to `config/config.php`.
6. Make sure PHP cURL is enabled.
7. Open the project through Apache/XAMPP.

Example configuration:

```php
<?php
return [
    'google_translate_api_key' => 'YOUR_GOOGLE_TRANSLATE_API_KEY',
];
```

The real API key must never be committed to Git.

## Translation engine

The app uses Google Cloud Translation - Basic v2 with the NMT model. When the source language is set to Auto Detect, the source language is omitted and Google detects it automatically.

The translation endpoint is server-side, so the API key is not exposed to browser JavaScript.

## License

All Rights Reserved.

This source code is proprietary. Unauthorized copying, modification, distribution, or use of this code or any part of this project is prohibited.
