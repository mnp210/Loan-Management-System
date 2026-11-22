<?php 
require_once __DIR__ . '/db_connect.php'; 

$payment_id = null;
$selected_loan_id = null;

if(isset($_GET['id'])){
	$payment_id = intval($_GET['id']);
	$stmt = $conn->prepare("SELECT * FROM payments WHERE id = ?");
	$stmt->bind_param("i", $payment_id);
	$stmt->execute();
	$res = $stmt->get_result();
	if($res && $res->num_rows > 0){
		$p_row = $res->fetch_assoc();
		$selected_loan_id = $p_row['loan_id'];
	}
	$stmt->close();
}
?>
<div class="container-fluid">
	<div class="col-lg-12">
		<form id="manage-payment">
			<input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
			<input type="hidden" name="id" value="<?php echo escape($payment_id ?? ''); ?>">
			<div class="row">
				<div class="col-md-6">
					<div class="form-group">
						<label for="" class="control-label">Loan Reference No.</label>
						<select name="loan_id" id="loan_id_select" class="custom-select browser-default select2" required>
							<option value=""></option>
							<?php 
							$loans = $conn->query("SELECT l.*, concat(b.lastname, ', ', b.firstname) as borrower_name 
								FROM loan_list l 
								INNER JOIN borrowers b ON b.id = l.borrower_id 
								WHERE l.status = 2 
								ORDER BY l.ref_no ASC");
							if ($loans) {
								while($row = $loans->fetch_assoc()):
							?>
							<option value="<?php echo $row['id']; ?>" <?php echo ($selected_loan_id == $row['id']) ? "selected" : ''; ?>>
								<?php echo escape($row['ref_no']) . ' - ' . escape($row['borrower_name']); ?>
							</option>
							<?php endwhile; } ?>
						</select>
					</div>
				</div>
			</div>
			<div class="row" id="fields">
			</div>
		</form>
	</div>
</div>

<script>
	$('[name="loan_id"]').change(function(){
		load_fields();
	});

	$('.select2').select2({
		placeholder: "Please select here",
		width: "100%"
	});

	function load_fields(){
		var lid = $('[name="loan_id"]').val();
		if (!lid) {
			$('#fields').html('');
			return;
		}
		start_load();
		$.ajax({
			url: 'load_fields.php',
			method: "POST",
			data: {
				id: '<?php echo escape($payment_id ?? ""); ?>',
				loan_id: lid,
				csrf_token: $('meta[name="csrf-token"]').attr('content')
			},
			success: function(resp){
				if(resp){
					$('#fields').html(resp);
					end_load();
				}
			},
			error: function(){
				end_load();
			}
		});
	}

	$('#manage-payment').submit(function(e){
	 	e.preventDefault();
	 	start_load();
	 	$.ajax({
	 		url: 'ajax.php?action=save_payment',
	 		method: 'POST',
	 		data: $(this).serialize(),
	 		success: function(resp){
	 			if(resp == 1){
	 				alert_toast("Payment data successfully saved.", "success");
	 				setTimeout(function(){
	 					location.reload();
	 				}, 1500);
	 			} else {
	 				alert_toast("An error occurred while saving payment.", "danger");
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
			load_fields();
		}
	});
</script>
