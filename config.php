<?php
// 1. Hardcoded Database Credentials (Bypassing Vercel's broken env variables)
define('DB_HOST', 'scratchnews-scratchnews.d.aivencloud.com');     
define('DB_NAME', 'defaultdb'); 
define('DB_USER', 'avnadmin');         
define('DB_PASS', 'AVNS_HAnT8Zz_XidX6H6_lV7'); // 👈 Paste your real revealed Aiven password here
define('DB_PORT', 24839);

// 2. Admin Credentials
define('ADMIN_USER', 'ScratchNews');
define('ADMIN_PASS_HASH', '$2y$10$lXSP7uyjOPjXW04m3PtPdOwixC4a2mkkV2V8NlDRVrKhJQrD7Uh7e');

// 3. Site Configuration
define('SITE_NAME', 'ScratchNews');

// 4. Brevo Email Credentials
define('BREVO_API_KEY', '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'); // 👈 Paste your new Brevo key here if you changed it
define('BREVO_SENDER_EMAIL', 'david.todb@gmail.com');

function getDB(): mysqli {
    static $conn = null;
    if ($conn === null) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        try {
            $conn = mysqli_init();
            $conn->ssl_set(NULL, NULL, NULL, NULL, NULL);
            $conn->real_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, (int)DB_PORT, null, MYSQLI_CLIENT_SSL);
            $conn->set_charset('utf8mb4');
        } catch (mysqli_sql_exception $e) {
            http_response_code(500);
            die('Database connection failed. Double-check the credentials in config.php.');
        }
    }
    return $conn;
}
