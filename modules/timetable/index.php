<?php
// ============================================================
//  modules/timetable/index.php
//  View timetable — filterable by class, displayed by day
// ============================================================

$page_title = "Timetable";
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
require_once '../../config/db.php';

// ── Flash messages ───────────────────────────────────────────
$success = $_SESSION['success'] ?? ''; unset($_SESSION['success']);
$error   = $_SESSION['error']   ?? ''; unset($_SESSION['error']);

// ── Days order ───────────────────────────────────────────────
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

// ── Filter by class ──────────────────────────────────────────
$class_id = (int)($_GET['class_id'] ?? 0);

// ── All classes for dropdown ─────────────────────────────────
$all_classes = mysqli_query($conn,
    "SELECT id, name, section FROM classes ORDER BY name ASC, section ASC"
);

// ── Fetch timetable ──────────────────────────────────────────
$where = $class_id > 0 ? "WHERE t.class_id = {$class_id}" : "";

$timetable_raw = mysqli_query($conn, "
    SELECT
        t.id,
        t.day,
        t.time_slot,
        t.subject,
        c.id      AS class_id,
        c.name    AS class_name,
        c.section AS class_section,
        s.id      AS teacher_id,
        s.name    AS teacher_name,
        s.role    AS teacher_role
    FROM timetable t
    JOIN classes c ON t.class_id   = c.id
    JOIN staff   s ON t.teacher_id = s.id
    {$where}
    ORDER BY
        FIELD(t.day, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'),
        t.time_slot ASC
");

// ── Group by day ─────────────────────────────────────────────
$timetable = [];
foreach ($days as $day) {
    $timetable[$day] = [];
}
while ($row = mysqli_fetch_assoc($timetable_raw)) {
    $timetable[$row['day']][] = $row;
}

// ── Total slots ──────────────────────────────────────────────
$total_slots = array_sum(array_map('count', $timetable));

// ── Selected class name for heading ──────────────────────────
$selected_class_name = '';
if ($class_id > 0) {
    $cls_row = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT name, section FROM classes WHERE id = {$class_id} LIMIT 1"
    ));
    if ($cls_row) {
        $selected_class_name = $cls_row['name'] .
            ($cls_row['section'] ? ' - ' . $cls_row['section'] : '');
    }
}
?>

<!-- ── PAGE HEADER ────────────────────────────────────────── -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0">Timetable</h4>
        <small class="text-muted">
            <?= $selected_class_name
                ? "Showing schedule for <strong>{$selected_class_name}</strong>"
                : "All classes — weekly schedule" ?>
        </small>
    </div>
    <a href="add.php" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Add Slot
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

<!-- ── FILTER BAR ─────────────────────────────────────────── -->
<div class="card mb-4">
    <div class="card-body py-3">
        <form method="GET" action="index.php" class="row g-2 align-items-end">
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
            <div class="col-md-5 text-md-end">
                <span class="badge bg-primary px-3 py-2">
                    <i class="fas fa-calendar-week me-1"></i>
                    <?= $total_slots ?> Total Slot<?= $total_slots !== 1 ? 's' : '' ?>
                </span>
            </div>
        </form>
    </div>
</div>

<!-- ── DAY TABS ───────────────────────────────────────────── -->
<ul class="nav nav-tabs mb-0" id="dayTabs">
    <?php foreach ($days as $i => $day):
        $count = count($timetable[$day]);
    ?>
    <li class="nav-item">
        <button
            class="nav-link <?= $i === 0 ? 'active' : '' ?> position-relative"
            data-bs-toggle="tab"
            data-bs-target="#tab-<?= strtolower($day) ?>"
        >
            <?= $day ?>
            <?php if ($count > 0): ?>
                <span class="badge bg-primary rounded-pill ms-1"><?= $count ?></span>
            <?php endif; ?>
        </button>
    </li>
    <?php endforeach; ?>
</ul>

