<?php
function sendAdminEmail($to, $subject, $body) {
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: Poulplume <poulplume@gmail.com>\r\n";
    
    return mail($to, $subject, $body, $headers);
}
?>