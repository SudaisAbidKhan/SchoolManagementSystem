<?php
// ============================================================
//  modules/expenses/report.php
//  Monthly expense report with breakdown + chart
// ============================================================

$page_title = "Expense Report";
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
require_once '../../config/db.php';

// ── Selected month (default: current month) ──────────────────
$month_year = trim($_GET['month_year'] ?? date('Y-m'));

// Validate format YYYY-MM
if (!preg_match('/^\d{4}-\d{2}$/', $month_year)) {
    $month_year = date('Y-m');
}

$month_label  = date('F Y', strtotime($month_year . '-01'));
$prev_month   = date('Y-m', strtotime($month_year . '-01 -1 month'));
$next_month   = date('Y-m', strtotime($month_year . '-01 +1 month'));
$is_future    = $next_month > date('Y-m');

// ── All available months ─────────────────────────────────────
$avail_months = mysqli_query($conn, "
    SELECT DISTINCT DATE_FORMAT(date,'%Y-%m') AS ym,
                    DATE_FORMAT(date,'%M %Y') AS label
    FROM expenses
    ORDER BY ym DESC
");

// ── Expenses for selected month ──────────────────────────────
$stmt = mysqli_prepare($conn, "
    SELECT * FROM expenses
    WHERE DATE_FORMAT(date,'%Y-%m') = ?
    ORDER BY amount DESC
");
mysqli_stmt_bind_param($stmt, "s", $month_year);
mysqli_stmt_execute($stmt);
$expenses = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

// ── Monthly totals for last 6 months (for trend chart) ───────
$trend_data = [];
for ($i = 5; $i >= 0; $i--) {
    $m = date('Y-m', strtotime("-$i months"));
    $l = date('M Y', strtotime("-$i months"));
    $r = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT COALESCE(SUM(amount),0) AS total
        FROM expenses
        WHERE DATE_FORMAT(date,'%Y-%m') = '$m'
    "));
    $trend_data[] = ['month' => $l, 'total' => (float)$r['total']];
}

// ── Summary for selected month ────────────────────────────────
$summary = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT
        COUNT(*)                 AS total_records,
        COALESCE(SUM(amount),0)  AS total_amount,
        COALESCE(MAX(amount),0)  AS max_amount,
        COALESCE(MIN(amount),0)  AS min_amount,
        COALESCE(AVG(amount),0)  AS avg_amount
    FROM expenses
    WHERE DATE_FORMAT(date,'%Y-%m') = '$month_year'
"));

// ── Previous month total for comparison ──────────────────────
$prev_total = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COALESCE(SUM(amount),0) AS total
    FROM expenses
    WHERE DATE_FORMAT(date,'%Y-%m') = '$prev_month'
"))['total'];

$vs_prev = $prev_total > 0
    ? (($summary['total_amount'] - $prev_total) / $prev_total) * 100
    : null;

// ── Fee collection for selected month (for net calc) ─────────
$fee_collected = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COALESCE(SUM(amount),0) AS total
    FROM fees
    WHERE status = 'Paid'
      AND DATE_FORMAT(payment_date,'%Y-%m') = '$month_year'
"))['total'];

$net = $fee_collected - $summary['total_amount'];

// ── Rows array for chart ──────────────────────────────────────
$expense_rows = [];
while ($row = mysqli_fetch_assoc($expenses)) {
    $expense_rows[] = $row;
}
?>

<!-- ── PAGE HEADER ────────────────────────────────────────── -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0">Monthly Expense Report</h4>
        <small class="text-muted">Detailed breakdown for
            <strong><?= $month_label ?></strong>
        </small>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-secondary">
            <i class="fas fa-print me-2"></i>Print
        </button>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back
        </a>
    </div>
</div>

<!-- ── MONTH NAVIGATION ───────────────────────────────────── -->
<div class="card mb-4">
    <div class="card-body py-3">
        <div class="row g-2 align-items-center">

            <!-- Navigator -->
            <div class="col-md-5 d-flex align-items-center gap-2">
                <a href="report.php?month_year=<?= $prev_month ?>"
                   class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <span class="fw-bold fs-6 mx-2"><?= $month_label ?></span>
                <a href="report.php?month_year=<?= $next_month ?>"
                   class="btn btn-outline-secondary btn-sm
                          <?= $is_future ? 'disabled' : '' ?>">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </div>

            <!-- Dropdown -->
            <div class="col-md-4">
                <form method="GET" action="report.php" class="d-flex gap-2">
                    <select name="month_year" class="form-select form-select-sm">
                        <?php
                        mysqli_data_seek($avail_months, 0);
                        while ($m = mysqli_fetch_assoc($avail_months)):
                        ?>
                        <option value="<?= $m['ym'] ?>"
                            <?= $month_year === $m['ym'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($m['label']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm">Go</button>
                </form>
            </div>

            <!-- Current month shortcut -->
            <div class="col-md-3 text-md-end">
                <a href="report.php?month_year=<?= date('Y-m') ?>"
                   class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-calendar-check me-1"></i>This Month
                </a>
            </div>

        </div>
    </div>
</div>

<?php if (empty($expense_rows)): ?>
    <div class="card">
        <div class="card-body text-center text-muted py-5">
            <i class="fas fa-receipt fa-3x mb-3 d-block opacity-25"></i>
            No expenses recorded for <strong><?= $month_label ?></strong>.
            <br>
            <a href="add.php" class="btn btn-primary mt-3">
                <i class="fas fa-plus me-2"></i>Add Expense
            </a>
        </div>
    </div>
<?php else: ?>

<!-- ── KPI CARDS ──────────────────────────────────────────── -->
<div class="row g-3 mb-4">

    <!-- Total Expenses -->
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-3">
                <div class="text-muted small mb-1">Total Expenses</div>
                <div class="fs-5 fw-bold text-danger">
                    PKR <?= number_format($summary['total_amount'], 0) ?>
                </div>
                <?php if ($vs_prev !== null): ?>
                <div class="small mt-1
                    <?= $vs_prev > 0 ? 'text-danger' : 'text-success' ?>">
                    <i class="fas fa-arrow-<?= $vs_prev > 0 ? 'up' : 'down' ?> me-1"></i>
                    <?= abs(round($vs_prev, 1)) ?>% vs last month
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Fee Collected -->
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-3">
                <div class="text-muted small mb-1">Fee Collected</div>
                <div class="fs-5 fw-bold text-success">
                    PKR <?= number_format($fee_collected, 0) ?>
                </div>
                <div class="small text-muted mt-1">This month</div>
            </div>
        </div>
    </div>

    <!-- Net Balance -->
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-3">
                <div class="text-muted small mb-1">Net Balance</div>
                <div class="fs-5 fw-bold
                    <?= $net >= 0 ? 'text-success' : 'text-danger' ?>">
                    PKR <?= number_format(abs($net), 0) ?>
                </div>
                <div class="small <?= $net >= 0 ? 'text-success' : 'text-danger' ?> mt-1">
                    <?= $net >= 0 ? 'Surplus' : 'Deficit' ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Records & Avg -->
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-3">
                <div class="text-muted small mb-1">
                    <?= $summary['total_records'] ?> Records
                </div>
                <div class="fs-6 fw-bold text-primary">
                    Avg PKR <?= number_format($summary['avg_amount'], 0) ?>
                </div>
                <div class="small text-muted mt-1">per expense</div>
            </div>
        </div>
    </div>

</div>

<div class="row g-4 mb-4">

    <!-- ── BREAKDOWN TABLE ───────────────────────────────── -->
    <div class="col-md-7">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>
                    <i class="fas fa-list me-2 text-danger"></i>
                    Expense Breakdown — <?= $month_label ?>
                </span>
                <span class="badge bg-danger">
                    <?= $summary['total_records'] ?> items
                </span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Title</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Share</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $i = 1;
                            foreach ($expense_rows as $row):
                                $share = $summary['total_amount'] > 0
                                    ? ($row['amount'] / $summary['total_amount']) * 100
                                    : 0;
                            ?>
                            <tr>
                                <td class="text-muted small"><?= $i++ ?></td>
                                <td>
                                    <div class="fw-semibold">
                                        <?= htmlspecialchars($row['title']) ?>
                                    </div>
                                    <?php if (!empty($row['description'])): ?>
                                    <small class="text-muted">
                                        <?= htmlspecialchars(
                                            strlen($row['description']) > 50
                                                ? substr($row['description'], 0, 50) . '…'
                                                : $row['description']
                                        ) ?>
                                    </small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted small">
                                    <?= date('d M', strtotime($row['date'])) ?>
                                </td>
                                <td class="fw-semibold text-danger">
                                    PKR <?= number_format($row['amount'], 0) ?>
                                </td>
                                <td style="min-width:100px;">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1"
                                             style="height:6px;">
                                            <div class="progress-bar bg-danger"
                                                 style="width:<?= $share ?>%"></div>
                                        </div>
                                        <small class="text-muted">
                                            <?= round($share, 1) ?>%
                                        </small>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-light fw-bold">
                                <td colspan="3" class="text-end">Total:</td>
                                <td class="text-danger">
                                    PKR <?= number_format($summary['total_amount'], 0) ?>
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ── PIE CHART ─────────────────────────────────────── -->
    <div class="col-md-5">
        <div class="card h-100">
            <div class="card-header">
                <i class="fas fa-chart-pie me-2 text-danger"></i>
                Expense Distribution
            </div>
            <div class="card-body d-flex flex-column align-items-center
                        justify-content-center">
                <canvas id="pieChart" style="max-height:240px;"></canvas>
            </div>
        </div>
    </div>

</div>

<!-- ── TREND CHART (last 6 months) ───────────────────────── -->
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-chart-line me-2 text-primary"></i>
        6-Month Expense Trend
    </div>
    <div class="card-body">
        <canvas id="trendChart" height="90"></canvas>
    </div>
</div>

<!-- ── STATS ROW ──────────────────────────────────────────── -->
<div class="row g-3">
    <div class="col-md-4">
        <div class="card border-start border-danger border-3">
            <div class="card-body py-3">
                <div class="text-muted small">Highest Expense</div>
                <div class="fw-bold text-danger">
                    PKR <?= number_format($summary['max_amount'], 0) ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-start border-success border-3">
            <div class="card-body py-3">
                <div class="text-muted small">Lowest Expense</div>
                <div class="fw-bold text-success">
                    PKR <?= number_format($summary['min_amount'], 0) ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-start border-primary border-3">
            <div class="card-body py-3">
                <div class="text-muted small">Previous Month Total</div>
                <div class="fw-bold text-primary">
                    PKR <?= number_format($prev_total, 0) ?>
                    <small class="text-muted fw-normal">
                        (<?= date('M Y', strtotime($prev_month . '-01')) ?>)
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<!-- ── PRINT STYLES ───────────────────────────────────────── -->
<style>
@media print {
    #sidebar, .navbar, .print-toolbar,
    .btn, form, .nav-tabs { display: none !important; }
    #pageContent { margin-left: 0 !important; }
    .card { box-shadow: none !important; border: 1px solid #dee2e6 !important; }
}
</style>

<!-- ── CHARTS ─────────────────────────────────────────────── -->
<?php if (!empty($expense_rows)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// ── Pie chart data ───────────────────────────────────────────
const pieLabels  = <?= json_encode(array_column($expense_rows, 'title')) ?>;
const pieAmounts = <?= json_encode(array_map(fn($r) => (float)$r['amount'], $expense_rows)) ?>;

const palette = [
    '#dc3545','#fd7e14','#ffc107','#28a745','#20c997',
    '#0dcaf0','#0d6efd','#6610f2','#d63384','#6c757d',
];

new Chart(document.getElementById('pieChart'), {
    type: 'doughnut',
    data: {
        labels: pieLabels,
        datasets: [{
            data: pieAmounts,
            backgroundColor: palette.slice(0, pieLabels.length),
            borderWidth: 2,
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } },
            tooltip: {
                callbacks: {
                    label: ctx =>
                        ' PKR ' + ctx.parsed.toLocaleString() +
                        ' (' + Math.round(ctx.parsed /
                            ctx.dataset.data.reduce((a,b)=>a+b,0)*100) + '%)'
                }
            }
        }
    }
});

// ── Trend chart ──────────────────────────────────────────────
const trendData = <?= json_encode($trend_data) ?>;

new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: {
        labels: trendData.map(d => d.month),
        datasets: [{
            label: 'Monthly Expenses (PKR)',
            data: trendData.map(d => d.total),
            borderColor: '#dc3545',
            backgroundColor: 'rgba(220,53,69,0.1)',
            borderWidth: 2,
            pointRadius: 5,
            pointBackgroundColor: '#dc3545',
            fill: true,
            tension: 0.3,
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
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
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>