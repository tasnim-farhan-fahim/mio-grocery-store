Grocery Store Management System (PHP + MVC + MySQL)

Quick start (Windows, XAMPP):

1. Copy this folder into your web server folder (e.g., C:\xampp\htdocs\grocery_store) or configure a virtual host.
2. Start Apache and MySQL (XAMPP Control Panel).
3. Import the SQL schema: open phpMyAdmin or run the SQL file `db/schema.sql` to create the database and tables.
4. Update DB credentials in `config/db.php` if needed.
5. Open http://localhost/grocery_store/ in your browser.

Default Admin Credentials

- Username: `admin@mio.local`or `Administrator`
- Password: `admin123`

What is included:
- Basic front controller `index.php` with tiny router.
- `config/db.php` PDO connection.
- Controllers: `AuthController`, `ProductController`, `OrderController`.
- Views: auth (login/register), products (list/add/edit), orders (cart/invoice).
- Simple cart stored in PHP session, checkout creates `orders` and `order_items` and reduces stock.

Next steps / improvements:
- Add role-based access control (admin vs staff).
- Add CSRF protection and input validation.
- Add AJAX search/filter and Chart.js reports.
- Implement transactions/payments and receipts (PDF generation).

Notes: This is a minimal scaffold to help you extend features quickly.
