<div class="page">
<h2>Your Cart</h2>
<?php if (empty($items)): ?>
    <p>Cart is empty. <a href="<?= htmlspecialchars($baseUrl) ?>/products">Continue shopping</a></p>
<?php else: ?>
    <table class="cart-table">
        <thead><tr><th>Product</th><th>Price</th><th>Qty</th><th>Subtotal</th><th>Actions</th></tr></thead>
        <tbody>
        <?php $total=0; foreach($items as $pid => $row): $total+=$row['subtotal']; ?>
            <tr>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td>$<?= number_format($row['price'],2) ?></td>
                <td>
                    <form method="post" action="<?= htmlspecialchars($baseUrl) ?>/update-cart" class="inline qty-control">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                        <input type="hidden" name="product_id" value="<?= $pid ?>">
                        <button type="button" class="qty-btn decrement" aria-label="Decrease">−</button>
                        <input type="number" name="quantity" value="<?= $row['qty'] ?>" min="0">
                        <button type="button" class="qty-btn increment" aria-label="Increase">+</button>
                    </form>
                </td>
                <td>$<?= number_format($row['subtotal'],2) ?></td>
                <td>
                    <form method="post" action="<?= htmlspecialchars($baseUrl) ?>/update-cart" class="inline">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                        <input type="hidden" name="product_id" value="<?= $pid ?>">
                        <input type="hidden" name="quantity" value="0">
                        <button type="submit" class="remove">Remove</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr><td colspan="3">Total</td><td colspan="2">$<?= number_format($total,2) ?></td></tr></tfoot>
    </table>
    <form method="post" action="<?= htmlspecialchars($baseUrl) ?>/checkout" class="checkout-form">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
        <button type="submit">Checkout</button>
    </form>
<?php endif; ?>
</div>
