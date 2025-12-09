<div class="page">
  <h2>Admin Dashboard</h2>
  <div class="stats-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin:.5rem 0 1rem;">
    <div class="card"><h4>Users</h4><p><?= (int)$summary['users'] ?></p></div>
    <div class="card"><h4>Products</h4><p><?= (int)$summary['products'] ?></p></div>
    <div class="card"><h4>Orders</h4><p><?= (int)$summary['orders'] ?></p></div>
    <div class="card"><h4>Revenue</h4><p>$<?= number_format($summary['revenue'],2) ?></p></div>
    <div class="card"><h4>Orders Today</h4><p><?= (int)$summary['ordersToday'] ?></p></div>
    <div class="card"><h4>Revenue Today</h4><p>$<?= number_format($summary['revenueToday'],2) ?></p></div>
  </div>

  <div class="admin-actions-bar">
    <a class="btn lg" href="<?= htmlspecialchars($this->baseUrl('admin/users')) ?>">👤 Manage Users</a>
    <a class="btn lg" href="<?= htmlspecialchars($this->baseUrl('products')) ?>">🛍️ Manage Products</a>
    <a class="btn lg secondary" href="<?= htmlspecialchars($this->baseUrl('admin/orders')) ?>">📦 View Orders</a>
    <a class="btn lg light" href="<?= htmlspecialchars($this->baseUrl('admin/analytics')) ?>">📊 Sales Analytics</a>
  </div>

  <h3>Recent Orders</h3>
  <table class="cart-table">
    <thead><tr><th>ID</th><th>Date</th><th>User</th><th>Email</th><th>Total</th></tr></thead>
    <tbody>
    <?php foreach($recentOrders as $o): ?>
      <tr>
        <td>#<?= (int)$o['id'] ?></td>
        <td><?= htmlspecialchars($o['order_date']) ?></td>
        <td><?= htmlspecialchars($o['user_name'] ?? 'Guest') ?></td>
        <td><?= htmlspecialchars($o['user_email'] ?? '') ?></td>
        <td>$<?= number_format($o['total_amount'],2) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
