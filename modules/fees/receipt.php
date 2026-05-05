<?php
// ============================================================
//  modules/fees/receipt.php
//  Printable fee receipt — opens in new tab
// ============================================================

require_once '../../includes/auth_check.php';
require_once '../../config/db.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    die("Invalid receipt ID.");
}

// ── Fetch fee + student + class ──────────────────────────────
$stmt = mysqli_prepare($conn, "
    SELECT
        f.id, f.amount, f.month, f.status,
        f.payment_date, f.remarks, f.created_at,
        s.id          AS student_id,
        s.name        AS student_name,
        s.father_name,
        s.cnic,
        s.contact,
        s.address,
        s.admission_date,
        c.name        AS class_name,
        c.section     AS class_section,
        c.fee         AS class_fee
    FROM fees f
    JOIN students s ON f.student_id = s.id
    JOIN classes  c ON s.class_id   = c.id
    WHERE f.id = ? LIMIT 1
");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$receipt = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$receipt) {
    die("Receipt not found.");
}

// ── Receipt number: zero-padded fee ID ──────────────────────
$receipt_no = 'RCP-' . str_pad($receipt['id'], 5, '0', STR_PAD_LEFT);
$class_full = $receipt['class_name'] .
    ($receipt['class_section'] ? ' - ' . $receipt['class_section'] : '');
