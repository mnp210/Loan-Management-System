<?php include 'db_connect.php'; ?>

<div class="container-fluid">
	<div class="col-lg-12">
		<div class="card">
			<div class="card-header">
				<large class="card-title">
					<b>Borrower List</b>
				</large>
				<button class="btn btn-primary btn-block col-md-2 float-right" type="button" id="new_borrower"><i class="fa fa-plus"></i> New Borrower</button>
			</div>
			<div class="card-body">
				<table class="table table-bordered" id="borrower-list">
					<colgroup>
						<col width="5%">
						<col width="35%">
						<col width="30%">
						<col width="20%">
						<col width="10%">
					</colgroup>
					<thead>
						<tr>
							<th class="text-center">#</th>
							<th class="text-center">Borrower</th>
							<th class="text-center">Current Loan</th>
							<th class="text-center">Next Payment Schedule</th>
							<th class="text-center">Action</th>
						</tr>
					</thead>
					<tbody>
						<?php
						$i = 1;
						$qry = $conn->query("SELECT * FROM borrowers order by id desc");
						while($row = $qry->fetch_assoc()):
							// Query active loan (status = 2) or most recent application
							$b_id = intval($row['id']);
							$loan_stmt = $conn->prepare("SELECT l.*, t.type_name, p.months, p.interest_percentage, p.penalty_rate 
								FROM loan_list l 
								LEFT JOIN loan_types t ON t.id = l.loan_type_id 
								LEFT JOIN loan_plan p ON p.id = l.plan_id 
								WHERE l.borrower_id = ? AND l.status = 2 
								ORDER BY l.id DESC LIMIT 1");
							$loan_stmt->bind_param("i", $b_id);
							$loan_stmt->execute();
							$active_loan = $loan_stmt->get_result()->fetch_assoc();
							$loan_stmt->close();

							$current_loan_html = '<span class="text-muted">None</span>';
							$next_sched_html = '<span class="text-muted">N/A</span>';

							if ($active_loan) {
								$monthly = ($active_loan['amount'] + ($active_loan['amount'] * ($active_loan['interest_percentage'] / 100))) / max(1, $active_loan['months']);
								$current_loan_html = '<p class="m-0">Ref: <b>' . escape($active_loan['ref_no']) . '</b></p>';
								$current_loan_html .= '<p class="m-0"><small>Type: <b>' . escape($active_loan['type_name'] ?? 'N/A') . '</b></small></p>';
								$current_loan_html .= '<p class="m-0"><small>Amount: <b>' . number_format($active_loan['amount'], 2) . '</b></small></p>';
								$current_loan_html .= '<p class="m-0"><small>Plan: <b>' . escape($active_loan['months']) . ' mos (' . escape($active_loan['interest_percentage']) . '%)</b></small></p>';

								// Query payment count to determine schedule offset
								$l_id = intval($active_loan['id']);
								$p_stmt = $conn->prepare("SELECT count(id) as paid_count FROM payments WHERE loan_id = ?");
								$p_stmt->bind_param("i", $l_id);
								$p_stmt->execute();
								$p_res = $p_stmt->get_result()->fetch_assoc();
								$paid_count = intval($p_res['paid_count'] ?? 0);
								$p_stmt->close();

								// Fetch next unpaid schedule
								$sch_stmt = $conn->prepare("SELECT date_due FROM loan_schedules WHERE loan_id = ? ORDER BY date(date_due) ASC LIMIT 1 OFFSET ?");
								$sch_stmt->bind_param("ii", $l_id, $paid_count);
								$sch_stmt->execute();
								$sch_res = $sch_stmt->get_result()->fetch_assoc();
								$sch_stmt->close();

								if ($sch_res && !empty($sch_res['date_due'])) {
									$due_date = $sch_res['date_due'];
									$is_overdue = (date('Ymd', strtotime($due_date)) < date('Ymd'));
									$installment_num = $paid_count + 1;
									$next_sched_html = '<p class="m-0">Installment #' . $installment_num . ' of ' . escape($active_loan['months']) . '</p>';
									$next_sched_html .= '<p class="m-0">Due: <b>' . date('M d, Y', strtotime($due_date)) . '</b></p>';
									$next_sched_html .= '<p class="m-0"><small>Monthly: <b>' . number_format($monthly, 2) . '</b></small></p>';
									if ($is_overdue) {
										$next_sched_html .= '<span class="badge badge-danger">Overdue</span>';
									} else {
										$next_sched_html .= '<span class="badge badge-success">On Schedule</span>';
									}
								} else {
									$next_sched_html = '<span class="badge badge-info">Fully Settled</span>';
								}
							} else {
								// Check for any pending application
								$pending_stmt = $conn->prepare("SELECT ref_no, status FROM loan_list WHERE borrower_id = ? AND status IN (0, 1) ORDER BY id DESC LIMIT 1");
								$pending_stmt->bind_param("i", $b_id);
								$pending_stmt->execute();
								$pending = $pending_stmt->get_result()->fetch_assoc();
								$pending_stmt->close();

								if ($pending) {
									$status_label = ($pending['status'] == 0) ? '<span class="badge badge-warning">For Approval</span>' : '<span class="badge badge-info">Approved</span>';
									$current_loan_html = '<p class="m-0">Ref: <b>' . escape($pending['ref_no']) . '</b></p>' . $status_label;
								}
							}
						?>
						 <tr>
						 	<td class="text-center"><?php echo $i++; ?></td>
						 	<td>
						 		<p class="m-0">Name: <b><?php echo escape(ucwords(trim($row['lastname'] . ", " . $row['firstname'] . ' ' . $row['middlename']))); ?></b></p>
						 		<p class="m-0"><small>Address: <b><?php echo escape($row['address']); ?></b></small></p>
						 		<p class="m-0"><small>Contact #: <b><?php echo escape($row['contact_no']); ?></b></small></p>
						 		<p class="m-0"><small>Email: <b><?php echo escape($row['email']); ?></b></small></p>
						 		<p class="m-0"><small>Tax ID: <b><?php echo escape($row['tax_id'] ?: 'N/A'); ?></b></small></p>
						 	</td>
						 	<td><?php echo $current_loan_html; ?></td>
						 	<td><?php echo $next_sched_html; ?></td>
						 	<td class="text-center">
						 			<button class="btn btn-outline-primary btn-sm edit_borrower" type="button" data-id="<?php echo $row['id']; ?>"><i class="fa fa-edit"></i></button>
						 			<button class="btn btn-outline-danger btn-sm delete_borrower" type="button" data-id="<?php echo $row['id']; ?>"><i class="fa fa-trash"></i></button>
						 	</td>
						 </tr>
						<?php endwhile; ?>
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
	$('#borrower-list').dataTable();
	$('#new_borrower').click(function(){
		uni_modal("New Borrower", "manage_borrower.php", 'mid-large');
	});
	$('.edit_borrower').click(function(){
		uni_modal("Edit Borrower", "manage_borrower.php?id=" + $(this).attr('data-id'), 'mid-large');
	});
	$('.delete_borrower').click(function(){
		_conf("Are you sure you want to delete this borrower?", "delete_borrower", [$(this).attr('data-id')]);
	});
	function delete_borrower($id){
		start_load();
		$.ajax({
			url: 'ajax.php?action=delete_borrower',
			method: 'POST',
			data: { id: $id, csrf_token: $('meta[name="csrf-token"]').attr('content') },
			success: function(resp){
				if(resp == 1){
					alert_toast("Borrower successfully deleted", 'success');
					setTimeout(function(){
						location.reload();
					}, 1500);
				} else {
					alert_toast("An error occurred while deleting borrower", 'danger');
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
