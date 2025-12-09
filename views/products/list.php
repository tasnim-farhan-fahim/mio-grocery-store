<div class="page">
<p class="welcome-message">Welcome To the Mio Grocery Store</p>
<div class="product-header">
    <h1>Products</h1>
    <form class="product-search" action="<?= htmlspecialchars($baseUrl) ?>/products" method="get">
        <input id="search-text" type="text" name="q" value="<?= htmlspecialchars($q ?? '') ?>" placeholder="Search products" autocomplete="off">
        <!-- Sort -->
         <label for="search-sort"> Filter:</label>
        <select id="search-sort" class="search-sort" name="sort" aria-label="Sort" onchange="this.form.submit()">
            <?php $sort = $sort ?? ''; ?>
            <option value="">Newest</option>
            <option value="name_asc" <?= $sort==='name_asc'?'selected':'' ?>>A–Z</option>
            <option value="name_desc" <?= $sort==='name_desc'?'selected':'' ?>>Z–A</option>
            <option value="price_asc" <?= $sort==='price_asc'?'selected':'' ?>>Low → High</option>
            <option value="price_desc" <?= $sort==='price_desc'?'selected':'' ?>>High → Low</option>
        </select>
        <select id="search-category" class="search-category" name="category_id" onchange="this.form.submit()">
            <option value="">All categories</option>
            <?php if (!empty($cats)) foreach($cats as $c): ?>
                <option value="<?= $c['id'] ?>" <?= isset($selectedCategory) && $selectedCategory==$c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['category_name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" style="display:none" aria-hidden="true">Apply</button>
    </form>
</div>
<?php if(!empty($_SESSION['user']) && $_SESSION['user']['role']==='admin'): ?>
    <p class="btn-add-top">
        <button type="button" class="btn-add js-open-add-product">Add Product</button>
        <button type="button" class="btn-add js-open-add-category">Add Category</button>
        <a class="btn-add" href="<?= htmlspecialchars($baseUrl) ?>/admin">Go to Dashboard</a>
    </p>
<?php elseif(!empty($_SESSION['user'])): ?>
    <p class="btn-add-top">
        <a class="btn-add" href="<?= htmlspecialchars($baseUrl) ?>/dashboard">Go to Dashboard</a>
    </p>
<?php endif; ?>
<div class="product-grid">
<?php foreach($products as $p): ?>
        <div class="product-card">
                <?php $img = $p['image_url'] ?? ''; ?>
                <div class="thumb">
                        <?php if($img): ?>
                            <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                        <?php else: ?>
                            <div class="no-image">No Image</div>
                        <?php endif; ?>
                </div>
                <h3><?= htmlspecialchars($p['name']) ?></h3>
                <p class="category"><?= htmlspecialchars($p['category_name']) ?: 'Uncategorized' ?></p>
                <p class="price">$<?= number_format($p['price'],2) ?></p>
                <p class="stock">Stock: <?= (int)$p['stock_quantity'] ?></p>
        <div class="actions">
            <form method="post" action="<?= htmlspecialchars($baseUrl) ?>/add-to-cart" class="add-cart-inline qty-control">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                <div class="qty-buttons">
                    <button type="button" class="qty-btn decrement" aria-label="Decrease">−</button>
                    <input type="number" name="quantity" value="1" min="1">
                    <button type="button" class="qty-btn increment" aria-label="Increase">+</button>
                </div>
                <div>
                    <button type="submit" class="btn-addToCart">🛒 Add to Cart</button>
                </div>
            </form>
            <?php if(!empty($_SESSION['user']) && $_SESSION['user']['role']==='admin'): ?>
                <div class="btn-admin-actions">
                    <button type="button"
                        class="btn-edit js-open-edit-product"
                        data-id="<?= $p['id'] ?>"
                        data-name="<?= htmlspecialchars($p['name']) ?>"
                        data-category-id="<?= (int)($p['category_id'] ?? 0) ?>"
                        data-price="<?= htmlspecialchars($p['price']) ?>"
                        data-stock="<?= (int)$p['stock_quantity'] ?>"
                        data-image="<?= htmlspecialchars($p['image_url'] ?? '') ?>">Edit</button>
                        <a class="btn-delete" href="<?= htmlspecialchars($baseUrl) ?>/product/delete?id=<?= $p['id'] ?>&csrf=<?= htmlspecialchars($csrf) ?>" onclick="return confirm('Delete this product?')">Delete</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>
</div>

<a class="floating-cart" href="<?= htmlspecialchars($baseUrl) ?>/cart" aria-label="View cart">
    <span class="icon" aria-hidden="true">🛒</span>
    <span class="float-count" aria-label="Items in cart"><?= (int)($cartCount ?? 0) ?></span>
</a>
</div>

<!-- Modals -->
<?php if(!empty($_SESSION['user']) && $_SESSION['user']['role']==='admin'): ?>
<div class="modal-overlay" hidden></div>
<div class="modal" id="modal-add-product" hidden>
    <div class="modal-header"><h3>Add Product</h3><button class="modal-close" aria-label="Close">✖</button></div>
    <form method="post" action="<?= htmlspecialchars($baseUrl) ?>/product/add" class="modal-form" id="form-add-product">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
        <label>Name<br><input type="text" name="name" required></label>
        <label>Category<br>
            <select name="category_id">
                <option value="">-- None --</option>
                <?php if (!empty($cats)) foreach($cats as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['category_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Price<br><input type="number" step="0.01" name="price" required></label>
        <label>Stock<br><input type="number" name="stock_quantity" value="0" required></label>
        <label>Image URL<br><input type="url" name="image_url" placeholder="https://example.com/image.jpg"></label>
        <div class="modal-actions"><button type="submit">Add</button></div>
    </form>
</div>

<div class="modal" id="modal-edit-product" hidden>
    <div class="modal-header"><h3>Edit Product</h3><button class="modal-close" aria-label="Close">✖</button></div>
    <form method="post" action="<?= htmlspecialchars($baseUrl) ?>/product/edit" class="modal-form" id="form-edit-product">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
        <input type="hidden" name="id" value="">
        <label>Name<br><input type="text" name="name" required></label>
        <label>Category<br>
            <select name="category_id">
                <option value="">-- None --</option>
                <?php if (!empty($cats)) foreach($cats as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['category_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Price<br><input type="number" step="0.01" name="price" required></label>
        <label>Stock<br><input type="number" name="stock_quantity" value="0" required></label>
        <label>Image URL<br><input type="url" name="image_url" placeholder="https://example.com/image.jpg"></label>
        <div class="modal-actions"><button type="submit">Update</button></div>
    </form>
</div>

<div class="modal" id="modal-add-category" hidden>
    <div class="modal-header"><h3>Add Category</h3><button class="modal-close" aria-label="Close">✖</button></div>
    <form method="post" action="<?= htmlspecialchars($baseUrl) ?>/product/add-category" class="modal-form" id="form-add-category">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
        <label>Category Name<br><input type="text" name="category_name" required></label>
        <div class="modal-actions"><button type="submit">Add</button></div>
    </form>
</div>
<?php endif; ?>
</div>
