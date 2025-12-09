<?php
// Database auto-provision & connection bootstrap for Mio Grocery Store.
// Adjust these with your local DB settings (XAMPP defaults shown).
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'grocery_store');
define('DB_USER', 'root');
define('DB_PASS', '');

// Establish a server-level connection (without specifying db) to allow creation if missing.
try {
    $serverPdo = new PDO('mysql:host=' . DB_HOST . ';charset=utf8mb4', DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (Exception $e) {
    die('Server connection failed: ' . $e->getMessage());
}

// Check if the target database exists; create and initialize from schema if not.
try {
    $stmt = $serverPdo->query("SHOW DATABASES LIKE '" . DB_NAME . "'");
    $exists = $stmt->fetchColumn();
    if (!$exists) {
        // Load schema file and execute its statements.
        $schemaPath = __DIR__ . '/../db/schema.sql';
        if (!file_exists($schemaPath)) {
            throw new RuntimeException('Schema file missing: ' . $schemaPath);
        }
        $schemaSql = file_get_contents($schemaPath);
        if ($schemaSql === false) {
            throw new RuntimeException('Unable to read schema file.');
        }
        // Split statements on semicolons while preserving multi-line statements.
        $statements = array_filter(array_map('trim', preg_split('/;\s*\n/', $schemaSql)));
        foreach ($statements as $sql) {
            // Skip comments and empties.
            if ($sql === '' || preg_match('/^--/m', $sql)) continue;
            $serverPdo->exec($sql);
        }
        // After creation, seed default admin if none exists
        $appPdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $adminCheck = $appPdo->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();
        if ((int)$adminCheck === 0) {
            $pass = password_hash('admin123', PASSWORD_DEFAULT);
            $ins = $appPdo->prepare('INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)');
            $ins->execute(['Administrator','admin@mio.local',$pass,'admin']);
        }
    }
} catch (Exception $e) {
    die('Database provisioning failed: ' . $e->getMessage());
}

// Now connect specifically to the application database.
try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Exception $e) {
    die('Database connection failed: ' . $e->getMessage());
}

// $pdo is now ready for application queries.

// Ensure there is at least one admin user even if DB pre-existed.
try {
    $adminCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();
    if ((int)$adminCount === 0) {
        $pass = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)');
        $stmt->execute(['Administrator','admin@mio.local',$pass,'admin']);
    }
    // Ensure products.image_url column exists
    $colCheck = $pdo->query("SHOW COLUMNS FROM products LIKE 'image_url'")->fetchColumn();
    if (!$colCheck) {
        $pdo->exec('ALTER TABLE products ADD COLUMN image_url VARCHAR(512) NULL AFTER stock_quantity');
    }
    // Ensure user_cart_items table exists for logged-in cart persistence
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS user_cart_items (
            user_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL,
            PRIMARY KEY (user_id, product_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );
    // Ensure orders.user_id exists and references users(id)
    $orderUserCol = $pdo->query("SHOW COLUMNS FROM orders LIKE 'user_id'")->fetchColumn();
    if (!$orderUserCol) {
        $pdo->exec('ALTER TABLE orders ADD COLUMN user_id INT NULL AFTER customer_id');
        // Add FK if possible (ignore if fails due to existing data)
        try { $pdo->exec('ALTER TABLE orders ADD CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL'); } catch (Exception $e) {}
    }
} catch (Exception $e) {
    // Silent fail if table missing; schema creation will handle it.
}
