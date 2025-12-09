<?php
require_once __DIR__ . '/BaseController.php';

class AdminController extends BaseController
{
    public function dashboard()
    {
        $this->requireAdmin();
        global $pdo;
        // Summary metrics
        $totalUsers = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        $totalProducts = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
        $totalOrders = (int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
        $totalRevenue = (float)$pdo->query('SELECT COALESCE(SUM(total_amount),0) FROM orders')->fetchColumn();
        $today = date('Y-m-d');
        $ordersToday = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(order_date) = '".$today."'")->fetchColumn();
        $revenueToday = (float)$pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE DATE(order_date) = '".$today."'")->fetchColumn();

        // Recent orders
        $recent = $pdo->query('SELECT o.id, o.order_date, o.total_amount, u.name AS user_name, u.email AS user_email
                               FROM orders o LEFT JOIN users u ON o.user_id = u.id
                               ORDER BY o.id DESC LIMIT 10')->fetchAll();

        $this->render('admin/dashboard', [
            'summary' => [
                'users' => $totalUsers,
                'products' => $totalProducts,
                'orders' => $totalOrders,
                'revenue' => $totalRevenue,
                'ordersToday' => $ordersToday,
                'revenueToday' => $revenueToday,
            ],
            'recentOrders' => $recent,
        ]);
    }

    public function users()
    {
        $this->requireAdmin();
        global $pdo;
        $rows = $pdo->query('SELECT u.id, u.name, u.email, u.role,
                                     (SELECT COUNT(*) FROM orders o WHERE o.user_id=u.id) AS orders_count,
                                     (SELECT COALESCE(SUM(total_amount),0) FROM orders o2 WHERE o2.user_id=u.id) AS total_spent,
                                     (SELECT COALESCE(SUM(quantity),0) FROM user_cart_items c WHERE c.user_id=u.id) AS cart_items
                              FROM users u ORDER BY u.id DESC')->fetchAll();
        $this->render('admin/users', [ 'users' => $rows ]);
    }

    public function orders()
    {
        $this->requireAdmin();
        global $pdo;
        $rows = $pdo->query('SELECT o.id, o.order_date, o.total_amount,
                                     (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id=o.id) AS items,
                                     u.name AS user_name, u.email AS user_email
                              FROM orders o LEFT JOIN users u ON o.user_id = u.id
                              ORDER BY o.id DESC LIMIT 200')->fetchAll();
        $this->render('admin/orders', [ 'orders' => $rows ]);
    }

    public function carts()
    {
        $this->requireAdmin();
        global $pdo;
        // Users with any cart items
        $users = $pdo->query('SELECT u.id, u.name, u.email,
                                     COALESCE(SUM(c.quantity),0) AS items
                              FROM users u
                              LEFT JOIN user_cart_items c ON c.user_id=u.id
                              GROUP BY u.id
                              HAVING items > 0
                              ORDER BY items DESC')->fetchAll();
        // Fetch items per user
        $itemsByUser = [];
        $stmt = $pdo->prepare('SELECT c.product_id, c.quantity, p.name, p.price
                               FROM user_cart_items c JOIN products p ON p.id=c.product_id
                               WHERE c.user_id=? ORDER BY p.name ASC');
        foreach ($users as $u) {
            $stmt->execute([$u['id']]);
            $itemsByUser[$u['id']] = $stmt->fetchAll();
        }
        $this->render('admin/carts', [ 'users' => $users, 'itemsByUser' => $itemsByUser ]);
    }

    // GET: /admin/user?id=123 shows details and order history
    // POST: update role for a user (admin/customer)
    public function user()
    {
        $this->requireAdmin();
        global $pdo;
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) { http_response_code(400); echo 'Missing user id'; return; }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (! $this->verifyCsrf($_POST['csrf'] ?? '')) { die('Bad CSRF token'); }
            $role = $_POST['role'] ?? '';
            if (!in_array($role, ['admin','customer'], true)) { $role = 'customer'; }
            $up = $pdo->prepare('UPDATE users SET role=? WHERE id=?');
            $up->execute([$role, $id]);
            $this->setFlash('success','User role updated');
            // PRG
            header('Location: ' . $this->baseUrl('admin/user') . '?id=' . $id);
            return;
        }

        // Fetch user
        $stmt = $pdo->prepare('SELECT id, name, email, role FROM users WHERE id=?');
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        if (!$user) { http_response_code(404); echo 'User not found'; return; }

        // Aggregates
        $ocStmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE user_id=?');
        $ocStmt->execute([$id]);
        $ordersCount = (int)$ocStmt->fetchColumn();
        $tsStmt = $pdo->prepare('SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE user_id=?');
        $tsStmt->execute([$id]);
        $totalSpent = (float)$tsStmt->fetchColumn();

        // Orders list
        $orders = $pdo->prepare('SELECT id, order_date, total_amount FROM orders WHERE user_id=? ORDER BY id DESC');
        $orders->execute([$id]);
        $orders = $orders->fetchAll();

        $this->render('admin/user_detail', [
            'u' => $user,
            'ordersCount' => $ordersCount,
            'totalSpent' => $totalSpent,
            'orders' => $orders,
            'csrf' => $this->csrfToken(),
        ]);
    }

    // Sales analytics: top products and daily revenue
    public function analytics()
    {
        $this->requireAdmin();
        global $pdo;
        // Top selling products by quantity
        $topProducts = $pdo->query(
            'SELECT p.id, p.name,
                    COALESCE(SUM(oi.quantity),0) AS qty,
                    COALESCE(SUM(oi.quantity * oi.price),0) AS revenue
             FROM order_items oi
             JOIN products p ON p.id = oi.product_id
             GROUP BY p.id, p.name
             ORDER BY qty DESC
             LIMIT 10'
        )->fetchAll();

        // Product sales summary (with last order date)
        $productSummary = $pdo->query(
            'SELECT p.id, p.name,
                    COALESCE(SUM(oi.quantity),0) AS qty,
                    COALESCE(SUM(oi.quantity * oi.price),0) AS revenue,
                    MAX(o.order_date) AS last_order
             FROM order_items oi
             JOIN orders o ON o.id = oi.order_id
             JOIN products p ON p.id = oi.product_id
             GROUP BY p.id, p.name
             ORDER BY revenue DESC
             LIMIT 50'
        )->fetchAll();

        // Revenue by day (last 30 days)
        $daily = $pdo->query(
            "SELECT DATE(order_date) AS day,
                    COUNT(*) AS orders,
                    COALESCE(SUM(total_amount),0) AS revenue
             FROM orders
             WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
             GROUP BY DATE(order_date)
             ORDER BY day ASC"
        )->fetchAll();

        $this->render('admin/analytics', [
            'topProducts' => $topProducts,
            'productSummary' => $productSummary,
            'daily' => $daily,
        ]);
    }

    // Export the entire products table as a XAMPP/phpMyAdmin-importable .sql file
    public function exportProducts()
    {
        $this->requireAdmin();
        global $pdo;

        $filename = 'products_export_' . date('Ymd_His') . '.sql';
        header('Content-Type: application/sql; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo "-- Mio Grocery Store: products table export\n";
        echo "-- Generated: " . date('c') . "\n\n";
        echo "SET FOREIGN_KEY_CHECKS=0;\n";

        // Table DDL
        $row = $pdo->query('SHOW CREATE TABLE `products`')->fetch(\PDO::FETCH_NUM);
        $createSql = $row[1] ?? '';
        echo "DROP TABLE IF EXISTS `products`;\n";
        echo $createSql . ";\n\n";

        // Columns
        $cols = $pdo->query('SHOW COLUMNS FROM `products`')->fetchAll();
        $colNames = array_map(function($c){ return $c['Field']; }, $cols);
        $colList = '`' . implode('`,`', $colNames) . '`';

        // Data rows
        $rows = $pdo->query('SELECT * FROM `products`')->fetchAll();
        if ($rows && count($rows) > 0) {
            echo "LOCK TABLES `products` WRITE;\n";
            echo "INSERT INTO `products` (" . $colList . ") VALUES\n";
            $lines = [];
            foreach ($rows as $r) {
                $vals = [];
                foreach ($colNames as $cn) {
                    $v = $r[$cn];
                    if ($v === null) {
                        $vals[] = 'NULL';
                    } else {
                        $vals[] = $pdo->quote($v);
                    }
                }
                $lines[] = '(' . implode(',', $vals) . ')';
            }
            echo implode(",\n", $lines) . ";\n";
            echo "UNLOCK TABLES;\n\n";
        }

        echo "SET FOREIGN_KEY_CHECKS=1;\n";
        exit;
    }

    // Import products table data from an uploaded .sql dump
    public function importProducts()
    {
        $this->requireAdmin();
        global $pdo;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->verifyCsrf($_POST['csrf'] ?? '')) { die('Bad CSRF token'); }

            if (!isset($_FILES['sql_file']) || $_FILES['sql_file']['error'] !== UPLOAD_ERR_OK) {
                $this->setFlash('error', 'Upload failed. Please select a .sql file.');
                header('Location: ' . $this->baseUrl('admin/import-products'));
                return;
            }

            $file = $_FILES['sql_file'];
            $maxSize = 5 * 1024 * 1024; // 5MB
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if ($ext !== 'sql' || $file['size'] <= 0 || $file['size'] > $maxSize) {
                $this->setFlash('error', 'Invalid file. Only .sql up to 5MB allowed.');
                header('Location: ' . $this->baseUrl('admin/import-products'));
                return;
            }

            $sql = file_get_contents($file['tmp_name']);
            if ($sql === false) {
                $this->setFlash('error', 'Could not read uploaded file.');
                header('Location: ' . $this->baseUrl('admin/import-products'));
                return;
            }

            // Split statements by semicolon followed by any newline (Windows/Unix)
            $statements = preg_split('/;\s*\R/m', $sql);
            $executed = 0;
            try {
                // Disable FK checks for session to avoid constraint errors during DROP/INSERT
                $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
                foreach ($statements as $stmt) {
                    if (!is_string($stmt)) { continue; }
                    // Remove block comments /* ... */ and line comments starting with --
                    $noBlock = preg_replace('/\/\*.*?\*\//s', '', $stmt);
                    $lines = preg_split('/\R/', (string)$noBlock);
                    $cleanLines = [];
                    foreach ($lines as $line) {
                        if (preg_match('/^\s*--/', $line)) { continue; }
                        // Skip DELIMITER directives if present
                        if (preg_match('/^\s*DELIMITER\b/i', $line)) { continue; }
                        $cleanLines[] = $line;
                    }
                    $clean = trim(implode("\n", $cleanLines));
                    if ($clean === '') { continue; }
                    $pdo->exec($clean);
                    $executed++;
                }
                // Re-enable FK checks
                $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
                $this->setFlash('success', 'Import completed. Executed ' . $executed . ' statements.');
                header('Location: ' . $this->baseUrl('admin/dashboard'));
                return;
            } catch (\Exception $e) {
                // Ensure FK checks are re-enabled even on error
                try { $pdo->exec('SET FOREIGN_KEY_CHECKS=1'); } catch (\Exception $ie) {}
                $this->setFlash('error', 'Import failed: ' . $e->getMessage());
                header('Location: ' . $this->baseUrl('admin/import-products'));
                return;
            }
        }

        // GET: show simple upload form
        $this->render('admin/import_products', [ 'csrf' => $this->csrfToken() ]);
    }
}