<!-- ── TAB CONTENT ────────────────────────────────────────── -->
<div class="tab-content border border-top-0 rounded-bottom bg-white mb-4">
    <?php foreach ($days as $i => $day): ?>
    <div
        class="tab-pane fade <?= $i === 0 ? 'show active' : '' ?>"
        id="tab-<?= strtolower($day) ?>"
    >
        <?php if (empty($timetable[$day])): ?>
            <div class="text-center text-muted py-5">
                <i class="fas fa-calendar-times fa-3x mb-3 d-block opacity-25"></i>
                No slots scheduled for <?= $day ?>.
                <a href="add.php?day=<?= urlencode($day) ?>">Add one</a>.
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width:160px;">Time Slot</th>
                        <th>Subject</th>
                        <th>Class</th>
                        <th>Teacher</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($timetable[$day] as $slot): ?>
                    <tr>
                        <td>
                            <span class="badge bg-light text-dark border fw-normal fs-6 px-2">
                                <i class="fas fa-clock me-1 text-primary"></i>
                                <?= htmlspecialchars($slot['time_slot']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="fw-semibold">
                                <?= htmlspecialchars($slot['subject'] ?? '—') ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-primary bg-opacity-10 text-primary fw-semibold">
                                <?= htmlspecialchars($slot['class_name']) ?>
                                <?= $slot['class_section']
                                    ? '- ' . htmlspecialchars($slot['class_section'])
                                    : '' ?>
                            </span>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="tt-avatar">
                                    <?= strtoupper(substr($slot['teacher_name'], 0, 1)) ?>
                                </div>
                                <?= htmlspecialchars($slot['teacher_name']) ?>
                            </div>
                        </td>
                        <td class="text-center">
                            <a href="edit.php?id=<?= $slot['id'] ?>"
                               class="btn btn-sm btn-outline-primary me-1" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button
                                class="btn btn-sm btn-outline-danger" title="Delete"
                                onclick="confirmDelete(
                                    <?= $slot['id'] ?>,
                                    '<?= htmlspecialchars($slot['subject'] ?? 'this slot', ENT_QUOTES) ?>',
                                    '<?= htmlspecialchars($slot['time_slot'], ENT_QUOTES) ?>'
                                )"
                            >
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>

<!-- ── WEEKLY GRID OVERVIEW ───────────────────────────────── -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-th me-2 text-primary"></i>Weekly Overview
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0 text-center small">
                <thead class="table-light">
                    <tr>
                        <?php foreach ($days as $day): ?>
                            <th class="py-2"><?= $day ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <?php foreach ($days as $day): ?>
                        <td class="p-2 align-top" style="min-width:120px;">
                            <?php if (empty($timetable[$day])): ?>
                                <span class="text-muted">—</span>
                            <?php else: ?>
                                <?php foreach ($timetable[$day] as $slot): ?>
                                <div class="overview-slot mb-1">
                                    <div class="fw-semibold text-primary small">
                                        <?= htmlspecialchars($slot['subject'] ?? 'N/A') ?>
                                    </div>
                                    <div class="text-muted" style="font-size:0.7rem;">
                                        <?= htmlspecialchars($slot['time_slot']) ?>
                                    </div>
                                    <div class="text-muted" style="font-size:0.7rem;">
                                        <?= htmlspecialchars($slot['class_name']) ?>
                                        <?= $slot['class_section']
                                            ? '- ' . htmlspecialchars($slot['class_section'])
                                            : '' ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ── DELETE MODAL ───────────────────────────────────────── -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title text-danger">
                    <i class="fas fa-trash me-2"></i>Delete Timetable Slot
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the
                    <strong id="slotSubject"></strong> slot at
                    <strong id="slotTime"></strong>?
                </p>
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

<style>
.tt-avatar {
    width: 30px; height: 30px;
    border-radius: 50%;
    background: #198754;
    background: #19875422;
    color: #198754;
    font-weight: 700;
    font-size: 0.8rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.overview-slot {
    background: #f8f9fa;
    border-radius: 6px;
    padding: 4px 6px;
    border-left: 3px solid #0d6efd;
}
</style>

<script>
function confirmDelete(id, subject, time) {
    document.getElementById('slotSubject').textContent = subject;
    document.getElementById('slotTime').textContent    = time;
    document.getElementById('deleteBtn').href = 'delete.php?id=' + id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php require_once '../../includes/footer.php'; ?>