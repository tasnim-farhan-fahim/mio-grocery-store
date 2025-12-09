<?php if (!isset($baseUrl)) { $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); $baseUrl = ($base === '/' ? '' : $base) ?: '/'; } ?>
<div class="page">
<h2>Invoice #<?= (int)$order['id'] ?></h2>
<p>Date: <?= htmlspecialchars($order['order_date']) ?></p>
<?php 
    // Prefer logged-in user's data, fallback to order data if present
    $customerName = '';
    $customerEmail = '';

    if (isset($_SESSION['user'])) {
        $u = $_SESSION['user'];
        if (!empty($u['name'])) { $customerName = $u['name']; }
        elseif (!empty($u['username'])) { $customerName = $u['username']; }
        elseif (!empty($u['user_name'])) { $customerName = $u['user_name']; }
        if (!empty($u['email'])) { $customerEmail = $u['email']; }
    }

    // Fallbacks from $order payload if provided by controller
    if ($customerName === '') {
        if (isset($order['user_name']) && $order['user_name'] !== '') {
            $customerName = $order['user_name'];
        } elseif (isset($order['username']) && $order['username'] !== '') {
            $customerName = $order['username'];
        } elseif (isset($order['name']) && $order['name'] !== '') {
            $customerName = $order['name'];
        }
    }
    if ($customerEmail === '' && isset($order['email']) && $order['email'] !== '') {
        $customerEmail = $order['email'];
    }

    if ($customerName !== ''): ?>
    <p>Customer: <?= htmlspecialchars($customerName) ?></p>
    <?php endif; if ($customerEmail !== ''): ?>
    <p>Email: <?= htmlspecialchars($customerEmail) ?></p>
<?php endif; ?>
<table class="cart-table">
    <thead><tr><th>Product</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr></thead>
    <tbody>
    <?php $sum=0; foreach($items as $it): $sub=$it['quantity']*$it['price']; $sum+=$sub; ?>
    <tr>
        <td><?= htmlspecialchars($it['name']) ?></td>
        <td><?= (int)$it['quantity'] ?></td>
        <td>$<?= number_format($it['price'],2) ?></td>
        <td>$<?= number_format($sub,2) ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot><tr><td colspan="3">Total</td><td>$<?= number_format($sum,2) ?></td></tr></tfoot>
    </table>
    <p><a href="<?= htmlspecialchars($baseUrl) ?>/products">Continue shopping</a></p>
</div>
