<?php
require_once 'db.php';
if (isset($_POST['login'])) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
    $stmt->execute([$_POST['user'], $_POST['pass']]);
    $u = $stmt->fetch();
    if ($u) {
        $_SESSION['user_id'] = $u['id'];
        $_SESSION['username'] = $u['username'];
        $_SESSION['role'] = $u['role'];
        header("Location: index.php"); exit();
    } else { $err = "Invalid login credentials"; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Product Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="height: 100vh;">
    <div class="card shadow-sm p-4" style="width: 400px; border-radius: 15px;">
        <h2 class="fw-bold mb-4 text-center">Sign In</h2>
        <?php if(isset($err)): ?><div class="alert alert-danger py-2"><?= $err ?></div><?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label small fw-bold">Username</label>
                <input type="text" name="user" class="form-control py-2" required placeholder="admin or user">
            </div>
            <div class="mb-4">
                <label class="form-label small fw-bold">Password</label>
                <input type="password" name="pass" class="form-control py-2" required placeholder="1234">
            </div>
            <button type="submit" name="login" class="btn btn-dark w-100 py-2 fw-bold">Login to Dashboard</button>
        </form>
    </div>
</body>
</html>