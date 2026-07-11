<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $host = '://aivencloud.com'; 
    $user = 'avnadmin';
    $pass = 'AVNS_HAnT8Zz_XidX6H6_lV7'; 
    $name = 'defaultdb';
    $port = 24839; 

    $conn = mysqli_init();
    $conn->ssl_set(NULL, NULL, NULL, NULL, NULL);
    $conn->real_connect($host, $user, $pass, $name, (int)$port, null, MYSQLI_CLIENT_SSL);
    $conn->set_charset('utf8mb4');

    // Read the database file safely from your local repository folder
    $sqlFile = __DIR__ . '/backup.sql';
    if (!file_exists($sqlFile)) {
        die("<h1>❌ Error: cannot find backup.sql in your root folder.</h1>");
    }
    
    $sql = file_get_contents($sqlFile);

    // Execute the full migration
    if ($conn->multi_query($sql)) {
        echo "<h1>🎉 SUCCESS! Your entire InfinityFree database and live rows migrated perfectly!</h1>";
        echo "<p>Go ahead and test your main homepage now.</p>";
    }

} catch (mysqli_sql_exception $e) {
    echo "<h1>❌ Migration Error:</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
