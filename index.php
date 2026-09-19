<!doctype html>
<html lang="az">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AI Translate</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
<main class="app-shell">
    <div class="container py-4 py-lg-5">
        <header class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <div class="brand">AI<span>Translate</span></div>
                <p class="subtitle mb-0">AI-powered translation</p>
            </div>
            <button id="themeToggle" class="icon-btn" type="button" aria-label="Theme">☾</button>
        </header>

        <section class="translator-card">
            <div class="language-bar">
                <select id="fromLanguage" class="form-select">
                    <option value="auto">Auto Detect</option>
                    <option value="az">Azərbaycan</option>
                    <option value="en">English</option>
                    <option value="tr">Türkçe</option>
                    <option value="de">Deutsch</option>
                    <option value="ru">Русский</option>
                    <option value="fr">Français</option>
                    <option value="es">Español</option>
                </select>
                <button id="swapBtn" class="swap-btn" type="button" aria-label="Swap languages">⇄</button>
                <select id="toLanguage" class="form-select">
                    <option value="en" selected>English</option>
                    <option value="az">Azərbaycan</option>
                    <option value="tr">Türkçe</option>
                    <option value="de">Deutsch</option>
                    <option value="ru">Русский</option>
                    <option value="fr">Français</option>
                    <option value="es">Español</option>
                </select>
            </div>

            <div class="translation-grid">
                <div class="text-panel">
                    <textarea id="sourceText" maxlength="5000" placeholder="Translate something..."></textarea>
                    <div class="panel-footer">
                        <span id="charCount">0 / 5000</span>
                        <div>
                            <button id="clearBtn" class="text-btn" type="button">Clear</button>
                            <button id="speakSourceBtn" class="text-btn" type="button">🔊</button>
                        </div>
                    </div>
                </div>

                <div class="text-panel result-panel">
                    <div id="resultText" class="result-text">Translation will appear here...</div>
                    <div class="panel-footer">
                        <span id="statusText">Ready</span>
                        <div>
                            <button id="copyBtn" class="text-btn" type="button">Copy</button>
                            <button id="speakResultBtn" class="text-btn" type="button">🔊</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="action-row">
                <select id="tone" class="form-select tone-select" aria-label="Translation style">
                    <option value="natural">Natural</option>
                    <option value="formal">Formal</option>
                    <option value="professional">Professional</option>
                    <option value="casual">Casual</option>
                </select>
                <span class="auto-translate-hint">Auto translation enabled</span>
            </div>
        </section>
    </div>
</main>
<script src="assets/js/app.js"></script>
</body>
</html>
