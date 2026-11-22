<?php include 'db_connect.php'; ?>

<div class="container-fluid">
	<div class="col-lg-12">
		<div class="card">
			<div class="card-header">
				<large class="card-title">
					<b>Loan List</b>
					<button class="btn btn-primary btn-sm btn-block col-md-2 float-right" type="button" id="new_application"><i class="fa fa-plus"></i> Create New Application</button>
				</large>
			</div>
			<div class="card-body">
				<table class="table table-bordered" id="loan-list">
					<colgroup>
						<col width="5%">
						<col width="25%">
						<col width="25%">
						<col width="25%">
						<col width="10%">
						<col width="10%">
					</colgroup>
					<thead>
						<tr>
							<th class="text-center">#</th>
							<th class="text-center">Borrower</th>
							<th class="text-center">Loan Details</th>
							<th class="text-center">Next Payment Details</th>
							<th class="text-center">Status</th>
							<th class="text-center">Action</th>
						</tr>
					</thead>
					<tbody>
						<?php
							$i = 1;
							$type_arr = [];
							$type = $conn->query("SELECT * FROM loan_types");
							if ($type) {
								while($row = $type->fetch_assoc()){
									$type_arr[$row['id']] = $row['type_name'];
								}
							}

							$plan_arr = [];
							$plan = $conn->query("SELECT *, concat(months, ' month/s [ ', interest_percentage, '%, ', penalty_rate, '% ]') as plan FROM loan_plan");
							if ($plan) {
								while($row = $plan->fetch_assoc()){
									$plan_arr[$row['id']] = $row;
								}
							}

							$qry = $conn->query("SELECT l.*, concat(b.lastname, ', ', b.firstname, ' ', b.middlename) as name, b.contact_no, b.address 
								FROM loan_list l 
								INNER JOIN borrowers b ON b.id = l.borrower_id 
								ORDER BY l.id ASC");

							if ($qry) {
								while($row = $qry->fetch_assoc()):
									$p_id = $row['plan_id'];
									$plan_info = $plan_arr[$p_id] ?? ['months' => 1, 'interest_percentage' => 0, 'penalty_rate' => 0, 'plan' => 'N/A'];
									$months = max(1, intval($plan_info['months']));
									$interest_pct = floatval($plan_info['interest_percentage']);
									$penalty_rate = floatval($plan_info['penalty_rate']);

									$monthly = ($row['amount'] + ($row['amount'] * ($interest_pct / 100))) / $months;
									$penalty = $monthly * ($penalty_rate / 100);

									$loan_id = intval($row['id']);
									$p_stmt = $conn->prepare("SELECT amount, penalty_amount FROM payments WHERE loan_id = ?");
									$p_stmt->bind_param("i", $loan_id);
									$p_stmt->execute();
									$payments = $p_stmt->get_result();
									$paid_count = $payments->num_rows;
									$sum_paid = 0;
									while($p = $payments->fetch_assoc()){
										$sum_paid += ($p['amount'] - $p['penalty_amount']);
									}
									$p_stmt->close();

									$next_due = null;
									if($row['status'] == 2):
										$sch_stmt = $conn->prepare("SELECT date_due FROM loan_schedules WHERE loan_id = ? ORDER BY date(date_due) ASC LIMIT 1 OFFSET ?");
										$sch_stmt->bind_param("ii", $loan_id, $paid_count);
										$sch_stmt->execute();
										$sch_res = $sch_stmt->get_result()->fetch_assoc();
										if ($sch_res) {
											$next_due = $sch_res['date_due'];
										}
										$sch_stmt->close();
									endif;
						 ?>
						 <tr>
						 	<td class="text-center"><?php echo $i++; ?></td>
						 	<td>
						 		<p>Name: <b><?php echo escape($row['name']); ?></b></p>
						 		<p><small>Contact #: <b><?php echo escape($row['contact_no']); ?></b></small></p>
						 		<p><small>Address: <b><?php echo escape($row['address']); ?></b></small></p>
						 	</td>
						 	<td>
						 		<p>Reference: <b><?php echo escape($row['ref_no']); ?></b></p>
						 		<p><small>Loan Type: <b><?php echo escape($type_arr[$row['loan_type_id']] ?? 'N/A'); ?></b></small></p>
						 		<p><small>Plan: <b><?php echo escape($plan_info['plan']); ?></b></small></p>
						 		<p><small>Amount: <b><?php echo number_format($row['amount'], 2); ?></b></small></p>
						 		<p><small>Total Payable: <b><?php echo number_format($monthly * $months, 2); ?></b></small></p>
						 		<p><small>Monthly Payable: <b><?php echo number_format($monthly, 2); ?></b></small></p>
						 		<p><small>Overdue Penalty: <b><?php echo number_format($penalty, 2); ?></b></small></p>
						 		<?php if(($row['status'] == 2 || $row['status'] == 3) && !empty($row['date_released'])): ?>
						 		<p><small>Date Released: <b><?php echo date("M d, Y", strtotime($row['date_released'])); ?></b></small></p>
						 		<?php endif; ?>
						 	</td>
						 	<td>
						 		<?php if($row['status'] == 2): ?>
						 			<?php if($next_due): ?>
						 				<?php $is_overdue = (date('Ymd', strtotime($next_due)) < date('Ymd')); ?>
						 				<?php $add = $is_overdue ? $penalty : 0; ?>
						 				<p>Next Due: <b><?php echo date('M d, Y', strtotime($next_due)); ?></b></p>
						 				<p><small>Monthly Amount: <b><?php echo number_format($monthly, 2); ?></b></small></p>
						 				<p><small>Penalty: <b><?php echo number_format($add, 2); ?></b></small></p>
						 				<p><small>Payable Amount: <b><?php echo number_format($monthly + $add, 2); ?></b></small></p>
						 				<?php if($is_overdue): ?>
						 					<span class="badge badge-danger">Overdue</span>
						 				<?php else: ?>
						 					<span class="badge badge-success">On Track</span>
						 				<?php endif; ?>
						 			<?php else: ?>
						 				<span class="badge badge-success">All Installments Paid</span>
						 			<?php endif; ?>
						 		<?php else: ?>
					 				<span class="text-muted">N/A</span>
						 		<?php endif; ?>
						 	</td>
						 	<td class="text-center">
						 		<?php if($row['status'] == 0): ?>
						 			<span class="badge badge-warning">For Approval</span>
						 		<?php elseif($row['status'] == 1): ?>
						 			<span class="badge badge-info">Approved</span>
					 			<?php elseif($row['status'] == 2): ?>
						 			<span class="badge badge-primary">Released</span>
					 			<?php elseif($row['status'] == 3): ?>
						 			<span class="badge badge-success">Completed</span>
					 			<?php elseif($row['status'] == 4): ?>
						 			<span class="badge badge-danger">Denied</span>
						 		<?php endif; ?>
						 	</td>
						 	<td class="text-center">
						 			<button class="btn btn-outline-primary btn-sm edit_loan" type="button" data-id="<?php echo $row['id']; ?>"><i class="fa fa-edit"></i></button>
						 			<button class="btn btn-outline-danger btn-sm delete_loan" type="button" data-id="<?php echo $row['id']; ?>"><i class="fa fa-trash"></i></button>
						 	</td>
						 </tr>
						<?php endwhile; } ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>
<style>
	td p {
		margin: unset;
	}
	td {
		vertical-align: middle !important;
	}
</style>	
<script>
	$('#loan-list').dataTable();
	$('#new_application').click(function(){
		uni_modal("New Loan Application", "manage_loan.php", 'mid-large');
	});
	$('.edit_loan').click(function(){
		uni_modal("Edit Loan", "manage_loan.php?id=" + $(this).attr('data-id'), 'mid-large');
	});
	$('.delete_loan').click(function(){
		_conf("Are you sure you want to delete this loan?", "delete_loan", [$(this).attr('data-id')]);
	});
	function delete_loan($id){
		start_load();
		$.ajax({
			url: 'ajax.php?action=delete_loan',
			method: 'POST',
			data: { id: $id, csrf_token: $('meta[name="csrf-token"]').attr('content') },
			success: function(resp){
				if(resp == 1){
					alert_toast("Loan successfully deleted", 'success');
					setTimeout(function(){
						location.reload();
					}, 1500);
				} else {
					alert_toast("An error occurred while deleting loan", 'danger');
					end_load();
				}
			},
			error: function(){
				alert_toast("Request failed", 'danger');
				end_load();
			}
		});
	}
</script>
