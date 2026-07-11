<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // 1. Enter the clean text hostname (DO NOT include mysql:// or :13306)
    $host = 'scratchnews-scratchnews.d.aivencloud.com'; 
    $user = 'avnadmin';
    $pass = 'AVNS_mAOAv_6PmX9CtpKB6Kx'; 
    $name = 'defaultdb';
    $port = 24839;

    $conn = mysqli_init();
    $conn->ssl_set(NULL, NULL, NULL, NULL, NULL);
    
    // Explicit port structure
    $conn->real_connect($host, $user, $pass, $name, (int)$port, null, MYSQLI_CLIENT_SSL);
    $conn->set_charset('utf8mb4');

    // 2. Your raw schema execution queries
    $sql = "
    CREATE TABLE IF NOT EXISTS articles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    summary VARCHAR(500) NOT NULL,
    content TEXT NOT NULL,
    author VARCHAR(100) NOT NULL DEFAULT 'ScratchNews Staff',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS visits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    page VARCHAR(255) NOT NULL,
    user_agent VARCHAR(255) NOT NULL,
    visited_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";

    if ($conn->multi_query($sql)) {
        echo "<h1>🎉 SUCCESS! Your database tables are created.</h1>";
    }

} catch (mysqli_sql_exception $e) {
    echo "<h1>❌ Connection Error:</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
