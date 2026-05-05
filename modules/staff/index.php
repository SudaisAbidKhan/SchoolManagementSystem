<?php
// ============================================================
//  modules/staff/index.php
//  List all staff members
// ============================================================

$page_title = "Staff";
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
require_once '../../config/db.php';

// ── Flash messages ───────────────────────────────────────────
$success = $_SESSION['success'] ?? ''; unset($_SESSION['success']);
$error   = $_SESSION['error']   ?? ''; unset($_SESSION['error']);

// ── Search / Filter ──────────────────────────────────────────
$search = trim($_GET['search'] ?? '');
$role   = trim($_GET['role']   ?? '');

$where  = "WHERE 1=1";
$params = [];
$types  = "";

if (!empty($search)) {
    $where   .= " AND (name LIKE ? OR contact LIKE ?)";
    $like     = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
    $types   .= "ss";
}
if (!empty($role)) {
    $where   .= " AND role = ?";
    $params[] = $role;
    $types   .= "s";
}

$sql  = "SELECT * FROM staff {$where} ORDER BY role ASC, name ASC";
$stmt = mysqli_prepare($conn, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$staff_list = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

// ── Summary counts ───────────────────────────────────────────
$summary = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT
        COUNT(*)                                              AS total,
        SUM(CASE WHEN role='Teacher'    THEN 1 ELSE 0 END)   AS teachers,
        SUM(CASE WHEN role='Admin'      THEN 1 ELSE 0 END)   AS admins,
        COALESCE(SUM(salary), 0)                              AS total_salary
    FROM staff
"));

// ── Role options ─────────────────────────────────────────────
$roles = ['Teacher', 'Admin', 'Accountant', 'Peon', 'Other'];
?>

<!-- ── PAGE HEADER ────────────────────────────────────────── -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0">Staff</h4>
        <small class="text-muted">Manage all staff members</small>
    </div>
    <a href="add.php" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Add Staff
    </a>
</div>

<!-- ── FLASH MESSAGES ────────────────────────────────────── -->
<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- ── SUMMARY CARDS ──────────────────────────────────────── -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body py-3">
                <div class="fs-3 fw-bold text-primary"><?= $summary['total'] ?></div>
                <div class="text-muted small">Total Staff</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body py-3">
                <div class="fs-3 fw-bold text-success"><?= $summary['teachers'] ?></div>
                <div class="text-muted small">Teachers</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body py-3">
                <div class="fs-3 fw-bold text-warning"><?= $summary['admins'] ?></div>
                <div class="text-muted small">Admin Staff</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body py-3">
                <div class="fs-6 fw-bold text-danger">
                    PKR <?= number_format($summary['total_salary'], 0) ?>
                </div>
                <div class="text-muted small">Monthly Payroll</div>
            </div>
        </div>
    </div>
</div>

<!-- ── SEARCH & FILTER ────────────────────────────────────── -->
<div class="card mb-3">
    <div class="card-body py-3">
        <form method="GET" action="index.php" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label small fw-semibold mb-1">Search</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        placeholder="Name or contact number..."
                        value="<?= htmlspecialchars($search) ?>"
                    >
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Filter by Role</label>
                <select name="role" class="form-select">
                    <option value="">All Roles</option>
                    <?php foreach ($roles as $r): ?>
                        <option value="<?= $r ?>" <?= $role === $r ? 'selected' : '' ?>>
                            <?= $r ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-filter me-1"></i>Filter
                </button>
                <a href="index.php" class="btn btn-outline-secondary w-100">
                    <i class="fas fa-times"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- ── STAFF TABLE ────────────────────────────────────────── -->
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span><i class="fas fa-users me-2 text-primary"></i>All Staff</span>
        <span class="badge bg-primary"><?= mysqli_num_rows($staff_list) ?> Records</span>
    </div>
    <div class="card-body p-0">
        <?php if (mysqli_num_rows($staff_list) === 0): ?>
            <div class="text-center text-muted py-5">
                <i class="fas fa-users fa-3x mb-3 d-block opacity-25"></i>
                <?= !empty($search) || !empty($role)
                    ? 'No staff match your search.'
                    : 'No staff found. <a href="add.php">Add your first staff member</a>.' ?>
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Contact</th>
                        <th>Monthly Salary</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; while ($row = mysqli_fetch_assoc($staff_list)): ?>
                    <tr>
                        <td class="text-muted small"><?= $i++ ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="staff-avatar">
                                    <?= strtoupper(substr($row['name'], 0, 1)) ?>
                                </div>
                                <span class="fw-semibold">
                                    <?= htmlspecialchars($row['name']) ?>
                                </span>
                            </div>
                        </td>
                        <td><?= roleBadge($row['role']) ?></td>
                        <td><?= htmlspecialchars($row['contact'] ?? '—') ?></td>
                        <td class="fw-semibold text-success">
                            PKR <?= number_format($row['salary'], 0) ?>
                        </td>
                        <td class="text-center">
                            <a href="edit.php?id=<?= $row['id'] ?>"
                               class="btn btn-sm btn-outline-primary me-1" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button
                                class="btn btn-sm btn-outline-danger" title="Delete"
                                onclick="confirmDelete(<?= $row['id'] ?>, '<?= htmlspecialchars($row['name'], ENT_QUOTES) ?>')"
                            >
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ── DELETE MODAL ───────────────────────────────────────── -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title text-danger">
                    <i class="fas fa-trash me-2"></i>Delete Staff Member
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="staffName"></strong>?</p>
                <div class="alert alert-warning mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    This will also remove them from any assigned timetable slots.
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="deleteBtn" class="btn btn-danger">
                    <i class="fas fa-trash me-2"></i>Delete
                </a>
            </div>
        </div>
    </div>
</div>

<?php
// ── Role badge helper ────────────────────────────────────────
function roleBadge(string $role): string {
    $map = [
        'Teacher'    => 'success',
        'Admin'      => 'primary',
        'Accountant' => 'info',
        'Peon'       => 'secondary',
        'Other'      => 'warning',
    ];
    $color = $map[$role] ?? 'secondary';
    return "<span class='badge bg-{$color}'>{$role}</span>";
}
?>

<style>
.staff-avatar {
    width: 34px; height: 34px;
    border-radius: 50%;
    background: #0d6efd22;
    color: #0d6efd;
    font-weight: 700;
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
</style>

<script>
function confirmDelete(id, name) {
    document.getElementById('staffName').textContent = name;
    document.getElementById('deleteBtn').href = 'delete.php?id=' + id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php require_once '../../includes/footer.php'; ?>