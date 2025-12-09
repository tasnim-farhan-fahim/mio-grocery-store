<div class="page">
<h2>Edit Product</h2>
<form method="post" action="<?= htmlspecialchars($baseUrl) ?>/product/edit?id=<?= $product['id'] ?>" class="product-form">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
    <label>Name<br><input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>" required></label>
    <label>Category<br><select name="category_id">
        <option value="">-- None --</option>
        <?php foreach($cats as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $c['id']==$product['category_id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['category_name']) ?></option>
        <?php endforeach; ?>
    </select></label>
    <label>Price<br><input type="number" step="0.01" name="price" value="<?= $product['price'] ?>" required></label>
    <label>Stock<br><input type="number" name="stock_quantity" value="<?= $product['stock_quantity'] ?>" required></label>
        <label>Image URL<br><input type="url" name="image_url" value="<?= htmlspecialchars($product['image_url'] ?? '') ?>" placeholder="https://example.com/image.jpg"></label>
    <button type="submit">Save</button>
    <a href="<?= htmlspecialchars($baseUrl) ?>/products" class="back-link">Cancel</a>
</form>
</div>
