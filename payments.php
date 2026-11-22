<?php include 'db_connect.php'; ?>

<div class="container-fluid">
	<div class="col-lg-12">
		<div class="card">
			<div class="card-header">
				<large class="card-title">
					<b>Payment List</b>
					<button class="btn btn-primary btn-sm btn-block col-md-2 float-right" type="button" id="new_payments"><i class="fa fa-plus"></i> New Payment</button>
				</large>
			</div>
			<div class="card-body">
				<table class="table table-bordered" id="payment-list">
					<colgroup>
						<col width="5%">
						<col width="20%">
						<col width="30%">
						<col width="15%">
						<col width="15%">
						<col width="15%">
					</colgroup>
					<thead>
						<tr>
							<th class="text-center">#</th>
							<th class="text-center">Loan Reference No</th>
							<th class="text-center">Payee</th>
							<th class="text-center">Amount Paid</th>
							<th class="text-center">Penalty Paid</th>
							<th class="text-center">Action</th>
						</tr>
					</thead>
					<tbody>
						<?php
							$i = 1;
							$qry = $conn->query("SELECT p.*, l.ref_no, concat(b.lastname, ', ', b.firstname, ' ', b.middlename) as borrower_name 
								FROM payments p 
								INNER JOIN loan_list l ON l.id = p.loan_id 
								INNER JOIN borrowers b ON b.id = l.borrower_id 
								ORDER BY p.id ASC");

							if ($qry) {
								while($row = $qry->fetch_assoc()):
						 ?>
						 <tr>
						 	<td class="text-center"><?php echo $i++; ?></td>
						 	<td>
						 		<b><?php echo escape($row['ref_no']); ?></b>
						 	</td>
						 	<td>
						 		<?php echo escape($row['payee']); ?>
						 		<?php if(!empty($row['date_created'])): ?>
						 			<br><small class="text-muted">Date: <?php echo date('M d, Y h:i A', strtotime($row['date_created'])); ?></small>
						 		<?php endif; ?>
						 	</td>
						 	<td class="text-right">
						 		<?php echo number_format($row['amount'], 2); ?>
						 	</td>
						 	<td class="text-right">
						 		<?php echo number_format($row['penalty_amount'], 2); ?>
						 	</td>
						 	<td class="text-center">
						 			<button class="btn btn-outline-primary btn-sm edit_payment" type="button" data-id="<?php echo $row['id']; ?>"><i class="fa fa-edit"></i></button>
						 			<button class="btn btn-outline-danger btn-sm delete_payment" type="button" data-id="<?php echo $row['id']; ?>"><i class="fa fa-trash"></i></button>
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
	$('#payment-list').dataTable();
	$('#new_payments').click(function(){
		uni_modal("New Payment", "manage_payment.php", 'mid-large');
	});
	$('.edit_payment').click(function(){
		uni_modal("Edit Payment", "manage_payment.php?id=" + $(this).attr('data-id'), 'mid-large');
	});
	$('.delete_payment').click(function(){
		_conf("Are you sure you want to delete this payment?", "delete_payment", [$(this).attr('data-id')]);
	});
	function delete_payment($id){
		start_load();
		$.ajax({
			url: 'ajax.php?action=delete_payment',
			method: 'POST',
			data: { id: $id, csrf_token: $('meta[name="csrf-token"]').attr('content') },
			success: function(resp){
				if(resp == 1){
					alert_toast("Payment successfully deleted", 'success');
					setTimeout(function(){
						location.reload();
					}, 1500);
				} else {
					alert_toast("An error occurred while deleting payment", 'danger');
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
