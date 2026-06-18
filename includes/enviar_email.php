<?php
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function enviarEmail($destinatario, $asunto, $cuerpoHtml) {
    $cfg = include __DIR__ . '/config_email.php';

    if (empty($cfg['smtp_host']) || empty($cfg['smtp_user']) || empty($cfg['smtp_pass'])) {
        error_log("config_email.php no configurado");
        return false;
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = $cfg['smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = $cfg['smtp_user'];
        $mail->Password = $cfg['smtp_pass'];
        $mail->SMTPSecure = $cfg['smtp_secure'] ?? 'tls';
        $mail->Port = $cfg['smtp_port'] ?? 587;

        $mail->setFrom($cfg['from_email'] ?: $cfg['smtp_user'], $cfg['from_name'] ?? 'FullTaller');
        $mail->addAddress($destinatario);
        $mail->CharSet = 'UTF-8';
        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body = $cuerpoHtml;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Error enviando email: " . $mail->ErrorInfo);
        return false;
    }
}
