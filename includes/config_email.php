<?php
// Configuración SMTP para envío de emails del sistema
// Creá una cuenta de correo en tu hosting (DonWeb/cPanel) y completá los datos
return [
    'smtp_host' => '',        // Ej: mail.tudominio.com
    'smtp_port' => 587,        // 587 (TLS) o 465 (SSL)
    'smtp_secure' => 'tls',    // 'tls' o 'ssl'
    'smtp_user' => '',         // Cuenta de correo completa: no-reply@tudominio.com
    'smtp_pass' => '',         // Contraseña de la cuenta
    'from_email' => '',        // Mismo correo de arriba
    'from_name' => 'FullTaller', // Nombre que verá el destinatario
];
