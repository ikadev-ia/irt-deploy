<?php
// config/chatbot_config.php
// Obtenez une clé API gratuite sur https://aistudio.google.com/apikey
define('GEMINI_API_KEY', ''); // Laissez vide pour n'utiliser que la base locale
define('USE_GEMINI', !empty(GEMINI_API_KEY));
?>