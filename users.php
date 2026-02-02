<?php
require_once 'db.php';

// Check Login & Admin
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
if ($_SESSION['role'] !== 'admin') { header("Location: index.php"); exit(); }

// --- Logic Management ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // [ADD USER]
    if (isset($_POST['add_user'])) {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $role = $_POST['role'];

        $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check->execute([$username]);
        
        if ($check->rowCount() == 0) {
            $sql = "INSERT INTO users (username, password, role, created_at) VALUES (?, ?, ?, NOW())";
            $conn->prepare($sql)->execute([$username, $password, $role]);
            header("Location: users.php?msg=added");
        } else {
            header("Location: users.php?err=duplicate");
        }
        exit();
    }

    // [EDIT USER] (ใหม่)
    if (isset($_POST['edit_user'])) {
        $id = $_POST['user_id'];
        $username = trim($_POST['username']);
        $role = $_POST['role'];
        $password = $_POST['password']; // อาจจะเป็นค่าว่างถ้าไม่เปลี่ยน

        // เช็คชื่อซ้ำ (ยกเว้นตัวเอง)
        $check = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $check->execute([$username, $id]);

        if ($check->rowCount() == 0) {
            if (!empty($password)) {
                // ถ้ากรอกรหัสผ่านใหม่ ให้ Update รหัสผ่านด้วย
                $sql = "UPDATE users SET username=?, role=?, password=? WHERE id=?";
                $conn->prepare($sql)->execute([$username, $role, $password, $id]);
            } else {
                // ถ้าไม่กรอกรหัสผ่าน ให้ Update แค่ Role/Username
                $sql = "UPDATE users SET username=?, role=? WHERE id=?";
                $conn->prepare($sql)->execute([$username, $role, $id]);
            }
            header("Location: users.php?msg=updated");
        } else {
            header("Location: users.php?err=duplicate");
        }
        exit();
    }
}

// [DELETE USER]
if (isset($_GET['delete_id'])) {
    $del_id = $_GET['delete_id'];
    if ($del_id != $_SESSION['user_id']) {
        $conn->prepare("DELETE FROM users WHERE id = ?")->execute([$del_id]);
        header("Location: users.php?msg=deleted");
    } else {
        header("Location: users.php?err=self_delete");
    }
    exit();
}

$users = $conn->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>User Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background-color: #f3f4f6; font-family: 'Inter', sans-serif; color: #1f2937; }
        .table-card { background: white; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); overflow: hidden; border: none; }
        .table thead th { background-color: #f9fafb; color: #6b7280; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; padding: 16px 24px; border-bottom: 1px solid #e5e7eb; }
        .table tbody td { padding: 16px 24px; vertical-align: middle; border-bottom: 1px solid #f3f4f6; font-size: 0.9rem; }
        .btn-primary-custom { background-color: #111827; color: white; border: none; border-radius: 8px; padding: 10px 20px; font-weight: 500; transition: all 0.2s; }
        .btn-primary-custom:hover { background-color: #374151; color: white; transform: translateY(-1px); }
        .badge-role { padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .role-admin { background-color: #e0e7ff; color: #4338ca; }
        .role-user { background-color: #f3f4f6; color: #4b5563; }
        .nav-link-back { color: #6b7280; text-decoration: none; font-weight: 500; font-size: 0.9rem; display: flex; align-items: center; gap: 8px; transition: color 0.2s; }
        .nav-link-back:hover { color: #111827; }
    </style>
</head>
<body>

<div class="container py-5">
    
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <a href="index.php" class="nav-link-back mb-2"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            <h2 class="fw-bold m-0 text-dark">User Management</h2>
            <p class="text-muted small m-0">Create, edit, and manage system access.</p>
        </div>
        <button class="btn-primary-custom" onclick="clearModal()" data-bs-toggle="modal" data-bs-target="#userModal">
            <i class="fas fa-user-plus me-2"></i> Add New User
        </button>
    </div>

    <?php if(isset($_GET['err']) && $_GET['err'] == 'duplicate'): ?>
        <div class="alert alert-danger shadow-sm border-0 rounded-3 small"><i class="fas fa-exclamation-circle me-2"></i> Username already exists!</div>
    <?php endif; ?>

    <div class="table-card">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Password</th>
                        <th>Created At</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($users as $u): ?>
                    <tr>
                        <td>
                            <div class="fw-bold text-dark">
                                <i class="fas fa-user-circle text-muted me-2"></i><?= htmlspecialchars($u['username']) ?>
                            </div>
                        </td>
                        <td>
                            <span class="badge-role <?= $u['role'] == 'admin' ? 'role-admin' : 'role-user' ?>">
                                <?= $u['role'] ?>
                            </span>
                        </td>
                        <td class="text-muted small font-monospace">•••••••</td>
                        <td class="text-muted small"><?= date('d M Y, H:i', strtotime($u['created_at'])) ?></td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-light border me-1" onclick='editUser(<?= json_encode($u) ?>)' title="Edit">
                                <i class="fas fa-pen text-secondary"></i>
                            </button>
                            
                            <?php if($u['id'] != $_SESSION['user_id']): ?>
                                <a href="users.php?delete_id=<?= $u['id'] ?>" class="btn btn-sm btn-light text-danger border-0" onclick="return confirm('Remove this user?')">
                                   <i class="fas fa-trash-alt"></i>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="modalTitle">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-4">
                <form action="users.php" method="POST" id="userForm">
                    <input type="hidden" name="user_id" id="user_id">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">USERNAME</label>
                        <input type="text" name="username" id="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">PASSWORD</label>
                        <input type="password" name="password" id="password" class="form-control" placeholder="••••••">
                        <small class="text-muted d-none" id="passHelp">Leave blank to keep current password.</small>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted">ROLE</label>
                        <select name="role" id="role" class="form-select">
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <button type="submit" name="add_user" id="btnSubmit" class="btn btn-dark w-100 py-2 fw-bold" style="border-radius: 8px;">Create Account</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function clearModal() {
        document.getElementById('userForm').reset();
        document.getElementById('modalTitle').innerText = "Add New User";
        document.getElementById('btnSubmit').name = "add_user";
        document.getElementById('btnSubmit').innerText = "Create Account";
        document.getElementById('user_id').value = "";
        
        // Reset Password field state
        document.getElementById('password').required = true;
        document.getElementById('passHelp').classList.add('d-none');
    }

    function editUser(data) {
        var myModal = new bootstrap.Modal(document.getElementById('userModal'));
        myModal.show();

        document.getElementById('modalTitle').innerText = "Edit User";
        document.getElementById('btnSubmit').name = "edit_user";
        document.getElementById('btnSubmit').innerText = "Update User";
        document.getElementById('user_id').value = data.id;

        document.getElementById('username').value = data.username;
        document.getElementById('role').value = data.role;
        
        // Password optional when editing
        document.getElementById('password').value = "";
        document.getElementById('password').required = false;
        document.getElementById('passHelp').classList.remove('d-none');
    }
</script>
</body>
</html>