<div class="page">
  <h2>User Details</h2>
  <div class="card" style="padding:1rem;margin-bottom:1rem;">
    <div><strong>ID:</strong> <?= (int)$u['id'] ?></div>
    <div><strong>Name:</strong> <?= htmlspecialchars($u['name']) ?></div>
    <div><strong>Email:</strong> <?= htmlspecialchars($u['email']) ?></div>
    <div><strong>Role:</strong> <?= htmlspecialchars($u['role']) ?></div>
    <div style="margin-top:.5rem;"><strong>Orders:</strong> <?= (int)$ordersCount ?> &nbsp; <strong>Total Spent:</strong> $<?= number_format($totalSpent,2) ?></div>
  </div>

  <div class="card" style="padding:1rem;margin-bottom:1rem;">
    <h3 style="margin-top:0;">Manage Role</h3>
    <form method="post" action="">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
      <label>Role
        <select name="role">
          <option value="customer" <?= $u['role']==='customer'?'selected':'' ?>>Customer</option>
          <option value="admin" <?= $u['role']==='admin'?'selected':'' ?>>Admin</option>
        </select>
      </label>
      <button type="submit" class="btn">Update</button>
    </form>
  </div>

  <h3>Order History</h3>
  <?php if (empty($orders)): ?>
    <p>No orders yet.</p>
  <?php else: ?>
    <table class="cart-table">
      <thead><tr><th>ID</th><th>Date</th><th>Total</th><th>Invoice</th></tr></thead>
      <tbody>
      <?php foreach($orders as $o): ?>
        <tr>
          <td>#<?= (int)$o['id'] ?></td>
          <td><?= htmlspecialchars($o['order_date']) ?></td>
          <td>$<?= number_format($o['total_amount'],2) ?></td>
          <td><a class="btn" href="<?= htmlspecialchars($this->baseUrl('invoice')) ?>?id=<?= (int)$o['id'] ?>" target="_blank">View</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
