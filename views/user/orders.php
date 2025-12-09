<div class="page">
  <h2>My Orders</h2>
  <?php if (empty($orders)): ?>
    <p>You don't have any orders yet.</p>
  <?php else: ?>
  <table class="cart-table">
    <thead><tr><th>ID</th><th>Date</th><th>Total</th></tr></thead>
    <tbody>
    <?php foreach($orders as $o): ?>
      <tr>
        <td>#<?= (int)$o['id'] ?></td>
        <td><?= htmlspecialchars($o['order_date']) ?></td>
        <td>$<?= number_format($o['total_amount'],2) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>
