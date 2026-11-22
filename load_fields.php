<?php 
require_once __DIR__ . '/db_connect.php'; 

$loan_id = intval($_POST['loan_id'] ?? 0);
$payment_id = !empty($_POST['id']) ? intval($_POST['id']) : null;

$payee_val = '';
$amount_val = '';

if ($payment_id) {
	$stmt = $conn->prepare("SELECT * FROM payments WHERE id = ?");
	$stmt->bind_param("i", $payment_id);
	$stmt->execute();
	$p_res = $stmt->get_result();
	if ($p_res && $p_res->num_rows > 0) {
		$p_data = $p_res->fetch_assoc();
		$payee_val = $p_data['payee'];
		$amount_val = $p_data['amount'];
	}
	$stmt->close();
}

$meta = [];
if ($loan_id > 0) {
	$stmt = $conn->prepare("SELECT l.*, concat(b.lastname, ', ', b.firstname, ' ', b.middlename) as name, b.contact_no, b.address,
		p.months, p.interest_percentage, p.penalty_rate
		FROM loan_list l 
		INNER JOIN borrowers b ON b.id = l.borrower_id 
		LEFT JOIN loan_plan p ON p.id = l.plan_id
		WHERE l.id = ?");
	$stmt->bind_param("i", $loan_id);
	$stmt->execute();
	$l_res = $stmt->get_result();
	if ($l_res && $l_res->num_rows > 0) {
		$meta = $l_res->fetch_assoc();
	}
	$stmt->close();
}

if (empty($payee_val) && !empty($meta['name'])) {
	$payee_val = $meta['name'];
}

$months = max(1, intval($meta['months'] ?? 1));
$interest_pct = floatval($meta['interest_percentage'] ?? 0);
$penalty_rate = floatval($meta['penalty_rate'] ?? 0);
$principal = floatval($meta['amount'] ?? 0);

$monthly = ($principal + ($principal * ($interest_pct / 100))) / $months;
$penalty = $monthly * ($penalty_rate / 100);

$p_count_stmt = $conn->prepare("SELECT count(id) as paid_count FROM payments WHERE loan_id = ?");
$p_count_stmt->bind_param("i", $loan_id);
$p_count_stmt->execute();
$paid_count = intval($p_count_stmt->get_result()->fetch_assoc()['paid_count'] ?? 0);
$p_count_stmt->close();

$sch_stmt = $conn->prepare("SELECT date_due FROM loan_schedules WHERE loan_id = ? ORDER BY date(date_due) ASC LIMIT 1 OFFSET ?");
$sch_stmt->bind_param("ii", $loan_id, $paid_count);
$sch_stmt->execute();
$sch_res = $sch_stmt->get_result()->fetch_assoc();
$next_due = $sch_res['date_due'] ?? null;
$sch_stmt->close();

$add = 0;
if ($next_due && (date('Ymd', strtotime($next_due)) < date('Ymd'))) {
	$add = $penalty;
}
?>
<div class="col-lg-12">
<hr>
<div class="row">
	<div class="col-md-6">
		<div class="form-group">
			<label for="">Payee</label>
			<input name="payee" class="form-control" required="" value="<?php echo escape($payee_val); ?>">
		</div>
	</div>
</div>
<hr>
<div class="row">
	<div class="col-md-6">
		<p><small>Next Due Date: <b><?php echo $next_due ? date('M d, Y', strtotime($next_due)) : 'All Schedules Completed'; ?></b></small></p>
		<p><small>Monthly Amount: <b><?php echo number_format($monthly, 2); ?></b></small></p>
		<p><small>Penalty: <b><?php echo number_format($add, 2); ?></b></small></p>
		<p><small>Total Payable Amount: <b><?php echo number_format($monthly + $add, 2); ?></b></small></p>
	</div>
	<div class="col-md-6">
		<div class="form-group">
			<label for="">Amount to Pay</label>
			<input type="number" name="amount" step="any" min="0" class="form-control text-right" required="" value="<?php echo escape($amount_val ?: ($monthly + $add)); ?>">
			<input type="hidden" name="penalty_amount" value="<?php echo $add; ?>">
			<input type="hidden" name="loan_id" value="<?php echo $loan_id; ?>">
			<input type="hidden" name="overdue" value="<?php echo $add > 0 ? 1 : 0; ?>">
		</div>
	</div>
</div>
</div>
