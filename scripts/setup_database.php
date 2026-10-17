<?php
try {
    $host = 'localhost';
    $username = 'root';
    $password = '';
    $dbname = 'company_portal';

    // Create initial connection
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Drop existing database if it exists
    $pdo->exec("DROP DATABASE IF EXISTS `$dbname`");
    echo "<div class='message warning'>Existing database dropped successfully.</div>";

    // Read and execute SQL file
    $sql = file_get_contents(__DIR__ . '/../database/database_setup.sql');
    $pdo->exec($sql);
    
    echo "<div class='message success'>
            <h3>✅ Database Setup Successful!</h3>
            <p>Database recreated with fresh tables and sample data.</p>
          </div>";

} catch(PDOException $e) {
    echo "<div class='message error'>
            <h3>❌ Setup Error</h3>
            <p>Error: " . htmlspecialchars($e->getMessage()) . "</p>
          </div>";
}
?>

<style>
.message {
    padding: 15px;
    border-radius: 4px;
    margin: 20px;
    font-family: Arial, sans-serif;
}
.success {
    background-color: #d4edda;
    color: #155724;
}
.error {
    background-color: #f8d7da;
    color: #721c24;
}
.warning {
    background-color: #fff3cd;
    color: #856404;
}
h3 { margin-top: 0; }
p { margin-bottom: 0; }
</style>