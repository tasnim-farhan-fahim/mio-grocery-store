<div class="page">
  <h2>User Carts</h2>
  <?php if (empty($users)): ?>
    <p>No users have items in carts.</p>
  <?php else: ?>
    <?php foreach($users as $u): $uid=(int)$u['id']; $items=$itemsByUser[$uid] ?? []; ?>
      <div class="user-cart" style="margin-bottom:1rem;">
        <h3><?= htmlspecialchars($u['name']) ?> <small style="color:#666;">(<?= htmlspecialchars($u['email']) ?>) — <?= (int)$u['items'] ?> item(s)</small></h3>
        <?php if ($items): ?>
          <table class="cart-table">
            <thead><tr><th>Product</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr></thead>
            <tbody>
            <?php foreach($items as $it): $sub=$it['quantity']*$it['price']; ?>
              <tr>
                <td><?= htmlspecialchars($it['name']) ?></td>
                <td><?= (int)$it['quantity'] ?></td>
                <td>$<?= number_format($it['price'],2) ?></td>
                <td>$<?= number_format($sub,2) ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
