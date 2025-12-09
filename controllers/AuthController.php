<?php
require_once __DIR__ . '/BaseController.php';

class AuthController extends BaseController
{
    public function register()
    {
        global $pdo;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->verifyCsrf($_POST['csrf'] ?? '')) { die('Bad CSRF token'); }
            $name = $_POST['name'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $role = 'customer';
            // Allow role assignment only if current user is admin
            if (!empty($_SESSION['user']) && $_SESSION['user']['role']==='admin' && !empty($_POST['role'])) {
                $role = $_POST['role'] === 'admin' ? 'admin' : 'customer';
            }

            if ($name && $email && $password) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                try {
                    $stmt = $pdo->prepare('INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)');
                    $stmt->execute([$name, $email, $hash, $role]);
                    $this->setFlash('success','Registration successful. Please login.');
                    header('Location: ' . $this->baseUrl('auth/login'));
                    exit;
                } catch (Exception $e) {
                    $error = 'Registration failed (duplicate email?)';
                }
            }
        }
        $this->render('auth/register', [ 'error' => $error ?? null, 'csrf' => $this->csrfToken() ]);
    }

    public function login()
    {
        global $pdo;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->verifyCsrf($_POST['csrf'] ?? '')) { die('Bad CSRF token'); }
            // Allow login via email or username (stored in `name`)
            $identity = trim($_POST['identity'] ?? ($_POST['email'] ?? ''));
            $password = $_POST['password'] ?? '';
            $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? OR name = ? LIMIT 1');
            $stmt->execute([$identity, $identity]);
            $user = $stmt->fetch();
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user'] = ['id' => $user['id'], 'name' => $user['name'], 'role' => $user['role']];
                // Merge any session cart with user's saved cart
                try {
                    $sessCart = $_SESSION['cart'] ?? [];
                    $stmtC = $pdo->prepare('SELECT product_id, quantity FROM user_cart_items WHERE user_id=?');
                    $stmtC->execute([$user['id']]);
                    $saved = [];
                    foreach ($stmtC->fetchAll() as $r) { $saved[(int)$r['product_id']] = (int)$r['quantity']; }
                    // Merge quantities
                    foreach ($sessCart as $pid => $qty) {
                        if (isset($saved[$pid])) $saved[$pid] += (int)$qty; else $saved[$pid] = (int)$qty;
                    }
                    $_SESSION['cart'] = $saved;
                    // Upsert merged into DB
                    $pdo->prepare('DELETE FROM user_cart_items WHERE user_id=?')->execute([$user['id']]);
                    if (!empty($saved)) {
                        $ins = $pdo->prepare('INSERT INTO user_cart_items (user_id, product_id, quantity) VALUES (?,?,?)');
                        foreach ($saved as $pid => $qty) { $ins->execute([$user['id'], (int)$pid, (int)$qty]); }
                    }
                } catch (Exception $e) {
                    // Ignore cart merge errors; proceed with login
                }
                $this->setFlash('success','Logged in successfully');
                header('Location: ' . $this->baseUrl('products'));
                exit;
            }
            $error = 'Invalid credentials';
        }
        $this->render('auth/login', [ 'error' => $error ?? null, 'csrf' => $this->csrfToken() ]);
    }

    public function logout()
    {
        // Clear session and ensure any cart cookie is removed
        session_unset();
        session_destroy();
        header('Location: ' . $this->baseUrl('auth/login'));
        exit;
    }
}
