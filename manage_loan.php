<?php 
require_once __DIR__ . '/db_connect.php';

$meta = [];
if(isset($_GET['id'])){
	$stmt = $conn->prepare("SELECT * FROM loan_list WHERE id = ?");
	$l_id = intval($_GET['id']);
	$stmt->bind_param("i", $l_id);
	$stmt->execute();
	$res = $stmt->get_result();
	if($res && $res->num_rows > 0){
		$meta = $res->fetch_assoc();
	}
	$stmt->close();
}
?>
<div class="container-fluid">
	<div class="col-lg-12">
	<form action="" id="loan-application">
		<input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
		<input type="hidden" name="id" value="<?php echo escape($meta['id'] ?? ''); ?>">
		<div class="row">
			<div class="col-md-6">
				<label class="control-label">Borrower</label>
				<?php
				$borrower = $conn->query("SELECT *, concat(lastname, ', ', firstname, ' ', middlename) as name FROM borrowers order by concat(lastname, ', ', firstname, ' ', middlename) asc");
				?>
				<select name="borrower_id" id="borrower_id" class="custom-select browser-default select2" required>
					<option value=""></option>
						<?php while($row = $borrower->fetch_assoc()): ?>
							<option value="<?php echo $row['id']; ?>" <?php echo isset($meta['borrower_id']) && $meta['borrower_id'] == $row['id'] ? "selected" : ''; ?>>
								<?php echo escape($row['name']) . ' | Tax ID: ' . escape($row['tax_id'] ?: 'N/A'); ?>
							</option>
						<?php endwhile; ?>
				</select>
			</div>
			<div class="col-md-6">
				<label class="control-label">Loan Type</label>
				<?php
				$type = $conn->query("SELECT * FROM loan_types order by type_name asc");
				?>
				<select name="loan_type_id" id="loan_type_id" class="custom-select browser-default select2" required>
					<option value=""></option>
						<?php while($row = $type->fetch_assoc()): ?>
							<option value="<?php echo $row['id']; ?>" <?php echo isset($meta['loan_type_id']) && $meta['loan_type_id'] == $row['id'] ? "selected" : ''; ?>>
								<?php echo escape($row['type_name']); ?>
							</option>
						<?php endwhile; ?>
				</select>
			</div>
		</div>

		<div class="row mt-2">
			<div class="col-md-6">
				<label class="control-label">Loan Plan</label>
				<?php
				$plan = $conn->query("SELECT * FROM loan_plan order by months desc");
				?>
				<select name="plan_id" id="plan_id" class="custom-select browser-default select2" required>
					<option value=""></option>
						<?php while($row = $plan->fetch_assoc()): ?>
							<option value="<?php echo $row['id']; ?>" <?php echo isset($meta['plan_id']) && $meta['plan_id'] == $row['id'] ? "selected" : ''; ?> 
								data-months="<?php echo $row['months']; ?>" 
								data-interest_percentage="<?php echo $row['interest_percentage']; ?>" 
								data-penalty_rate="<?php echo $row['penalty_rate']; ?>">
								<?php echo escape($row['months']) . ' month/s [ ' . escape($row['interest_percentage']) . '%, ' . escape($row['penalty_rate']) . '% penalty ]'; ?>
							</option>
						<?php endwhile; ?>
				</select>
				<small class="text-muted">months [ interest%, penalty% ]</small>
			</div>
			<div class="form-group col-md-6">
				<label class="control-label">Loan Amount</label>
				<input type="number" name="amount" class="form-control text-right" step="any" min="1" required value="<?php echo escape($meta['amount'] ?? ''); ?>">
			</div>
		</div>

		<div class="row mt-2">
			<div class="form-group col-md-8">
				<label class="control-label">Purpose</label>
				<textarea name="purpose" id="" cols="30" rows="2" class="form-control" required><?php echo escape($meta['purpose'] ?? ''); ?></textarea>
			</div>
			<div class="form-group col-md-4 d-flex align-items-end">
				<button class="btn btn-outline-primary btn-block mb-3" type="button" id="calculate">Calculate Amortization</button>
			</div>
		</div>

		<div id="calculation_table">
		</div>

		<?php if(isset($meta['status'])): ?>
		<div class="row">
			<div class="form-group col-md-6">
				<label class="control-label">Status</label>
				<select class="custom-select browser-default" name="status">
					<option value="0" <?php echo $meta['status'] == 0 ? "selected" : ''; ?>>For Approval</option>
					<option value="1" <?php echo $meta['status'] == 1 ? "selected" : ''; ?>>Approved</option>
					<?php if($meta['status'] != 4): ?>
					<option value="2" <?php echo $meta['status'] == 2 ? "selected" : ''; ?>>Released</option>
					<?php endif; ?>
					<?php if($meta['status'] == 2): ?>
					<option value="3" <?php echo $meta['status'] == 3 ? "selected" : ''; ?>>Complete</option>
					<?php endif; ?>
					<?php if($meta['status'] != 2): ?>
					<option value="4" <?php echo $meta['status'] == 4 ? "selected" : ''; ?>>Denied</option>
					<?php endif; ?>
				</select>
			</div>
		</div>
		<hr>
		<?php endif; ?>

		<div id="row-field">
			<div class="row">
				<div class="col-md-12 text-center">
					<button class="btn btn-primary btn-sm px-4">Save</button>
					<button class="btn btn-secondary btn-sm px-4" type="button" data-dismiss="modal">Cancel</button>
				</div>
			</div>
		</div>
	</form>
	</div>
</div>
<script>
	$('.select2').select2({
		placeholder: "Please select here",
		width: "100%"
	});

	$('#calculate').click(function(){
		calculate();
	});

	function calculate(){
		start_load();
		var plan_val = $('#plan_id').val();
		var amount_val = $('[name="amount"]').val();
		if(!plan_val || !amount_val){
			alert_toast("Please select a plan and enter an amount first.", "warning");
			end_load();
			return false;
		}
		var plan = $("#plan_id option[value='" + plan_val + "']");
		$.ajax({
			url: "calculation_table.php",
			method: "POST",
			data: {
				amount: amount_val,
				months: plan.attr('data-months'),
				interest: plan.attr('data-interest_percentage'),
				penalty: plan.attr('data-penalty_rate')
			},
			success: function(resp){
				if(resp){
					$('#calculation_table').html(resp);
					end_load();
				}
			},
			error: function(){
				end_load();
			}
		});
	}

	$('#loan-application').submit(function(e){
		e.preventDefault();
		start_load();
		$.ajax({
			url: 'ajax.php?action=save_loan',
			method: "POST",
			data: $(this).serialize(),
			success: function(resp){
				if(resp == 1){
					$('.modal').modal('hide');
					alert_toast("Loan Data successfully saved.", "success");
					setTimeout(function(){
						location.reload();
					}, 1500);
				} else {
					alert_toast("An error occurred while saving loan.", "danger");
					end_load();
				}
			},
			error: function(){
				alert_toast("Request failed.", "danger");
				end_load();
			}
		});
	});

	$(document).ready(function(){
		if('<?php echo isset($_GET['id']) ? "1" : "0"; ?>' === "1") {
			calculate();
		}
	});
</script>
<style>
	#uni_modal .modal-footer{
		display: none;
	}
</style>
