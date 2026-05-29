<?php
require_once 'config/db.php';
require_once 'includes/auth.php';

// allow customers, mechanics, admins to view invoices with permission checks
checkLogin();

$job = trim($_GET['job'] ?? '');
if ($job === '') {
    die('Invalid invoice request.');
}

try {
    $stmt = $pdo->prepare(
        'SELECT inv.*, j.mechanic_id, j.vehicle_no, v.customer_id, v.brand, v.model, u.name AS mechanic_name, c.name AS customer_name
         FROM invoices inv
         JOIN job_cards j ON inv.job_card_id = j.job_card_id
         JOIN vehicles v ON j.vehicle_no = v.vehicle_no
         LEFT JOIN users u ON j.mechanic_id = u.id
         LEFT JOIN users c ON v.customer_id = c.id
         WHERE inv.job_card_id = ?'
    );
    $stmt->execute([$job]);
    $inv = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$inv) throw new Exception('Invoice not found.');

    // permission: admin, assigned mechanic, or owning customer
    $allowed = false;
    if ($_SESSION['user_role'] === 'ADMIN') $allowed = true;
    if ($_SESSION['user_role'] === 'MECHANIC' && intval($_SESSION['user_id']) === intval($inv['mechanic_id'])) $allowed = true;
    if ($_SESSION['user_role'] === 'CUSTOMER' && intval($_SESSION['user_id']) === intval($inv['customer_id'])) $allowed = true;
    if (!$allowed) throw new Exception('You are not authorized to view this invoice.');

} catch (Exception $e) {
    die(htmlspecialchars($e->getMessage()));
}
// Handle customer payment action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'pay_invoice') {
    $method = $_POST['payment_method'] ?? 'UPI';
    try {
        if ($_SESSION['user_role'] !== 'CUSTOMER') throw new Exception('Only customers can make payments here.');
        // verify ownership
        if (intval($_SESSION['user_id']) !== intval($inv['customer_id'])) throw new Exception('You are not authorized to pay this invoice.');

        $u = $pdo->prepare('UPDATE invoices SET payment_status = ?, payment_method = ? WHERE job_card_id = ?');
        $u->execute(['PAID', $method, $job]);

        // reload to reflect payment
        header('Location: invoices.php?job=' . urlencode($job));
        exit;
    } catch (Exception $e) {
        $payment_error = $e->getMessage();
    }
}

include 'includes/header.php';
?>
<div class="main-wrapper" style="padding: 30px;">
    <div class="invoice-container">
        <div class="premium-invoice">
            <div class="premium-header">
                <div class="premium-brand">
                    <img src="includes/image/8099LO.png" alt="8099 PitStop">
                    <div>
                        <h3 style="margin:0;">8099 PitStop</h3>
                        <div class="small-note">Precision engineering • Premium workshop services</div>
                    </div>
                </div>

                <div class="billing-block">
                    <div style="font-weight:800; font-size:18px;">Invoice #<?= htmlspecialchars($inv['invoice_no']) ?></div>
                    <div class="small-note">Job: <?= htmlspecialchars($inv['job_card_id']) ?></div>
                    <div class="small-note">Vehicle: <?= htmlspecialchars($inv['brand'].' '.$inv['model'].' / '.$inv['vehicle_no']) ?></div>
                    <div style="margin-top:8px;">
                        <?php if ($inv['payment_status'] === 'PAID'): ?>
                            <span class="paid-badge">PAID</span>
                        <?php else: ?>
                            <span class="unpaid-badge">UNPAID</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div style="display:flex; gap:28px; margin-top:18px; align-items:flex-start;">
                <div style="flex:1;">
                    <div style="font-weight:700;">Billed To</div>
                    <div style="margin-top:6px;"><strong><?= htmlspecialchars($inv['customer_name']) ?></strong></div>
                    <div class="small-note">Mechanic: <?= htmlspecialchars($inv['mechanic_name'] ?? 'N/A') ?></div>
                </div>
                <div style="width:280px;" class="billing-block">
                    <div class="small-note">Invoice Date</div>
                    <div style="font-weight:700;"><?= htmlspecialchars(date('d M Y', strtotime($inv['invoice_date']))) ?></div>
                    <div class="small-note" style="margin-top:8px;">Payment Method</div>
                    <div style="font-weight:700;"><?= htmlspecialchars($inv['payment_method'] ?: '—') ?></div>
                </div>
            </div>

            <div class="line-items">
                <div class="line-item" style="border-bottom:1px solid rgba(255,255,255,0.03); padding-bottom:12px;">
                    <div>
                        <div style="font-weight:700;">Service Charges</div>
                        <div class="desc">Standard and custom service items</div>
                    </div>
                    <div style="font-weight:800;">₹<?= number_format($inv['service_charges'],2) ?></div>
                </div>
                <div class="line-item">
                    <div>
                        <div style="font-weight:700;">Parts Charges</div>
                        <div class="desc">Parts and consumables</div>
                    </div>
                    <div style="font-weight:800;">₹<?= number_format($inv['parts_charges'],2) ?></div>
                </div>

                <div class="line-item" style="border-top:1px dashed rgba(255,255,255,0.04); margin-top:12px; padding-top:12px;">
                    <div class="desc">CGST (9%)</div>
                    <div>₹<?= number_format($inv['cgst'],2) ?></div>
                </div>
                <div class="line-item">
                    <div class="desc">SGST (9%)</div>
                    <div>₹<?= number_format($inv['sgst'],2) ?></div>
                </div>

                <div class="line-item" style="margin-top:12px; font-size:1.1rem; border-top:1px solid rgba(255,255,255,0.04); padding-top:12px;">
                    <div style="font-weight:900;">Total Payable</div>
                    <div style="font-weight:900; font-size:1.25rem;">₹<?= number_format($inv['total_payable'],2) ?></div>
                </div>
            </div>

            <div class="small-note">Thank you for choosing 8099 PitStop. Please keep this invoice for your records.</div>

            <div class="invoice-actions" style="margin-top:18px; display:flex; gap:12px;">
                <button type="button" onclick="window.print()" class="btn-primary">Download / Print</button>
                <?php if ($_SESSION['user_role'] === 'CUSTOMER' && $inv['payment_status'] !== 'PAID'): ?>
                    <form method="POST" action="invoices.php?job=<?= urlencode($job) ?>" style="margin:0;">
                        <input type="hidden" name="action" value="pay_invoice">
                        <input type="hidden" name="payment_method" value="UPI">
                        <button type="submit" class="btn-neon">Pay Now — ₹<?= number_format($inv['total_payable'],2) ?></button>
                    </form>
                <?php endif; ?>
                <a href="<?= $_SESSION['user_role'] === 'ADMIN' ? 'admin_dash.php' : ($_SESSION['user_role'] === 'MECHANIC' ? 'mechanic_dash.php' : 'customer_dash.php') ?>" class="btn-ghost">Back</a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
