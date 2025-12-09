<?php
require_once __DIR__ . '/BaseController.php';

class UserController extends BaseController
{
    public function dashboard()
    {
        $this->requireLogin();
        global $pdo;
        $uid = (int)$_SESSION['user']['id'];
        // Fetch counts and totals
        $stmt1 = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE user_id=?');
        $stmt1->execute([$uid]);
        $ordersCount = (int)$stmt1->fetchColumn();
        $stmt2 = $pdo->prepare('SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE user_id=?');
        $stmt2->execute([$uid]);
        $totalSpent = (float)$stmt2->fetchColumn();
        $recent = $pdo->prepare('SELECT id, order_date, total_amount FROM orders WHERE user_id=? ORDER BY id DESC LIMIT 10');
        $recent->execute([$uid]);
        $recent = $recent->fetchAll();
        $this->render('user/dashboard', [
            'ordersCount' => $ordersCount,
            'totalSpent' => $totalSpent,
            'recent' => $recent,
        ]);
    }

    public function orders()
    {
        $this->requireLogin();
        global $pdo;
        $uid = (int)$_SESSION['user']['id'];
        $rows = $pdo->prepare('SELECT id, order_date, total_amount FROM orders WHERE user_id=? ORDER BY id DESC');
        $rows->execute([$uid]);
        $this->render('user/orders', [ 'orders' => $rows->fetchAll() ]);
    }
}
