<div class="page">
  <h2>My Dashboard</h2>
  <div class="stats-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin:.5rem 0 1rem;">
    <div class="card"><h4>Orders</h4><p><?= (int)$ordersCount ?></p></div>
    <div class="card"><h4>Total Spent</h4><p>$<?= number_format($totalSpent,2) ?></p></div>
  </div>

  <h3>Recent Orders</h3>
  <?php if (empty($recent)): ?>
    <p>No orders yet.</p>
  <?php else: ?>
  <table class="cart-table">
    <thead><tr><th>ID</th><th>Date</th><th>Total</th></tr></thead>
    <tbody>
    <?php foreach($recent as $o): ?>
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
