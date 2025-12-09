<div class="page">
<h2>Add Product</h2>
<form method="post" action="<?= htmlspecialchars($baseUrl) ?>/product/add" class="product-form">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
    <label>Name<br><input type="text" name="name" required></label>
    <label>Category<br><select name="category_id">
        <option value="">-- None --</option>
        <?php foreach($cats as $c): ?>
            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['category_name']) ?></option>
        <?php endforeach; ?>
    </select></label>
    <label>Price<br><input type="number" step="0.01" name="price" required></label>
    <label>Stock<br><input type="number" name="stock_quantity" value="0" required></label>
    <label>Image URL<br><input type="url" name="image_url" placeholder="https://example.com/image.jpg"></label>
    <button type="submit">Add</button>
    <a href="<?= htmlspecialchars($baseUrl) ?>/products" class="back-link">Back</a>
</form>
</div>
