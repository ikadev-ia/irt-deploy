<?php
// config/mail.php
function sendEmail($to, $subject, $message) {
    // Exemple avec mail() (à adapter selon votre serveur)
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: PoultryTracker <noreply@poultrytracker.com>\r\n";
    return mail($to, $subject, $message, $headers);
}
?>