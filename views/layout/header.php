<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Mio Grocery Store</title>
  <link rel="icon" type="image/png" href="https://logos.textgiraffe.com/logos/logo-name/Mio-designstyle-smoothie-m.png">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl) ?>/assets/css/style.css">
  <script src="<?= htmlspecialchars($baseUrl) ?>/assets/js/script.js" defer></script>
</head>
<?php
  $currentPathForBody = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
  $isDashboardPage = preg_match('#/(admin(/dashboard)?|dashboard)$#', $currentPathForBody) || $currentPathForBody === '/admin' || $currentPathForBody === '/dashboard';
?>
<body class="<?= $isDashboardPage ? 'is-dashboard' : '' ?>">
<header class="site-header">
  <div class="brand"><a href="<?= htmlspecialchars($baseUrl) ?>/products"><span class="brand-icon" aria-hidden="true">🥕</span><span class="brand-text">Mio Grocery Store</span></a></div>
  <button class="nav-toggle" aria-label="Toggle Navigation">☰</button>
  <nav class="site-nav">
    <ul>
      <?php
        $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $productsActive = preg_match('#/products$#', $currentPath);
        $cartActive = preg_match('#/cart$#', $currentPath);
        // removed add product nav option
        $loginActive = preg_match('#/auth/login$#', $currentPath);
        $registerActive = preg_match('#/auth/register$#', $currentPath);
        // Treat any admin/* or dashboard/* route as active for the user link
        $dashboardActive = (bool)preg_match('#/(admin|dashboard)(/|$)#', $currentPath);
      ?>
      <li><a class="<?= $productsActive ? 'active' : '' ?>" href="<?= htmlspecialchars($baseUrl) ?>/products"><span class="nav-icon" aria-hidden="true">🛍️</span><span class="nav-text">Products</span></a></li>
      <li><a class="<?= $cartActive ? 'active' : '' ?>" href="<?= htmlspecialchars($baseUrl) ?>/cart"><span class="nav-icon" aria-hidden="true">🛒</span><span class="nav-text">Cart</span><span class="cart-count" aria-label="Items in cart"><?= (int)$cartCount ?></span></a></li>
      <?php // removed Add Product nav item (now handled by modal on products page) ?>
      <?php if (!empty($_SESSION['user'])): ?>
        <li class="user">
          <a class="user-dashboard-link <?= $dashboardActive ? 'active' : '' ?>" href="<?= htmlspecialchars($baseUrl) ?>/<?= $_SESSION['user']['role'] === 'admin' ? 'admin' : 'dashboard' ?>">
            Hello <?= htmlspecialchars($_SESSION['user']['name']) ?>
            <span class="dash-icon" aria-hidden="true"><?= $_SESSION['user']['role'] === 'admin' ? '📊' : '🏠' ?></span>
          </a>
        </li>
        <li><a href="<?= htmlspecialchars($baseUrl) ?>/auth/logout"><span class="nav-icon" aria-hidden="true">🚪</span><span class="nav-text">Logout</span></a></li>
      <?php else: ?>
        <li><a class="<?= $loginActive ? 'active' : '' ?>" href="<?= htmlspecialchars($baseUrl) ?>/auth/login"><span class="nav-icon" aria-hidden="true">🔐</span><span class="nav-text">Login</span></a></li>
        <li><a class="<?= $registerActive ? 'active' : '' ?>" href="<?= htmlspecialchars($baseUrl) ?>/auth/register"><span class="nav-icon" aria-hidden="true">🆕</span><span class="nav-text">Register</span></a></li>
      <?php endif; ?>
    </ul>
  </nav>
</header>
<main class="content">
<?php if(!empty($flash)): ?>
  <div class="flash-wrapper">
    <?php foreach($flash as $f): ?>
      <div class="flash <?= htmlspecialchars($f['type']) ?>"><?= htmlspecialchars($f['message']) ?></div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
