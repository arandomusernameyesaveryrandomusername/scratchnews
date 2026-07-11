<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // 1. Separate the text host from the numeric port cleanly
    $host = '://aivencloud.com'; 
    $user = 'avnadmin';
    $pass = 'AVNS_HAnT8Zz_XidX6H6_lV7'; 
    $name = 'defaultdb';
    $port = 13306; 

    $conn = mysqli_init();
    $conn->ssl_set(NULL, NULL, NULL, NULL, NULL);
    
    // Pass the port explicitly as a standalone number at the end
    $conn->real_connect($host, $user, $pass, $name, (int)$port, null, MYSQLI_CLIENT_SSL);
    $conn->set_charset('utf8mb4');

    // 2. Your raw schema execution queries
    $sql = "
    /* PASTE YOUR SCHEMA.SQL TEXT HERE */
    ";

    if ($conn->multi_query($sql)) {
        echo "<h1>🎉 SUCCESS! Your database tables are created.</h1>";
    }

} catch (mysqli_sql_exception $e) {
    echo "<h1>❌ Connection Error:</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
