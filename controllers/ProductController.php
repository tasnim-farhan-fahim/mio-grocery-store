<?php
require_once __DIR__ . '/BaseController.php';

class ProductController extends BaseController
{
    public function index()
    {
        global $pdo;
        // Filters: search by name and category
        $q = trim($_GET['q'] ?? '');
        $cat = $_GET['category_id'] ?? '';
        $sort = $_GET['sort'] ?? '';
        $priceMin = isset($_GET['price_min']) && $_GET['price_min'] !== '' ? (float)$_GET['price_min'] : null;
        $priceMax = isset($_GET['price_max']) && $_GET['price_max'] !== '' ? (float)$_GET['price_max'] : null;
        $sql = 'SELECT p.*, c.category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id';
        $conds = [];
        $params = [];
        if ($q !== '') { $conds[] = 'p.name LIKE ?'; $params[] = '%'.$q.'%'; }
        if ($cat !== '' && $cat !== null) { $conds[] = 'p.category_id = ?'; $params[] = $cat; }
        if ($priceMin !== null) { $conds[] = 'p.price >= ?'; $params[] = $priceMin; }
        if ($priceMax !== null) { $conds[] = 'p.price <= ?'; $params[] = $priceMax; }
        if ($conds) { $sql .= ' WHERE ' . implode(' AND ', $conds); }
        // Sorting: name ASC/DESC, price ASC/DESC, default by id desc (newest)
        $orderBy = '';
        switch ($sort) {
            case 'name_asc':
                $orderBy = ' ORDER BY LOWER(p.name) ASC, p.id DESC';
                break;
            case 'name_desc':
                $orderBy = ' ORDER BY LOWER(p.name) DESC, p.id DESC';
                break;
            case 'price_asc':
                $orderBy = ' ORDER BY p.price ASC, LOWER(p.name) ASC';
                break;
            case 'price_desc':
                $orderBy = ' ORDER BY p.price DESC, LOWER(p.name) ASC';
                break;
            default:
                $orderBy = ' ORDER BY p.id DESC';
        }
        $sql .= $orderBy;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll();
        $cats = $pdo->query('SELECT * FROM categories')->fetchAll();
        $this->render('products/list', [
            'products' => $products,
            'cats' => $cats,
            'q' => $q,
            'selectedCategory' => $cat,
            'sort' => $sort,
            'priceMin' => $priceMin,
            'priceMax' => $priceMax,
            'csrf' => $this->csrfToken(),
        ]);
    }

    public function add()
    {
        global $pdo;
        $this->requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->verifyCsrf($_POST['csrf'] ?? '')) { die('Bad CSRF token'); }
            $name = trim($_POST['name'] ?? '');
            if ($name !== '') {
                // Uppercase first letter (multibyte safe)
                $first = mb_strtoupper(mb_substr($name, 0, 1));
                $name = $first . mb_substr($name, 1);
            }
            $category_id = $_POST['category_id'] ?? null;
            $price = $_POST['price'] ?? 0;
            $stock = $_POST['stock_quantity'] ?? 0;
            $image_url = $_POST['image_url'] ?? null;
            $stmt = $pdo->prepare('INSERT INTO products (name,category_id,price,stock_quantity,image_url) VALUES (?,?,?,?,?)');
            $stmt->execute([$name, $category_id ?: null, $price, $stock, $image_url]);
            $this->setFlash('success','Product added');
            header('Location: ' . $this->baseUrl('products'));
            exit;
        }
        $cats = $pdo->query('SELECT * FROM categories')->fetchAll();
        $this->render('products/add', [ 'cats' => $cats, 'csrf' => $this->csrfToken() ]);
    }

    public function edit()
    {
        global $pdo;
        $id = $_GET['id'] ?? null;
        if (!$id) { header('Location: /products'); exit; }
        $this->requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->verifyCsrf($_POST['csrf'] ?? '')) { die('Bad CSRF token'); }
            $name = trim($_POST['name'] ?? '');
            if ($name !== '') {
                $first = mb_strtoupper(mb_substr($name, 0, 1));
                $name = $first . mb_substr($name, 1);
            }
            $category_id = $_POST['category_id'] ?? null;
            $price = $_POST['price'] ?? 0;
            $stock = $_POST['stock_quantity'] ?? 0;
            $image_url = $_POST['image_url'] ?? null;
            $stmt = $pdo->prepare('UPDATE products SET name=?,category_id=?,price=?,stock_quantity=?,image_url=? WHERE id=?');
            $stmt->execute([$name, $category_id ?: null, $price, $stock, $image_url, $id]);
            $this->setFlash('success','Product updated');
            header('Location: ' . $this->baseUrl('products'));
            exit;
        }
        $product = $pdo->prepare('SELECT * FROM products WHERE id=?');
        $product->execute([$id]);
        $product = $product->fetch();
        $cats = $pdo->query('SELECT * FROM categories')->fetchAll();
        $this->render('products/edit', [ 'product' => $product, 'cats' => $cats, 'csrf' => $this->csrfToken() ]);
    }

    public function delete()
    {
        global $pdo;
        $id = $_GET['id'] ?? null;
        $this->requireAdmin();
        if ($id && isset($_GET['csrf']) && $this->verifyCsrf($_GET['csrf'])) {
            $stmt = $pdo->prepare('DELETE FROM products WHERE id=?');
            $stmt->execute([$id]);
            $this->setFlash('success','Product deleted');
        } else {
            $this->setFlash('error','Delete failed (CSRF or missing id)');
        }
        header('Location: ' . $this->baseUrl('products'));
        exit;
    }

    // Admin: Add new category (AJAX or normal)
    public function addCategory()
    {
        global $pdo;
        $this->requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . $this->baseUrl('products')); exit; }
        if (!$this->verifyCsrf($_POST['csrf'] ?? '')) { http_response_code(400); echo 'Bad CSRF token'; return; }
        $name = trim($_POST['category_name'] ?? '');
        if ($name === '') { 
            $msg = 'Category name required';
            if ($this->isAjax()) { header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>$msg]); return; }
            $this->setFlash('error',$msg); header('Location: ' . $this->baseUrl('products')); exit; 
        }
        $stmt = $pdo->prepare('INSERT INTO categories (category_name) VALUES (?)');
        $stmt->execute([$name]);
        $msg = 'Category added';
        if ($this->isAjax()) { header('Content-Type: application/json'); echo json_encode(['success'=>true,'message'=>$msg]); return; }
        $this->setFlash('success',$msg); header('Location: ' . $this->baseUrl('products')); exit;
    }

    private function isAjax()
    {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
            || (!empty($_POST['ajax']) && $_POST['ajax'] == '1');
    }

    // Return categories as JSON for dynamic UI updates
    public function categories()
    {
        global $pdo;
        header('Content-Type: application/json');
        $rows = $pdo->query('SELECT id, category_name FROM categories ORDER BY category_name ASC')->fetchAll();
        echo json_encode([ 'success' => true, 'categories' => $rows ]);
    }
}
