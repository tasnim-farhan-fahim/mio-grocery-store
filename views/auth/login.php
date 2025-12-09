<div class="page">
<h2>Login</h2>
<?php if (!empty($error)) echo '<p class="error">'.htmlspecialchars($error).'</p>'; ?>
<form method="post" action="<?= htmlspecialchars($baseUrl) ?>/auth/login" class="auth-form">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
    <label>Email or Username<br><input type="text" name="identity" required></label>
    <label>Password<br><input type="password" name="password" required></label>
    <button type="submit">Login</button>
    <p class="alt">Or <a href="<?= htmlspecialchars($baseUrl) ?>/auth/register">Create an account</a></p>
</form>
</div>
