<?php
require_once 'db.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$isAdmin = ($_SESSION['role'] === 'admin');

// --- 1. Logic Management ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // [ADD]
    if (isset($_POST['add_product'])) {
        $m_percent = str_replace('%', '', $_POST['margin_percent']);
        $sql = "INSERT INTO products (product_name, sale_price, cost_price, margin_percent, margin_price, quarter, marketing_channel, partner_name, end_user_name, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?)";
        $conn->prepare($sql)->execute([$_POST['product_name'], $_POST['sale_price'], $_POST['cost_price'], $m_percent, $_POST['margin_price'], $_POST['quarter'], $_POST['marketing_channel'], $_POST['partner_name'], $_POST['end_user_name'], $_SESSION['user_id']]);
        header("Location: index.php"); exit();
    }

    // [EDIT]
    if (isset($_POST['edit_product']) && $isAdmin) {
        $m_percent = str_replace('%', '', $_POST['margin_percent']);
        $sql = "UPDATE products SET product_name=?, sale_price=?, cost_price=?, margin_percent=?, margin_price=?, quarter=?, marketing_channel=?, partner_name=?, end_user_name=? WHERE id=?";
        $conn->prepare($sql)->execute([$_POST['product_name'], $_POST['sale_price'], $_POST['cost_price'], $m_percent, $_POST['margin_price'], $_POST['quarter'], $_POST['marketing_channel'], $_POST['partner_name'], $_POST['end_user_name'], $_POST['product_id']]);
        header("Location: index.php"); exit();
    }
}

// [ACTIONS: Approve / Revert / Delete]
if ($isAdmin) {
    // Approve
    if (isset($_GET['approve_id'])) {
        $conn->prepare("UPDATE products SET status = 'Approved', approved_by = ?, approved_at = NOW() WHERE id = ?")->execute([$_SESSION['user_id'], $_GET['approve_id']]);
        header("Location: index.php"); exit();
    }
    // [NEW] Revert (Cancel Approve) -> กลับเป็น Pending
    if (isset($_GET['revert_id'])) {
        $conn->prepare("UPDATE products SET status = 'Pending', approved_by = NULL, approved_at = NULL WHERE id = ?")->execute([$_GET['revert_id']]);
        header("Location: index.php"); exit();
    }
    // Delete
    if (isset($_GET['delete_id'])) {
        $conn->prepare("DELETE FROM products WHERE id = ?")->execute([$_GET['delete_id']]);
        header("Location: index.php"); exit();
    }
}

// --- 2. Query ---
$search = $_GET['search'] ?? '';
$q_filter = $_GET['q_filter'] ?? '';

$query_str = "SELECT p.*, u1.username as creator_name, u2.username as approver_name 
              FROM products p 
              LEFT JOIN users u1 ON p.created_by = u1.id 
              LEFT JOIN users u2 ON p.approved_by = u2.id WHERE 1=1";
