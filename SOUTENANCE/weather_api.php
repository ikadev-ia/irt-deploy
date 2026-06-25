<?php
function getMaliWeather() {
    return [
        'temperature' => 35,
        'humidity' => 30,
        'description' => 'Ensoleillé',
        'feels_like' => 37,
        'wind_speed' => 10,
        'city' => 'Bamako, Mali',
        'advice' => '✅ Conditions normales pour l\'élevage'
    ];
}

function calculateChickenNeeds($batch_age, $bird_count, $weather) {
    if ($batch_age < 10) {
        $base_feed_kg = $bird_count * 0.08;
        $feed_type = "Démarrage (0-10j)";
    } elseif ($batch_age < 30) {
        $base_feed_kg = $bird_count * 0.12;
        $feed_type = "Croissance (11-30j)";
    } else {
        $base_feed_kg = $bird_count * 0.15;
        $feed_type = "Finition (31+j)";
    }
    
    return [
        'daily_feed_kg' => round($base_feed_kg, 1),
        'daily_water_liters' => round($bird_count * 0.25, 1),
        'base_feed_kg' => round($base_feed_kg, 1),
        'adjustment_percent' => '0%',
        'feed_type' => $feed_type,
        'advice' => 'Suivez le programme d\'alimentation standard'
    ];
}

function getPreventiveAdvice($batch_age, $weather) {
    $advice = [];
    if ($batch_age < 7) {
        $advice[] = "🐣 Semaine 1 : Température 32-35°C, lumière 23h/24h";
    } elseif ($batch_age < 14) {
        $advice[] = "📈 Semaine 2 : Baisser température à 30°C";
    } elseif ($batch_age < 21) {
        $advice[] = "💪 Semaine 3 : Vérifier la consommation d'aliment";
    } elseif ($batch_age < 28) {
        $advice[] = "🏃 Semaine 4 : Prévention coccidiose";
    } else {
        $advice[] = "🐔 Phase finition : Aliment concentré, surveillance du poids";
    }
    
    if ($weather['temperature'] > 35) {
        $advice[] = "🔥 Canicule : Augmenter l'eau et la ventilation";
    }
    if ($weather['humidity'] < 30) {
        $advice[] = "💧 Air sec : Brumiser pour augmenter l'humidité";
    }
    
    return $advice;
}
?>