$is_paid    = $receipt['status'] === 'Paid';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Receipt <?= $receipt_no ?></title>
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; }

        body {
            background: #f4f6f9;
            font-family: 'Segoe UI', sans-serif;
            font-size: 14px;
        }

        /* ── Toolbar (hidden on print) ─── */
        .print-toolbar {
            background: #1e2a3a;
            color: #fff;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }
        @media print { .print-toolbar { display: none !important; } }

        /* ── Receipt wrapper ─────────── */
        .receipt-wrap {
            max-width: 720px;
            margin: 30px auto;
            padding: 0 16px 40px;
        }
        @media print {
            body { background: #fff; }
            .receipt-wrap { margin: 0; padding: 0; max-width: 100%; }
        }

        /* ── Receipt card ────────────── */
        .receipt-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        @media print {
            .receipt-card {
                box-shadow: none;
                border-radius: 0;
            }
        }

        /* ── Header ──────────────────── */
        .receipt-header {
            background: linear-gradient(135deg, #0d6efd, #0a58ca);
            color: #fff;
            padding: 28px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .school-name {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 2px;
        }
        .school-sub {
            font-size: 0.82rem;
            opacity: 0.85;
        }
        .receipt-no-box {
            text-align: right;
        }
        .receipt-no-box .rno {
            font-size: 1.2rem;
            font-weight: 700;
            font-family: monospace;
        }
        .receipt-no-box small {
            font-size: 0.75rem;
            opacity: 0.8;
        }

        /* ── Status banner ───────────── */
        .status-banner {
            padding: 10px 32px;
            font-weight: 600;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .status-banner.paid   { background: #d1fae5; color: #065f46; }
        .status-banner.unpaid { background: #fee2e2; color: #991b1b; }

        /* ── Body ────────────────────── */
        .receipt-body {
            padding: 28px 32px;
        }

        /* ── Section title ───────────── */
        .section-title {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #6c757d;
            font-weight: 600;
            margin-bottom: 10px;
            padding-bottom: 4px;
            border-bottom: 1px solid #f0f0f0;
        }

        /* ── Info grid ───────────────── */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px 24px;
            margin-bottom: 24px;
        }
        .info-item label {
            display: block;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #6c757d;
            margin-bottom: 2px;
        }
        .info-item span {
            font-weight: 600;
            color: #212529;
        }

        /* ── Amount box ──────────────── */
        .amount-box {
            border-radius: 10px;
            padding: 20px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
        }
        .amount-box.paid   { background: #f0fdf4; border: 2px solid #86efac; }
        .amount-box.unpaid { background: #fff1f2; border: 2px solid #fca5a5; }

        .amount-label {
            font-size: 0.8rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .amount-value {
            font-size: 2rem;
            font-weight: 800;
            line-height: 1;
        }
        .amount-box.paid   .amount-value { color: #16a34a; }
        .amount-box.unpaid .amount-value { color: #dc2626; }

        /* ── Watermark PAID ──────────── */
        .watermark {
            font-size: 4.5rem;
            font-weight: 900;
            opacity: 0.06;
            transform: rotate(-20deg);
            position: absolute;
            top: 50%;
            left: 50%;
            translate: -50% -50%;
            pointer-events: none;
            white-space: nowrap;
            color: #16a34a;
        }

        /* ── Footer ──────────────────── */
        .receipt-footer {
            border-top: 1px dashed #dee2e6;
            padding: 16px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.78rem;
            color: #6c757d;
        }
        .sig-line {
            border-top: 1px solid #dee2e6;
            padding-top: 4px;
            text-align: center;
            width: 160px;
            font-size: 0.72rem;
            color: #6c757d;
        }
    </style>
</head>
<body>

<!-- ── PRINT TOOLBAR ──────────────────────────────────────── -->
<div class="print-toolbar">
    <div>
        <i class="fas fa-receipt me-2"></i>
        Fee Receipt &mdash; <?= $receipt_no ?>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-sm btn-primary">
            <i class="fas fa-print me-1"></i>Print
        </button>
        <button onclick="window.location.href='index.php'" class="btn btn-sm btn-outline-light">
            <i class="fas fa-times me-1"></i>Close
        </button>
    </div>
</div>

<!-- ── RECEIPT ────────────────────────────────────────────── -->
<div class="receipt-wrap">
<div class="receipt-card" style="position:relative;overflow:hidden;">

    <?php if ($is_paid): ?>
    <div class="watermark">PAID</div>
    <?php endif; ?>

    <!-- Header -->
    <div class="receipt-header">
        <div>
            <div class="school-name">
                <i class="fas fa-school me-2"></i>School Management System
            </div>
            <div class="school-sub">Fee Payment Receipt</div>
        </div>
        <div class="receipt-no-box">
            <div class="rno"><?= $receipt_no ?></div>
            <small>Receipt Number</small>
        </div>
    </div>

    <!-- Status banner -->
    <div class="status-banner <?= $is_paid ? 'paid' : 'unpaid' ?>">
        <i class="fas <?= $is_paid ? 'fa-check-circle' : 'fa-times-circle' ?>"></i>
        <?= $is_paid
            ? 'PAYMENT RECEIVED'
            : 'PAYMENT PENDING — NOT YET PAID' ?>
    </div>

    <!-- Body -->
    <div class="receipt-body">

        <!-- Amount box -->
        <div class="amount-box <?= $is_paid ? 'paid' : 'unpaid' ?>">
            <div>
                <div class="amount-label">Fee Amount</div>
                <div class="amount-value">
                    PKR <?= number_format($receipt['amount'], 0) ?>
                </div>
                <div style="font-size:0.8rem;color:#6c757d;margin-top:4px;">
                    For: <strong><?= htmlspecialchars($receipt['month']) ?></strong>
                </div>
            </div>
            <div style="font-size:3rem;opacity:0.15;">
                <i class="fas <?= $is_paid ? 'fa-check-circle' : 'fa-hourglass-half' ?>"></i>
            </div>
        </div>

        <!-- Student Info -->
        <div class="section-title">Student Information</div>
        <div class="info-grid">
            <div class="info-item">
                <label>Student Name</label>
                <span><?= htmlspecialchars($receipt['student_name']) ?></span>
            </div>
            <div class="info-item">
                <label>Father's Name</label>
                <span><?= htmlspecialchars($receipt['father_name']) ?></span>
            </div>
            <div class="info-item">
                <label>Class</label>
                <span><?= htmlspecialchars($class_full) ?></span>
            </div>
            <div class="info-item">
                <label>CNIC / B-Form</label>
                <span><?= htmlspecialchars($receipt['cnic'] ?? '—') ?></span>
            </div>
            <div class="info-item">
                <label>Contact</label>
                <span><?= htmlspecialchars($receipt['contact'] ?? '—') ?></span>
            </div>
            <div class="info-item">
                <label>Admission Date</label>
                <span><?= date('d M Y', strtotime($receipt['admission_date'])) ?></span>
            </div>
        </div>

        <!-- Payment Info -->
        <div class="section-title">Payment Information</div>
        <div class="info-grid">
            <div class="info-item">
                <label>Fee Month</label>
                <span><?= htmlspecialchars($receipt['month']) ?></span>
            </div>
            <div class="info-item">
                <label>Monthly Class Fee</label>
                <span>PKR <?= number_format($receipt['class_fee'], 0) ?></span>
            </div>
            <div class="info-item">
                <label>Amount Paid</label>
                <span>PKR <?= number_format($receipt['amount'], 0) ?></span>
            </div>
            <div class="info-item">
                <label>Payment Status</label>
                <span style="color:<?= $is_paid ? '#16a34a' : '#dc2626' ?>">
                    <?= $receipt['status'] ?>
                </span>
            </div>
            <div class="info-item">
                <label>Payment Date</label>
                <span>
                    <?= $receipt['payment_date']
                        ? date('d M Y', strtotime($receipt['payment_date']))
                        : '—' ?>
                </span>
            </div>
            <div class="info-item">
                <label>Record Created</label>
                <span>
                    <?= date('d M Y', strtotime($receipt['created_at'])) ?>
                </span>
            </div>
            <?php if (!empty($receipt['remarks'])): ?>
            <div class="info-item" style="grid-column: span 2;">
                <label>Remarks</label>
                <span><?= htmlspecialchars($receipt['remarks']) ?></span>
            </div>
            <?php endif; ?>
        </div>

    </div><!-- /.receipt-body -->

    <!-- Footer -->
    <div class="receipt-footer">
        <div>
            <i class="fas fa-calendar-alt me-1"></i>
            Printed: <?= date('d M Y, h:i A') ?>
        </div>
        <div class="sig-line">Authorized Signature</div>
    </div>

</div><!-- /.receipt-card -->
</div><!-- /.receipt-wrap -->

</body>
</html>