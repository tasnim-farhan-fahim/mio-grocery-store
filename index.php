<?php
// Simple front controller / router
session_start();

require_once __DIR__ . '/config/db.php';

// Basic autoload for controllers and models
spl_autoload_register(function ($class) {
    $paths = [__DIR__ . '/controllers/' . $class . '.php', __DIR__ . '/models/' . $class . '.php'];
    foreach ($paths as $file) {
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
if ($base !== '' && strpos($uri, $base) === 0) {
    $uri = substr($uri, strlen($base));
}
$uri = trim($uri, '/');

// Very small router
switch ($uri) {
    case '':
    case 'products':
        $c = new ProductController();
        $c->index();
        break;
    case 'product/add':
        $c = new ProductController();
        $c->add();
        break;
    case 'product/edit':
        $c = new ProductController();
        $c->edit();
        break;
    case 'product/add-category':
        $c = new ProductController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') { $c->addCategory(); }
        else { http_response_code(405); echo 'Method not allowed'; }
        break;
    case 'product/categories': // JSON list
        $c = new ProductController();
        $c->categories();
        break;
    case 'auth/login':
        $c = new AuthController();
        $c->login();
        break;
    case 'auth/register':
        $c = new AuthController();
        $c->register();
        break;
    case 'auth/logout':
        $c = new AuthController();
        $c->logout();
        break;
    case 'cart':
        $c = new OrderController();
        $c->cart();
        break;
    case 'add-to-cart':
        $c = new OrderController();
        // handle POST add to cart
        if ($_SERVER['REQUEST_METHOD'] === 'POST') $c->addToCart();
        else { http_response_code(405); echo 'Method not allowed'; }
        break;
    case 'update-cart':
        $c = new OrderController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') $c->updateCart();
        else { http_response_code(405); echo 'Method not allowed'; }
        break;
    case 'checkout':
        $c = new OrderController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') $c->checkout(); else {
            // simple redirect to cart for GET
            $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
            header('Location: ' . ($base === '/' ? '' : $base) . '/cart');
        }
        break;
    case 'invoice':
        $c = new OrderController();
        $c->invoice();
        break;
    case 'product/delete':
        $c = new ProductController();
        $c->delete();
        break;
    // Admin routes
    case 'admin':
    case 'admin/dashboard':
        $c = new AdminController();
        $c->dashboard();
        break;
    case 'admin/users':
        $c = new AdminController();
        $c->users();
        break;
    case 'admin/user':
        $c = new AdminController();
        $c->user();
        break;
    case 'admin/orders':
        $c = new AdminController();
        $c->orders();
        break;
    case 'admin/carts':
        $c = new AdminController();
        $c->carts();
        break;
    case 'admin/analytics':
        $c = new AdminController();
        $c->analytics();
        break;
    // User routes
    case 'dashboard':
        $c = new UserController();
        $c->dashboard();
        break;
    case 'my/orders':
        $c = new UserController();
        $c->orders();
        break;
    default:
        http_response_code(404);
        echo "<h1>404 Not Found</h1>";
}
