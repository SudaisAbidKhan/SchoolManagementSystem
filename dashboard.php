<?php
// ============================================================
//  dashboard.php
//  Main admin dashboard with summary stats + recent activity
// ============================================================

$page_title = "Dashboard";
require_once 'includes/header.php';
require_once 'includes/sidebar.php';
require_once 'config/db.php';

// ── Stats ────────────────────────────────────────────────────

// Total students
$total_students = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS total FROM students")
)['total'];

// Total staff
$total_staff = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS total FROM staff")
)['total'];

// Total classes
$total_classes = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS total FROM classes")
)['total'];

// Monthly fee collection (current month, paid only)
$monthly_fee = mysqli_fetch_assoc(
    mysqli_query($conn, "
        SELECT COALESCE(SUM(amount), 0) AS total
        FROM fees
        WHERE status = 'Paid'
          AND MONTH(payment_date) = MONTH(CURDATE())
          AND YEAR(payment_date)  = YEAR(CURDATE())
    ")
)['total'];

// Monthly expenses (current month)
$monthly_expenses = mysqli_fetch_assoc(
    mysqli_query($conn, "
        SELECT COALESCE(SUM(amount), 0) AS total
        FROM expenses
        WHERE MONTH(date) = MONTH(CURDATE())
          AND YEAR(date)  = YEAR(CURDATE())
    ")
)['total'];

// Unpaid fees count
$unpaid_fees = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS total FROM fees WHERE status = 'Unpaid'")
)['total'];

// Net balance
$net_balance = $monthly_fee - $monthly_expenses;

// ── Recent Students (latest 5) ───────────────────────────────
$recent_students = mysqli_query($conn, "
    SELECT s.name, s.admission_date, c.name AS class_name, c.section
    FROM students s
    JOIN classes c ON s.class_id = c.id
    ORDER BY s.id DESC
    LIMIT 5
");

// ── Recent Fee Payments (latest 5) ──────────────────────────
$recent_payments = mysqli_query($conn, "
    SELECT s.name AS student_name, c.name AS class_name,
           f.amount, f.payment_date, f.month
    FROM fees f
    JOIN students s ON f.student_id = s.id
    JOIN classes  c ON s.class_id   = c.id
    WHERE f.status = 'Paid'
    ORDER BY f.id DESC
    LIMIT 5
");

// ── Recent Expenses (latest 5) ───────────────────────────────
$recent_expenses = mysqli_query($conn, "
    SELECT title, amount, date
    FROM expenses
    ORDER BY id DESC
    LIMIT 5
");

// ── Fee collection vs expenses (last 6 months) ───────────────
$chart_data = [];
for ($i = 5; $i >= 0; $i--) {
    $month_label = date('M Y', strtotime("-$i months"));
    $m           = date('m',   strtotime("-$i months"));
    $y           = date('Y',   strtotime("-$i months"));

    $fee_row = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT COALESCE(SUM(amount), 0) AS total FROM fees
        WHERE status = 'Paid' AND MONTH(payment_date) = $m AND YEAR(payment_date) = $y
    "));
    $exp_row = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT COALESCE(SUM(amount), 0) AS total FROM expenses
        WHERE MONTH(date) = $m AND YEAR(date) = $y
    "));

    $chart_data[] = [
        'month'    => $month_label,
        'fees'     => (float) $fee_row['total'],
        'expenses' => (float) $exp_row['total'],
    ];
}
?>

<!-- ── PAGE HEADER ────────────────────────────────────────── -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Dashboard</h4>
        <small class="text-muted">Welcome back, <strong><?= htmlspecialchars($_SESSION['admin_username']) ?></strong> &mdash; <?= date('l, d M Y') ?></small>
    </div>
    <span class="badge bg-primary px-3 py-2">
        <i class="fas fa-circle text-success me-1" style="font-size:0.5rem;vertical-align:middle;"></i>
        System Online
    </span>
</div>

<!-- ── STAT CARDS ─────────────────────────────────────────── -->
<div class="row g-3 mb-4">

    <div class="col-6 col-md-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#0d6efd,#4dabf7)">
            <i class="fas fa-user-graduate stat-icon"></i>
            <div>
                <div class="stat-value"><?= $total_students ?></div>
                <div class="stat-label">Total Students</div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#198754,#51cf66)">
            <i class="fas fa-users stat-icon"></i>
            <div>
                <div class="stat-value"><?= $total_staff ?></div>
                <div class="stat-label">Total Staff</div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#0dcaf0,#74c0fc)">
            <i class="fas fa-chalkboard stat-icon"></i>
            <div>
                <div class="stat-value"><?= $total_classes ?></div>
                <div class="stat-label">Total Classes</div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#dc3545,#ff6b6b)">
            <i class="fas fa-exclamation-circle stat-icon"></i>
            <div>
                <div class="stat-value"><?= $unpaid_fees ?></div>
                <div class="stat-label">Unpaid Fees</div>
            </div>
        </div>
    </div>

</div>

<!-- ── FINANCE SUMMARY CARDS ──────────────────────────────── -->
<div class="row g-3 mb-4">

    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle p-3" style="background:#e8f5e9">
                    <i class="fas fa-arrow-down text-success fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small">Fee Collected (This Month)</div>
                    <div class="fw-bold fs-5 text-success">PKR <?= number_format($monthly_fee, 2) ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle p-3" style="background:#fdecea">
                    <i class="fas fa-arrow-up text-danger fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small">Expenses (This Month)</div>
                    <div class="fw-bold fs-5 text-danger">PKR <?= number_format($monthly_expenses, 2) ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle p-3" style="background:<?= $net_balance >= 0 ? '#e8f5e9' : '#fdecea' ?>">
                    <i class="fas fa-wallet fs-4" style="color:<?= $net_balance >= 0 ? '#198754' : '#dc3545' ?>"></i>
                </div>
                <div>
                    <div class="text-muted small">Net Balance (This Month)</div>
                    <div class="fw-bold fs-5" style="color:<?= $net_balance >= 0 ? '#198754' : '#dc3545' ?>">
                        PKR <?= number_format(abs($net_balance), 2) ?>
                        <small class="fs-6"><?= $net_balance >= 0 ? 'Surplus' : 'Deficit' ?></small>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- ── CHART ──────────────────────────────────────────────── -->
<div class="card mb-4">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span><i class="fas fa-chart-bar me-2 text-primary"></i>Fee Collection vs Expenses (Last 6 Months)</span>
    </div>
    <div class="card-body">
        <canvas id="financeChart" height="100"></canvas>
    </div>
</div>

<!-- ── RECENT ACTIVITY TABLES ─────────────────────────────── -->
<div class="row g-3">

    <!-- Recent Students -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-user-graduate me-2 text-primary"></i>Recent Admissions</span>
                <a href="modules/students/index.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Class</th>
                            <th>Admitted</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($recent_students)): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td>
                                <?= htmlspecialchars($row['class_name']) ?>
                                <?= $row['section'] ? '-' . htmlspecialchars($row['section']) : '' ?>
                            </td>
                            <td class="text-muted small"><?= date('d M Y', strtotime($row['admission_date'])) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Payments -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-money-bill-wave me-2 text-success"></i>Recent Payments</span>
                <a href="modules/fees/index.php" class="btn btn-sm btn-outline-success">View All</a>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Month</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($recent_payments)): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['student_name']) ?></td>
                            <td class="text-muted small"><?= htmlspecialchars($row['month']) ?></td>
                            <td class="text-success fw-semibold">PKR <?= number_format($row['amount'], 0) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Expenses -->
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-receipt me-2 text-danger"></i>Recent Expenses</span>
                <a href="modules/expenses/index.php" class="btn btn-sm btn-outline-danger">View All</a>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Amount</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($recent_expenses)): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['title']) ?></td>
                            <td class="text-danger fw-semibold">PKR <?= number_format($row['amount'], 0) ?></td>
                            <td class="text-muted small"><?= date('d M Y', strtotime($row['date'])) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- ── CHART.JS ────────────────────────────────────────────── -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const chartData = <?= json_encode($chart_data) ?>;

new Chart(document.getElementById('financeChart'), {
    type: 'bar',
    data: {
        labels: chartData.map(d => d.month),
        datasets: [
            {
                label:           'Fee Collected (PKR)',
                data:            chartData.map(d => d.fees),
                backgroundColor: 'rgba(25, 135, 84, 0.75)',
                borderRadius:    6,
            },
            {
                label:           'Expenses (PKR)',
                data:            chartData.map(d => d.expenses),
                backgroundColor: 'rgba(220, 53, 69, 0.75)',
                borderRadius:    6,
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' },
            tooltip: {
                callbacks: {
                    label: ctx => ' PKR ' + ctx.parsed.y.toLocaleString()
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: val => 'PKR ' + val.toLocaleString()
                }
            }
        }
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>