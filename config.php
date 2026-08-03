<?php
date_default_timezone_set('UTC');
define('DB_HOST', 'sql206.infinityfree.com');      
define('DB_NAME', 'if0_41416197_scratchnews'); 
define('DB_USER', 'if0_41416197');         
define('DB_PASS', 'ltaqe0ytjXmcdHl');

define('ADMIN_USER', 'ScratchNews');
define('ADMIN_PASS_HASH', '$2y$10$lXSP7uyjOPjXW04m3PtPdOwixC4a2mkkV2V8NlDRVrKhJQrD7Uh7e');
define('SITE_NAME', 'ScratchNews');
define('SITE_VERSION', '0.17.1');
define('BREVO_API_KEY', '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');
define('BREVO_SENDER_EMAIL', 'david.todb@gmail.com');

define('GITHUB_TOKEN', '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');
define('GITHUB_REPO', 'xTODB/scratchnews-data');
define('GITHUB_BRANCH', 'main');
define('GOOGLE_CLIENT_ID', '926088564769-se27dq865srapskopjuknjbqrf7qbm72.apps.googleusercontent.com');
define('OPENAI_API_KEY', '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');

function getDB(): mysqli {
    static $conn = null;
    if ($conn === null) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            $conn->set_charset('utf8mb4');
            $conn->query("SET time_zone = '+00:00'");
        } catch (mysqli_sql_exception $e) {
            http_response_code(500);
            die('Database connection failed. Double-check the credentials in config.php.');
        }
    }
    return $conn;
}