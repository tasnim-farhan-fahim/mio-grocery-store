<?php
class BaseController
{
    protected function render($view, $data = [])
    {
        $viewFile = __DIR__ . '/../views/' . $view . '.php';
        if (!file_exists($viewFile)) {
            echo 'View not found: ' . htmlspecialchars($viewFile);
            return;
        }
        $data['flash'] = $this->getFlash();
        $data['baseUrl'] = $this->baseUrl();
        $data['cartCount'] = array_sum($_SESSION['cart'] ?? []);
        extract($data);
        // Basic layout inclusion
        include __DIR__ . '/../views/layout/header.php';
        include $viewFile;
        include __DIR__ . '/../views/layout/footer.php';
    }

    // Removed unused view() alias; render() is used directly.

    protected function setFlash($type, $message)
    {
        if (!isset($_SESSION['flash'])) $_SESSION['flash'] = [];
        $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
    }

    protected function getFlash()
    {
        $msgs = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $msgs;
    }

    // CSRF helpers
    protected function csrfToken()
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    protected function verifyCsrf($token)
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    protected function requireLogin()
    {
        if (empty($_SESSION['user'])) {
            header('Location: /auth/login');
            exit;
        }
    }

    protected function requireAdmin()
    {
        $this->requireLogin();
        if ($_SESSION['user']['role'] !== 'admin') {
            http_response_code(403);
            echo '<h1>403 Forbidden</h1>';
            exit;
        }
    }

    protected function baseUrl($path = '')
    {
        $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
        $url = ($base === '/' ? '' : $base);
        if ($path !== '') {
            $url .= '/' . ltrim($path, '/');
        }
        return $url === '' ? '/' : $url;
    }
}
