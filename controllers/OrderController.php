<?php
require_once __DIR__ . '/BaseController.php';

class OrderController extends BaseController
{
    private function saveCartForLoggedInUser()
    {
        if (empty($_SESSION['user']['id'])) return;
        global $pdo;
        $uid = (int)$_SESSION['user']['id'];
        $cart = $_SESSION['cart'] ?? [];
        // Upsert current items
        if ($cart) {
            $ins = $pdo->prepare('INSERT INTO user_cart_items (user_id, product_id, quantity) VALUES (?,?,?) ON DUPLICATE KEY UPDATE quantity=VALUES(quantity)');
            foreach ($cart as $pid => $qty) {
                $ins->execute([$uid, (int)$pid, (int)$qty]);
            }
            // Remove items no longer in session cart
            $ids = implode(',', array_map('intval', array_keys($cart)));
            $pdo->exec('DELETE FROM user_cart_items WHERE user_id=' . $uid . ' AND product_id NOT IN (' . ($ids ?: '0') . ')');
        } else {
            // If cart empty, clear all saved items for user
            $del = $pdo->prepare('DELETE FROM user_cart_items WHERE user_id=?');
            $del->execute([$uid]);
        }
    }

    // Removed unused loadCartForLoggedInUser(); persistence handled via saveCartForLoggedInUser() and login merge.

    public function cart()
    {
        global $pdo;
        $cart = $_SESSION['cart'] ?? [];
        $details = [];
        if ($cart) {
            $ids = implode(',', array_map('intval', array_keys($cart)));
            $rows = $pdo->query('SELECT id,name,price FROM products WHERE id IN (' . $ids . ')')->fetchAll();
            foreach ($rows as $row) {
                $pid = $row['id'];
                $details[$pid] = [
                    'name' => $row['name'],
                    'price' => $row['price'],
                    'qty' => $cart[$pid],
                    'subtotal' => $cart[$pid] * $row['price']
                ];
            }
        }
        $this->render('orders/cart', [
            'items' => $details,
            'csrf' => $this->csrfToken(),
        ]);
    }

