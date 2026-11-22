<?php 
require_once __DIR__ . '/db_connect.php'; 

$meta = [];
if(isset($_GET['id'])){
	$stmt = $conn->prepare("SELECT * FROM borrowers WHERE id = ?");
	$b_id = intval($_GET['id']);
	$stmt->bind_param("i", $b_id);
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
		<form id="manage-borrower">
			<input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
			<input type="hidden" name="id" value="<?php echo escape($meta['id'] ?? ''); ?>">
			<div class="row">
				<div class="col-md-4">
					<div class="form-group">
						<label for="" class="control-label">Last Name</label>
						<input name="lastname" class="form-control" required="" value="<?php echo escape($meta['lastname'] ?? ''); ?>">
					</div>
				</div>
				<div class="col-md-4">
					<div class="form-group">
						<label for="">First Name</label>
						<input name="firstname" class="form-control" required="" value="<?php echo escape($meta['firstname'] ?? ''); ?>">
					</div>
				</div>
				<div class="col-md-4">
					<div class="form-group">
						<label for="">Middle Name</label>
						<input name="middlename" class="form-control" value="<?php echo escape($meta['middlename'] ?? ''); ?>">
					</div>
				</div>
			</div>
			<div class="row form-group">
				<div class="col-md-6">
					<label for="">Address</label>
					<textarea name="address" id="" cols="30" rows="2" class="form-control" required=""><?php echo escape($meta['address'] ?? ''); ?></textarea>
				</div>
				<div class="col-md-5">
					<div class="">
						<label for="">Contact #</label>
						<input type="text" class="form-control" name="contact_no" value="<?php echo escape($meta['contact_no'] ?? ''); ?>">
					</div>
				</div>
			</div>
			<div class="row form-group">
				<div class="col-md-6">
					<label for="">Email</label>
					<input type="email" class="form-control" name="email" value="<?php echo escape($meta['email'] ?? ''); ?>">
				</div>
				<div class="col-md-5">
					<div class="">
						<label for="">Tax ID</label>
						<input type="text" class="form-control" name="tax_id" value="<?php echo escape($meta['tax_id'] ?? ''); ?>">
					</div>
				</div>
			</div>
		</form>
	</div>
</div>

<script>
	 $('#manage-borrower').submit(function(e){
	 	e.preventDefault();
	 	start_load();
	 	$.ajax({
	 		url: 'ajax.php?action=save_borrower',
	 		method: 'POST',
	 		data: $(this).serialize(),
	 		success: function(resp){
	 			if(resp == 1){
	 				alert_toast("Borrower data successfully saved.", "success");
	 				setTimeout(function(){
	 					location.reload();
	 				}, 1500);
	 			} else {
	 				alert_toast("An error occurred while saving borrower data.", "danger");
	 				end_load();
	 			}
	 		},
	 		error: function(){
	 			alert_toast("Request failed.", "danger");
	 			end_load();
	 		}
	 	});
	 });
</script>
