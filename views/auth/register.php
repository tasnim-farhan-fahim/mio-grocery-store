<div class="page">
<h2>Register</h2>
<?php if (!empty($error)) echo '<p class="error">'.htmlspecialchars($error).'</p>'; ?>
<form method="post" action="<?= htmlspecialchars($baseUrl) ?>/auth/register" class="auth-form">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
        <label>Name<br><input type="text" name="name" required></label>
        <label>Email<br><input type="email" name="email" required></label>
        <label>Password<br><input type="password" name="password" required minlength="6"></label>
        <?php if (!empty($_SESSION['user']) && $_SESSION['user']['role']==='admin'): ?>
            <label>Role<br>
                <select name="role">
                    <option value="customer">Customer</option>
                    <option value="admin">Admin</option>
                </select>
            </label>
        <?php endif; ?>
        <button type="submit">Create Account</button>
        <p class="alt">Already have an account? <a href="<?= htmlspecialchars($baseUrl) ?>/auth/login">Login</a></p>
</form>
</div>