$params = [];
if ($search) { $query_str .= " AND (p.product_name LIKE ? OR p.partner_name LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($q_filter) { $query_str .= " AND p.quarter = ?"; $params[] = $q_filter; }

$products = $conn->prepare($query_str . " ORDER BY p.id DESC");
$products->execute($params);
$products = $products->fetchAll();

// --- 3. Stats ---
$total_revenue = $conn->query("SELECT SUM(sale_price) FROM products WHERE status = 'Approved'")->fetchColumn() ?: 0;
$total_items = $conn->query("SELECT COUNT(*) FROM products")->fetchColumn();
$avg_margin = $conn->query("SELECT AVG(margin_percent) FROM products WHERE status = 'Approved'")->fetchColumn() ?: 0;
$partners_count = $conn->query("SELECT COUNT(DISTINCT partner_name) FROM products")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Product Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body { background-color: #f3f4f6; font-family: 'Inter', sans-serif; color: #1f2937; }
        .brand-logo { height: 45px; width: auto; object-fit: contain; }
        .stat-card { background: #ffffff; border: none; border-radius: 12px; padding: 24px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); transition: transform 0.2s; display: flex; justify-content: space-between; align-items: center; }
        .stat-card:hover { transform: translateY(-2px); }
        .stat-label { font-size: 0.75rem; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; margin-bottom: 4px; }
        .stat-value { font-size: 1.75rem; font-weight: 700; color: #111827; line-height: 1.2; }
        .icon-box { width: 48px; height: 48px; border-radius: 10px; background: #f8f9fa; display: flex; align-items: center; justify-content: center; color: #374151; font-size: 1.25rem; }
        .search-box { background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 8px 12px; display: flex; align-items: center; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
        .search-box input { border: none; outline: none; width: 100%; font-size: 0.9rem; margin-left: 8px; }
        .btn-primary-custom { background-color: #111827; color: white; border: none; border-radius: 8px; padding: 10px 20px; font-weight: 500; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); transition: all 0.2s; }
        .btn-primary-custom:hover { background-color: #374151; transform: translateY(-1px); }
        .table-card { background: white; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); overflow: hidden; border: none; }
        .table { margin-bottom: 0; }
        .table thead th { background-color: #f9fafb; color: #6b7280; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; padding: 16px 24px; border-bottom: 1px solid #e5e7eb; }
        .table tbody td { padding: 16px 24px; vertical-align: middle; border-bottom: 1px solid #f3f4f6; font-size: 0.9rem; }
        .table tbody tr:last-child td { border-bottom: none; }
        .table tbody tr:hover { background-color: #f9fafb; }
        .text-sub { font-size: 0.8rem; color: #6b7280; }
        .badge-pill { padding: 4px 10px; border-radius: 99px; font-size: 0.75rem; font-weight: 600; }
        .status-pending { background: #fff7ed; color: #c2410c; border: 1px solid #ffedd5; }
        .status-approved { background: #f0fdf4; color: #15803d; border: 1px solid #dcfce7; }
        .client-tag { display: inline-block; background: #f3f4f6; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; color: #4b5563; margin-right: 5px; }
    </style>
</head>
<body>

<div class="container py-4">
    
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div class="d-flex align-items-center gap-3">
            <img src="image.png" alt="Logo" class="brand-logo">
            <div style="height: 24px; width: 1px; background: #e5e7eb;"></div>
            <span class="fw-bold text-dark">Product Manager</span>
        </div>

        <div class="dropdown">
            <button class="btn btn-white border-0 d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown">
                <div class="bg-dark text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-size: 0.8rem;">
                    <?= strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)) ?>
                </div>
                <div class="text-start d-none d-sm-block">
                    <div class="fw-bold small text-dark"><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></div>
                </div>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2">
                <li class="px-3 py-2 text-muted small border-bottom mb-2">Role: <?= $isAdmin ? 'Admin' : 'Staff' ?></li>
                <?php if($isAdmin): ?>
                    <li><a class="dropdown-item small" href="users.php"><i class="fas fa-users-cog me-2"></i> Manage Users</a></li>
                    <li><hr class="dropdown-divider my-1"></li>
                <?php endif; ?>
                <li><a class="dropdown-item small text-danger" href="logout.php">Log out</a></li>
            </ul>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-3"><div class="stat-card"><div><div class="stat-label">Total Products</div><div class="stat-value"><?= $total_items ?></div></div><div class="icon-box"><i class="fas fa-cube"></i></div></div></div>
        <div class="col-md-3"><div class="stat-card"><div><div class="stat-label">Total Revenue</div><div class="stat-value">฿<?= number_format($total_revenue) ?></div></div><div class="icon-box"><i class="fas fa-dollar-sign"></i></div></div></div>
        <div class="col-md-3"><div class="stat-card"><div><div class="stat-label">Avg. Margin</div><div class="stat-value"><?= number_format($avg_margin, 1) ?>%</div></div><div class="icon-box"><i class="fas fa-chart-line"></i></div></div></div>
        <div class="col-md-3"><div class="stat-card"><div><div class="stat-label">Partners</div><div class="stat-value"><?= $partners_count ?></div></div><div class="icon-box"><i class="fas fa-user-group"></i></div></div></div>
    </div>

    <div class="d-flex flex-wrap gap-3 mb-4 justify-content-between align-items-center">
        <form method="GET" class="d-flex gap-3 flex-grow-1" style="max-width: 600px;">
            <div class="search-box flex-grow-1">
                <i class="fas fa-search text-muted"></i>
                <input type="text" name="search" placeholder="Search product or partner..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <select name="q_filter" class="form-select border-0 shadow-sm" style="width: 140px; cursor: pointer;">
                <option value="">Quarter: All</option>
                <option value="Q1" <?= $q_filter == 'Q1' ? 'selected' : '' ?>>Q1</option>
                <option value="Q2" <?= $q_filter == 'Q2' ? 'selected' : '' ?>>Q2</option>
                <option value="Q3" <?= $q_filter == 'Q3' ? 'selected' : '' ?>>Q3</option>
                <option value="Q4" <?= $q_filter == 'Q4' ? 'selected' : '' ?>>Q4</option>
            </select>
            <button type="submit" class="btn btn-light shadow-sm border"><i class="fas fa-filter"></i></button>
        </form>

        <button type="button" class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addProductModal" onclick="clearModal()">
            <i class="fas fa-plus me-2"></i> Add Product
        </button>
    </div>

    <div class="table-card">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Product Name </th>
                        <th>Sales & Cost Price</th>
                        <th>Partner & Enduser</th>
                        <th>Quarter</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($products as $p): ?>
                    <tr>
                        <td>
                            <div class="fw-bold text-dark"><?= htmlspecialchars($p['product_name']) ?></div>
                            <div class="text-sub mt-1">Added by <?= htmlspecialchars($p['creator_name']) ?></div>
                        </td>
                        <td>
                            <div class="fw-bold">฿<?= number_format($p['sale_price'], 2) ?></div>
                            <div class="text-sub">Cost: ฿<?= number_format($p['cost_price'], 2) ?></div>
                        </td>
                        <td>
                            <?php if($p['partner_name']): ?>
                                <div class="mb-1"><span class="client-tag"><i class="fas fa-building me-1"></i> <?= htmlspecialchars($p['partner_name']) ?></span></div>
                            <?php endif; ?>
                            <?php if($p['end_user_name']): ?>
                                <div class="text-sub ms-1"><i class="fas fa-level-up-alt fa-rotate-90 me-1"></i> <?= htmlspecialchars($p['end_user_name']) ?></div>
                            <?php endif; ?>
                            <?php if(!$p['partner_name'] && !$p['end_user_name']) echo '<span class="text-muted">-</span>'; ?>
                        </td>
                        <td><span class="badge bg-light text-dark border fw-normal"><?= $p['quarter'] ?></span></td>
                        <td>
                            <?php if($p['status'] == 'Pending'): ?>
                                <span class="badge-pill status-pending"><i class="fas fa-circle fa-xs me-1"></i> Pending</span>
                            <?php else: ?>
                                <span class="badge-pill status-approved"><i class="fas fa-check fa-xs me-1"></i> Approved</span>
                                <div class="text-sub mt-1" style="font-size: 0.7rem;">by <?= htmlspecialchars($p['approver_name']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <?php if($isAdmin): ?>
                                <button class="btn btn-sm btn-light border me-1" onclick='editProduct(<?= json_encode($p) ?>)' title="Edit"><i class="fas fa-pen text-secondary"></i></button>
                                
                                <?php if($p['status'] == 'Pending'): ?>
                                    <a href="index.php?approve_id=<?= $p['id'] ?>" class="btn btn-sm btn-dark px-3 rounded-pill me-1" style="font-size: 0.75rem;">Approve</a>
                                <?php else: ?>
                                    <a href="index.php?revert_id=<?= $p['id'] ?>" class="btn btn-sm btn-light text-warning border-0 me-1" title="Cancel Approval (Revert to Pending)" onclick="return confirm('Cancel approval properly?')"><i class="fas fa-undo"></i></a>
                                <?php endif; ?>

                                <a href="index.php?delete_id=<?= $p['id'] ?>" class="btn btn-sm btn-light text-danger border-0" onclick="return confirm('Confirm delete?')"><i class="fas fa-trash"></i></a>
                            
                            <?php else: ?>
                                <button class="btn btn-sm btn-light border text-primary px-3" onclick='viewProduct(<?= json_encode($p) ?>)'>
                                    <i class="fas fa-eye me-1"></i> View
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($products)): ?><tr><td colspan="6" class="text-center py-5 text-muted">No data available.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'modal_add.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function setFormReadOnly(isReadOnly) {
        const inputs = document.querySelectorAll('#modalForm input, #modalForm select');
        inputs.forEach(input => input.disabled = isReadOnly);
        document.getElementById('btnSubmit').style.display = isReadOnly ? 'none' : 'block';
    }
    function clearModal() {
        document.getElementById('modalForm').reset();
        document.getElementById('modalTitle').innerText = "Create New Product";
        document.getElementById('btnSubmit').name = "add_product";
        document.getElementById('btnSubmit').innerText = "Save Product";
        document.getElementById('product_id').value = "";
        setFormReadOnly(false);
    }
    function editProduct(data) { // Admin
        var myModal = new bootstrap.Modal(document.getElementById('addProductModal'));
        myModal.show();
        document.getElementById('modalTitle').innerText = "Edit Product";
        document.getElementById('btnSubmit').name = "edit_product";
        document.getElementById('btnSubmit').innerText = "Update";
        fillData(data);
        setFormReadOnly(false);
        document.getElementById('sale_val').dispatchEvent(new Event('input'));
    }
    function viewProduct(data) { // User
        var myModal = new bootstrap.Modal(document.getElementById('addProductModal'));
        myModal.show();
        document.getElementById('modalTitle').innerText = "Product Details";
        fillData(data);
        setFormReadOnly(true);
    }
    function fillData(data) {
        document.getElementById('product_id').value = data.id;
        document.querySelector('input[name="product_name"]').value = data.product_name;
        document.getElementById('sale_val').value = data.sale_price;
        document.getElementById('cost_val').value = data.cost_price;
        document.querySelector('select[name="quarter"]').value = data.quarter;
        document.querySelector('input[name="marketing_channel"]').value = data.marketing_channel;
        document.querySelector('input[name="partner_name"]').value = data.partner_name;
        document.querySelector('input[name="end_user_name"]').value = data.end_user_name;
    }
</script>
</body>
</html>