<?php
define('DB_HOST', $_ENV['DB_HOST']);      
define('DB_NAME', $_ENV['DB_NAME']); 
define('DB_USER', $_ENV['DB_USER']);         
define('DB_PASS', $_ENV['DB_PASS']);

define('ADMIN_USER', $_ENV['ADMIN_USER']);
define('ADMIN_PASS_HASH', $_ENV['ADMIN_PASS_HASH']);
define('SITE_NAME', 'ScratchNews');

define('BREVO_API_KEY', $_ENV['BREVO_API_KEY']);
define('BREVO_SENDER_EMAIL', $_ENV['BREVO_SENDER_EMAIL']);

function getDB(): mysqli {
    static $conn = null;
    if ($conn === null) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        try {
            $host = DB_HOST;
            $port = 3306;
            if (strpos($host, ':') !== false) {
                list($host, $port) = explode(':', $host);
            }
            
            // Create the connection object
            $conn = mysqli_init();
            
            // Establish the connection passing the native MYSQLI_CLIENT_SSL flag at the end
            $conn->real_connect($host, DB_USER, DB_PASS, DB_NAME, (int)$port, null, MYSQLI_CLIENT_SSL);
            $conn->set_charset('utf8mb4');
        } catch (mysqli_sql_exception $e) {
            http_response_code(500);
            die('Database connection failed. Double-check the credentials in config.php.');
        }
    }
    return $conn;
}