    // POST: add to cart
    public function addToCart()
    {
        global $pdo;
        $id = $_POST['product_id'] ?? null;
        $qty = max(1, (int)($_POST['quantity'] ?? 1));
        if (! $this->verifyCsrf($_POST['csrf'] ?? '')) { die('Bad CSRF token'); }
        if ($id) {
            if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
            if (isset($_SESSION['cart'][$id])) {
                $_SESSION['cart'][$id] += $qty;
            } else {
                $_SESSION['cart'][$id] = $qty;
            }
            // Fetch product name for user-friendly flash message
            $stmt = $pdo->prepare('SELECT name FROM products WHERE id = ?');
            $stmt->execute([$id]);
            $prod = $stmt->fetch();
            $pname = $prod ? $prod['name'] : 'Item';
            $message = sprintf('%d %s added to your cart', $qty, $pname);
            $this->setFlash('success', $message);
            // Persist to DB for logged-in user
            $this->saveCartForLoggedInUser();
        }
        // AJAX detection
        $isAjax = (
            (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
            || (!empty($_POST['ajax']) && $_POST['ajax'] == '1')
        );
        if ($isAjax) {
            $count = array_sum($_SESSION['cart'] ?? []);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'cartCount' => $count,
                'message' => isset($message) ? $message : 'Added'
            ]);
            exit;
        }
        // Non-AJAX fallback: redirect
        header('Location: ' . $this->baseUrl('products'));
        exit;
    }

    public function updateCart()
    {
        if (! $this->verifyCsrf($_POST['csrf'] ?? '')) { die('Bad CSRF token'); }
        $pid = $_POST['product_id'] ?? null;
        $qty = (int)($_POST['quantity'] ?? 0);
        $message = null;
        if ($pid && isset($_SESSION['cart'][$pid])) {
            if ($qty <= 0) {
                // Fetch product name for friendly message
                global $pdo;
                $stmt = $pdo->prepare('SELECT name FROM products WHERE id = ?');
                $stmt->execute([$pid]);
                $prod = $stmt->fetch();
                $pname = $prod ? $prod['name'] : 'Item';
                unset($_SESSION['cart'][$pid]);
                $message = sprintf('%s removed from your cart', $pname);
                $this->setFlash('info', $message);
            } else {
                $_SESSION['cart'][$pid] = $qty;
                $message = 'Quantity updated';
                $this->setFlash('success', $message);
            }
            // Persist to DB for logged-in user
            $this->saveCartForLoggedInUser();
        }
        // AJAX detection
        $isAjax = (
            (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (!empty($_POST['ajax']) && $_POST['ajax'] == '1')
        );
        if ($isAjax) {
            $count = array_sum($_SESSION['cart'] ?? []);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'cartCount' => $count,
                'message' => $message ?: 'Updated'
            ]);
            exit;
        }
        header('Location: ' . $this->baseUrl('cart'));
        exit;
    }

    public function checkout()
    {
        global $pdo;
        $cart = $_SESSION['cart'] ?? [];
        if (!$cart) { header('Location: ' . $this->baseUrl('products')); exit; }
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ! $this->verifyCsrf($_POST['csrf'] ?? '')) { die('Bad CSRF token'); }

        // Create an order; attach to logged-in user if available
        $pdo->beginTransaction();
        try {
            $total = 0;
            $userId = !empty($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;
            // Insert with user_id when column exists
            try {
                $stmt = $pdo->prepare('INSERT INTO orders (customer_id,user_id,order_date,total_amount) VALUES (?,?,?,?)');
                $stmt->execute([null, $userId, date('Y-m-d H:i:s'), 0]);
            } catch (Exception $e) {
                // Fallback for legacy schema without user_id
                $stmt = $pdo->prepare('INSERT INTO orders (customer_id,order_date,total_amount) VALUES (?,?,?)');
                $stmt->execute([null, date('Y-m-d H:i:s'), 0]);
            }
            $order_id = $pdo->lastInsertId();

            $pstmt = $pdo->prepare('SELECT id,price,stock_quantity FROM products WHERE id=?');
            $ipstmt = $pdo->prepare('INSERT INTO order_items (order_id,product_id,quantity,price) VALUES (?,?,?,?)');
            $up = $pdo->prepare('UPDATE products SET stock_quantity = stock_quantity - ? WHERE id=?');

            foreach ($cart as $pid => $qty) {
                $pstmt->execute([$pid]);
                $product = $pstmt->fetch();
                if (!$product) continue; // skip missing product
                // Prevent overselling: clamp qty to available stock
                $available = (int)$product['stock_quantity'];
                if ($available <= 0) { continue; }
                $useQty = min($available, (int)$qty);
                if ($useQty <= 0) continue;
                $ipstmt->execute([$order_id, $pid, $useQty, $product['price']]);
                $up->execute([$useQty, $pid]);
                $total += $product['price'] * $useQty;
            }

            $pdo->prepare('UPDATE orders SET total_amount=? WHERE id=?')->execute([$total, $order_id]);
            $pdo->commit();
            unset($_SESSION['cart']);
            // Clear saved cart for logged-in user after successful checkout
            if (!empty($_SESSION['user']['id'])) {
                $uid = (int)$_SESSION['user']['id'];
                $del = $pdo->prepare('DELETE FROM user_cart_items WHERE user_id=?');
                $del->execute([$uid]);
            }
            header('Location: ' . $this->baseUrl('invoice') . '?id=' . $order_id);
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $this->setFlash('error','Checkout failed: ' . $e->getMessage());
            header('Location: ' . $this->baseUrl('cart'));
            exit;
        }
    }

    public function invoice()
    {
        global $pdo;
        $id = $_GET['id'] ?? null;
        if (!$id) { header('Location: ' . $this->baseUrl('products')); exit; }
        $order = $pdo->prepare('SELECT * FROM orders WHERE id=?');
        $order->execute([$id]);
        $order = $order->fetch();
        $items = $pdo->prepare('SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id=p.id WHERE oi.order_id=?');
        $items->execute([$id]);
        $items = $items->fetchAll();
        $this->render('orders/invoice', [ 'order' => $order, 'items' => $items ]);
    }
}
