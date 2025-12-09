<div class="page">
  <h2>Users</h2>
  <table class="cart-table">
    <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Orders</th><th>Total Spent</th><th>Cart Items</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach($users as $u): ?>
      <tr>
        <td><?= (int)$u['id'] ?></td>
        <td><?= htmlspecialchars($u['name']) ?></td>
        <td><?= htmlspecialchars($u['email']) ?></td>
        <td><?= htmlspecialchars($u['role']) ?></td>
        <td><?= (int)$u['orders_count'] ?></td>
        <td>$<?= number_format($u['total_spent'],2) ?></td>
        <td><?= (int)$u['cart_items'] ?></td>
        <td><a class="btn" href="<?= htmlspecialchars($this->baseUrl('admin/user')) ?>?id=<?= (int)$u['id'] ?>">View</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
