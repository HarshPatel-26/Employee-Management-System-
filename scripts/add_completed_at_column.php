<?php
require_once __DIR__ . '/../app/lib/db.php';

try {
    $pdo = Database::pdo();
    
    // Add completed_at column to project_steps if it doesn't exist
    $sql = "SELECT COUNT(*) as column_exists 
            FROM information_schema.columns 
            WHERE table_schema = DATABASE() 
            AND table_name = 'project_steps' 
            AND column_name = 'completed_at'";
            
    $stmt = $pdo->query($sql);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['column_exists'] == 0) {
        $pdo->exec("ALTER TABLE project_steps 
                    ADD COLUMN completed_at timestamp NULL DEFAULT NULL 
                    AFTER is_done");
        echo "Added completed_at column to project_steps table.\n";
    } else {
        echo "completed_at column already exists.\n";
    }
    
    echo "Migration completed successfully!\n";
} catch (PDOException $e) {
    die("Migration failed: " . $e->getMessage() . "\n");
}