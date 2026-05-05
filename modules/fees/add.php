<?php
// ============================================================
//  modules/fees/add.php
//  Record a fee payment for a student
// ============================================================

session_start();
require_once '../../config/db.php';

// ── All students for dropdown ────────────────────────────────
$all_students = mysqli_query($conn, "
    SELECT s.id, s.name, s.father_name, c.name AS class_name,
           c.section AS class_section, c.fee AS class_fee
    FROM students s
    JOIN classes c ON s.class_id = c.id
    ORDER BY s.name ASC
");

// ── Generate month options (current + 11 previous) ───────────
$month_options = [];
for ($i = 0; $i < 12; $i++) {
    $month_options[] = date('F Y', strtotime("-$i months"));
}

$errors = [];
$input  = [
    'student_id'   => (int)($_GET['student_id'] ?? 0),
    'amount'       => '',
    'month'        => date('F Y'),
    'status'       => 'Paid',
    'payment_date' => date('Y-m-d'),
    'remarks'      => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ── Sanitize ─────────────────────────────────────────────
    $input['student_id']   = (int)($_POST['student_id']   ?? 0);
    $input['amount']       = trim($_POST['amount']       ?? '');
    $input['month']        = trim($_POST['month']        ?? '');
    $input['status']       = trim($_POST['status']       ?? 'Paid');
    $input['payment_date'] = trim($_POST['payment_date'] ?? '');
    $input['remarks']      = trim($_POST['remarks']      ?? '');

    // ── Validate ─────────────────────────────────────────────
    if ($input['student_id'] <= 0) {
        $errors[] = "Please select a student.";
    }
    if (empty($input['amount']) || !is_numeric($input['amount'])
        || (float)$input['amount'] <= 0) {
        $errors[] = "A valid fee amount is required.";
    }
    if (empty($input['month'])) {
        $errors[] = "Please select a month.";
    }
    if (!in_array($input['status'], ['Paid', 'Unpaid'])) {
        $errors[] = "Invalid payment status.";
    }
    if ($input['status'] === 'Paid' && empty($input['payment_date'])) {
        $errors[] = "Payment date is required for paid fees.";
    }

    // ── Duplicate check: same student + month ────────────────
    if (empty($errors)) {
        $dup = mysqli_prepare($conn,
            "SELECT id FROM fees WHERE student_id = ? AND month = ? LIMIT 1"
        );
        mysqli_stmt_bind_param($dup, "is", $input['student_id'], $input['month']);
        mysqli_stmt_execute($dup);
        mysqli_stmt_store_result($dup);
        if (mysqli_stmt_num_rows($dup) > 0) {
            $errors[] = "A fee record for this student and month already exists. 
                         Use Edit to update it.";
        }
        mysqli_stmt_close($dup);
    }

    // ── Insert ───────────────────────────────────────────────
    if (empty($errors)) {
        $payment_date = $input['status'] === 'Paid'
            ? $input['payment_date'] : null;
        $remarks      = !empty($input['remarks']) ? $input['remarks'] : null;
        $amount       = (float)$input['amount'];

        $stmt = mysqli_prepare($conn, "
            INSERT INTO fees (student_id, amount, month, status, payment_date, remarks)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        mysqli_stmt_bind_param($stmt, "idssss",
            $input['student_id'],
            $amount,
            $input['month'],
            $input['status'],
            $payment_date,
            $remarks
        );

        if (mysqli_stmt_execute($stmt)) {
            $new_id = mysqli_insert_id($conn);
            $_SESSION['success'] = "Fee record added successfully.";

            // Redirect to receipt if paid
            if ($input['status'] === 'Paid') {
                header("Location: receipt.php?id={$new_id}");
            } else {
                header("Location: index.php");
            }
            exit();
        } else {
            $errors[] = "Database error: " . mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt);
    }
}

$page_title = "Record Payment";
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<!-- ── PAGE HEADER ────────────────────────────────────────── -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0">Record Fee Payment</h4>
        <small class="text-muted">Add a new fee entry for a student</small>
    </div>
    <a href="index.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-2"></i>Back
    </a>
</div>

<!-- ── ERRORS ────────────────────────────────────────────── -->
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle me-2"></i>
        <ul class="mb-0 mt-1">
            <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- ── FORM ───────────────────────────────────────────────── -->
<div class="card" style="max-width: 680px;">
    <div class="card-header">
        <i class="fas fa-money-bill-wave me-2 text-success"></i>Payment Details
    </div>
    <div class="card-body">
        <form action="add.php" method="POST" id="feeForm">
            <div class="row g-3">

                <!-- Student -->
                <div class="col-12">
                    <label class="form-label fw-semibold">
                        Student <span class="text-danger">*</span>
                    </label>
                    <select name="student_id" id="studentSelect"
                            class="form-select" required>
                        <option value="">-- Select Student --</option>
                        <?php while ($s = mysqli_fetch_assoc($all_students)): ?>
                        <option
                            value="<?= $s['id'] ?>"
                            data-fee="<?= $s['class_fee'] ?>"
                            data-class="<?= htmlspecialchars(
                                $s['class_name'] .
                                ($s['class_section'] ? ' - ' . $s['class_section'] : ''),
                                ENT_QUOTES
                            ) ?>"
                            <?= (int)$input['student_id'] === (int)$s['id'] ? 'selected' : '' ?>
                        >
                            <?= htmlspecialchars($s['name']) ?>
                            — <?= htmlspecialchars($s['class_name']) ?>
                            <?= $s['class_section']
                                ? '(' . htmlspecialchars($s['class_section']) . ')'
                                : '' ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                    <!-- Auto-filled class info -->
                    <div id="classInfo" class="form-text text-primary mt-1 d-none">
                        <i class="fas fa-info-circle me-1"></i>
                        Class: <strong id="className"></strong> &mdash;
                        Monthly Fee: <strong id="classFee"></strong>
                    </div>
                </div>

                <!-- Month -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">
                        Month <span class="text-danger">*</span>
                    </label>
                    <select name="month" class="form-select" required>
                        <?php foreach ($month_options as $mo): ?>
                        <option value="<?= $mo ?>"
                            <?= $input['month'] === $mo ? 'selected' : '' ?>>
                            <?= $mo ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Amount -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">
                        Amount (PKR) <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text">PKR</span>
                        <input
                            type="number"
                            name="amount"
                            id="amountInput"
                            class="form-control"
                            placeholder="e.g. 2500"
                            min="1"
                            step="0.01"
                            value="<?= htmlspecialchars($input['amount']) ?>"
                            required
                        >
                    </div>
                </div>

                <!-- Status -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">
                        Status <span class="text-danger">*</span>
                    </label>
                    <select name="status" id="statusSelect" class="form-select" required>
                        <option value="Paid"
                            <?= $input['status'] === 'Paid' ? 'selected' : '' ?>>
                            Paid
                        </option>
                        <option value="Unpaid"
                            <?= $input['status'] === 'Unpaid' ? 'selected' : '' ?>>
                            Unpaid
                        </option>
                    </select>
                </div>

                <!-- Payment Date -->
                <div class="col-md-6" id="paymentDateWrap">
                    <label class="form-label fw-semibold">
                        Payment Date <span class="text-danger">*</span>
                    </label>
                    <input
                        type="date"
                        name="payment_date"
                        id="paymentDate"
                        class="form-control"
                        value="<?= htmlspecialchars($input['payment_date']) ?>"
                        max="<?= date('Y-m-d') ?>"
                    >
                </div>

                <!-- Remarks -->
                <div class="col-12">
                    <label class="form-label fw-semibold">Remarks</label>
                    <input
                        type="text"
                        name="remarks"
                        class="form-control"
                        placeholder="e.g. Paid via bank transfer, late payment, etc."
                        value="<?= htmlspecialchars($input['remarks']) ?>"
                    >
                </div>

            </div><!-- /.row -->

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="fas fa-save me-2"></i>Save Record
                </button>
                <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
            </div>

        </form>
    </div>
</div>

<script>
// ── Auto-fill amount from class fee when student selected ────
const studentSelect = document.getElementById('studentSelect');
const amountInput   = document.getElementById('amountInput');
const classInfo     = document.getElementById('classInfo');
const className     = document.getElementById('className');
const classFee      = document.getElementById('classFee');

studentSelect.addEventListener('change', function () {
    const opt = this.options[this.selectedIndex];
    const fee = opt.dataset.fee;
    const cls = opt.dataset.class;
    if (fee && cls) {
        amountInput.value = fee;
        className.textContent = cls;
        classFee.textContent  = 'PKR ' + parseInt(fee).toLocaleString();
        classInfo.classList.remove('d-none');
    } else {
        amountInput.value = '';
        classInfo.classList.add('d-none');
    }
});

// ── Trigger on page load if student pre-selected ─────────────
if (studentSelect.value) studentSelect.dispatchEvent(new Event('change'));

// ── Show/hide payment date based on status ───────────────────
const statusSelect      = document.getElementById('statusSelect');
const paymentDateWrap   = document.getElementById('paymentDateWrap');
const paymentDateInput  = document.getElementById('paymentDate');

function togglePaymentDate() {
    if (statusSelect.value === 'Unpaid') {
        paymentDateWrap.style.opacity = '0.4';
        paymentDateInput.removeAttribute('required');
        paymentDateInput.value = '';
    } else {
        paymentDateWrap.style.opacity = '1';
        paymentDateInput.setAttribute('required', 'required');
        if (!paymentDateInput.value) {
            paymentDateInput.value = '<?= date('Y-m-d') ?>';
        }
    }
}
statusSelect.addEventListener('change', togglePaymentDate);
togglePaymentDate();
</script>

<?php require_once '../../includes/footer.php'; ?>