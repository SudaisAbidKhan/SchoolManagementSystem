<?php
// ============================================================
//  modules/classes/index.php
//  List all classes
// ============================================================

$page_title = "Classes";
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
require_once '../../config/db.php';

// ── Flash messages ───────────────────────────────────────────
$success = $_SESSION['success'] ?? ''; unset($_SESSION['success']);
$error   = $_SESSION['error']   ?? ''; unset($_SESSION['error']);

// ── Fetch all classes with student count ─────────────────────
$classes = mysqli_query($conn, "
    SELECT
        c.id,
        c.name,
        c.section,
        c.fee,
        COUNT(s.id) AS total_students
    FROM classes c
    LEFT JOIN students s ON s.class_id = c.id
    GROUP BY c.id
    ORDER BY c.name ASC, c.section ASC
");
?>

<!-- ── PAGE HEADER ────────────────────────────────────────── -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0">Classes</h4>
        <small class="text-muted">Manage all classes and sections</small>
    </div>
    <a href="add.php" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Add Class
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

<!-- ── CLASSES TABLE ──────────────────────────────────────── -->
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span><i class="fas fa-chalkboard me-2 text-primary"></i>All Classes</span>
        <span class="badge bg-primary"><?= mysqli_num_rows($classes) ?> Classes</span>
    </div>
    <div class="card-body p-0">
        <?php if (mysqli_num_rows($classes) === 0): ?>
            <div class="text-center text-muted py-5">
                <i class="fas fa-chalkboard fa-3x mb-3 d-block opacity-25"></i>
                No classes found. <a href="add.php">Add your first class</a>.
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Class Name</th>
                        <th>Section</th>
                        <th>Monthly Fee</th>
                        <th>Students</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; while ($row = mysqli_fetch_assoc($classes)): ?>
                    <tr>
                        <td class="text-muted"><?= $i++ ?></td>
                        <td class="fw-semibold"><?= htmlspecialchars($row['name']) ?></td>
                        <td>
                            <?= $row['section']
                                ? '<span class="badge bg-secondary">' . htmlspecialchars($row['section']) . '</span>'
                                : '<span class="text-muted">—</span>' ?>
                        </td>
                        <td class="text-success fw-semibold">
                            PKR <?= number_format($row['fee'], 0) ?>
                        </td>
                        <td>
                            <span class="badge bg-info text-dark">
                                <i class="fas fa-users me-1"></i><?= $row['total_students'] ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <a href="edit.php?id=<?= $row['id'] ?>"
                               class="btn btn-sm btn-outline-primary me-1"
                               title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button
                                class="btn btn-sm btn-outline-danger"
                                title="Delete"
                                onclick="confirmDelete(<?= $row['id'] ?>, '<?= htmlspecialchars($row['name']) ?><?= $row['section'] ? ' - ' . htmlspecialchars($row['section']) : '' ?>', <?= $row['total_students'] ?>)"
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

<!-- ── DELETE CONFIRM MODAL ───────────────────────────────── -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title text-danger">
                    <i class="fas fa-trash me-2"></i>Delete Class
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="className"></strong>?</p>
                <div id="hasStudentsWarning" class="alert alert-warning d-none">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    This class has enrolled students. You must reassign or remove them before deleting.
                </div>
                <p class="text-muted small mb-0">This action cannot be undone.</p>
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

<script>
function confirmDelete(id, name, studentCount) {
    document.getElementById('className').textContent = name;
    const warning = document.getElementById('hasStudentsWarning');
    const deleteBtn = document.getElementById('deleteBtn');

    if (studentCount > 0) {
        warning.classList.remove('d-none');
        deleteBtn.classList.add('disabled');
    } else {
        warning.classList.add('d-none');
        deleteBtn.classList.remove('disabled');
        deleteBtn.href = 'delete.php?id=' + id;
    }
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php require_once '../../includes/footer.php'; ?>