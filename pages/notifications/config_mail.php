<?php
/**
 * PHPMailer configuration (PHP file)
 *
 * Copy this file to the same path and fill the 'password' value with your SMTP password.
 * Keep this file out of version control (add to .gitignore) to avoid committing secrets.
 *
 * Keys:
 *  - host, port, secure ('ssl' or 'tls')
 *  - username, password
 *  - from, from_name
 *  - debug (int)
 *  - app_url
 */

return [
    'host' => 'webmail.atgroup.es',
    'port' => 465,
    'secure' => 'ssl', // 'ssl' or 'tls'
    'username' => 'mandreo@atgroup.es',
    'password' => '&togk6Fy^5se', // <-- PUT SMTP PASSWORD HERE (or leave empty and use env/session)
    'from' => 'mandreo@atgroup.es',
    'from_name' => 'Notificador NIS2',
    'debug' => 2,
    'app_url' => 'http://localhost/NIS2',
];
