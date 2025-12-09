<div class="page">
  <h2>Orders</h2>
  <table class="cart-table">
    <thead><tr><th>ID</th><th>Date</th><th>User</th><th>Email</th><th>Items</th><th>Total</th></tr></thead>
    <tbody>
    <?php foreach($orders as $o): ?>
      <tr>
        <td>#<?= (int)$o['id'] ?></td>
        <td><?= htmlspecialchars($o['order_date']) ?></td>
        <td><?= htmlspecialchars($o['user_name'] ?? 'Guest') ?></td>
        <td><?= htmlspecialchars($o['user_email'] ?? '') ?></td>
        <td><?= (int)$o['items'] ?></td>
        <td>$<?= number_format($o['total_amount'],2) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
