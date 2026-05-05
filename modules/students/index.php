<?php
// ============================================================
//  modules/students/index.php
//  List all students with search and filter
// ============================================================

$page_title = "Students";
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
require_once '../../config/db.php';

// ── Flash messages ───────────────────────────────────────────
$success = $_SESSION['success'] ?? ''; unset($_SESSION['success']);
$error   = $_SESSION['error']   ?? ''; unset($_SESSION['error']);

// ── Search / Filter inputs ───────────────────────────────────
$search   = trim($_GET['search']   ?? '');
$class_id = (int)($_GET['class_id'] ?? 0);

// ── Build WHERE clause ───────────────────────────────────────
$where = "WHERE 1=1";
$params = [];
$types  = "";

if (!empty($search)) {
    $where   .= " AND (s.name LIKE ? OR s.father_name LIKE ? OR s.cnic LIKE ? OR s.contact LIKE ?)";
    $like     = "%{$search}%";
    $params   = array_merge($params, [$like, $like, $like, $like]);
    $types   .= "ssss";
}
if ($class_id > 0) {
    $where   .= " AND s.class_id = ?";
    $params[] = $class_id;
    $types   .= "i";
}

// ── Fetch students ───────────────────────────────────────────
$sql = "
    SELECT
        s.id, s.name, s.father_name, s.cnic,
        s.contact, s.admission_date,
        c.name    AS class_name,
        c.section AS class_section
    FROM students s
    JOIN classes c ON s.class_id = c.id
    {$where}
    ORDER BY s.id DESC
";

$stmt = mysqli_prepare($conn, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$students = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

// ── All classes for filter dropdown ─────────────────────────
$all_classes = mysqli_query($conn,
    "SELECT id, name, section FROM classes ORDER BY name ASC, section ASC"
);
?>

<!-- ── PAGE HEADER ────────────────────────────────────────── -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0">Students</h4>
        <small class="text-muted">Manage all student records</small>
    </div>
    <a href="add.php" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Add Student
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
                        placeholder="Name, father name, CNIC, contact..."
                        value="<?= htmlspecialchars($search) ?>"
                    >
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1">Filter by Class</label>
                <select name="class_id" class="form-select">
                    <option value="0">All Classes</option>
                    <?php
                    mysqli_data_seek($all_classes, 0);
                    while ($cls = mysqli_fetch_assoc($all_classes)):
                    ?>
                    <option value="<?= $cls['id'] ?>"
                        <?= $class_id === (int)$cls['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cls['name']) ?>
                        <?= $cls['section'] ? '- ' . htmlspecialchars($cls['section']) : '' ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
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

<!-- ── STUDENTS TABLE ─────────────────────────────────────── -->
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span><i class="fas fa-user-graduate me-2 text-primary"></i>All Students</span>
        <span class="badge bg-primary"><?= mysqli_num_rows($students) ?> Records</span>
    </div>
    <div class="card-body p-0">
        <?php if (mysqli_num_rows($students) === 0): ?>
            <div class="text-center text-muted py-5">
                <i class="fas fa-user-graduate fa-3x mb-3 d-block opacity-25"></i>
                <?= !empty($search) || $class_id > 0
                    ? 'No students match your search.'
                    : 'No students found. <a href="add.php">Add your first student</a>.' ?>
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Student Name</th>
                        <th>Father's Name</th>
                        <th>Class</th>
                        <th>Contact</th>
                        <th>Admitted</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; while ($row = mysqli_fetch_assoc($students)): ?>
                    <tr>
                        <td class="text-muted small"><?= $i++ ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-circle">
                                    <?= strtoupper(substr($row['name'], 0, 1)) ?>
                                </div>
                                <div>
                                    <div class="fw-semibold"><?= htmlspecialchars($row['name']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($row['cnic'] ?? '—') ?></small>
                                </div>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($row['father_name']) ?></td>
                        <td>
                            <span class="badge bg-primary bg-opacity-10 text-primary fw-semibold">
                                <?= htmlspecialchars($row['class_name']) ?>
                                <?= $row['class_section'] ? '- ' . htmlspecialchars($row['class_section']) : '' ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($row['contact'] ?? '—') ?></td>
                        <td class="text-muted small">
                            <?= date('d M Y', strtotime($row['admission_date'])) ?>
                        </td>
                        <td class="text-center">
                            <a href="view.php?id=<?= $row['id'] ?>"
                               class="btn btn-sm btn-outline-info me-1" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
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
                    <i class="fas fa-trash me-2"></i>Delete Student
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="studentName"></strong>?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    All fee records for this student will also be permanently deleted.
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

<!-- ── AVATAR STYLE ───────────────────────────────────────── -->
<style>
.avatar-circle {
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
    document.getElementById('studentName').textContent = name;
    document.getElementById('deleteBtn').href = 'delete.php?id=' + id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php require_once '../../includes/footer.php'; ?>