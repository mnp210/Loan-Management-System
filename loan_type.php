<?php include('db_connect.php'); ?>

<div class="container-fluid">
	<div class="col-lg-12">
		<div class="row">
			<!-- FORM Panel -->
			<div class="col-md-4">
			<form action="" id="manage-loan-type">
				<input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
				<div class="card">
					<div class="card-header">
						   Loan Type Form
				  	</div>
					<div class="card-body">
							<input type="hidden" name="id">
							<div class="form-group">
								<label class="control-label">Type Name</label>
								<input type="text" name="type_name" id="" class="form-control" required>
							</div>
							<div class="form-group">
								<label class="control-label">Description</label>
								<textarea name="description" id="" cols="30" rows="3" class="form-control" required></textarea>
							</div>
					</div>
							
					<div class="card-footer">
						<div class="row">
							<div class="col-md-12">
								<button class="btn btn-sm btn-primary col-sm-4 offset-md-2"> Save</button>
								<button class="btn btn-sm btn-secondary col-sm-4" type="button" onclick="_reset()"> Cancel</button>
							</div>
						</div>
					</div>
				</div>
			</form>
			</div>
			<!-- FORM Panel -->

			<!-- Table Panel -->
			<div class="col-md-8">
				<div class="card">
					<div class="card-body">
						<table class="table table-bordered table-hover">
							<thead>
								<tr>
									<th class="text-center">#</th>
									<th class="text-center">Loan Type</th>
									<th class="text-center">Action</th>
								</tr>
							</thead>
							<tbody>
								<?php 
								$i = 1;
								$types = $conn->query("SELECT * FROM loan_types order by type_name asc");
								if ($types) {
									while($row = $types->fetch_assoc()):
								?>
								<tr>
									<td class="text-center"><?php echo $i++; ?></td>
									<td>
										 <p>Type Name: <b><?php echo escape($row['type_name']); ?></b></p>
										 <p><small>Description: <b><?php echo escape($row['description']); ?></b></small></p>
									</td>
									<td class="text-center">
										<button class="btn btn-sm btn-primary edit_ltype" type="button" 
											data-id="<?php echo $row['id']; ?>" 
											data-type_name="<?php echo escape($row['type_name']); ?>" 
											data-description="<?php echo escape($row['description']); ?>">Edit</button>
										<button class="btn btn-sm btn-danger delete_ltype" type="button" data-id="<?php echo $row['id']; ?>">Delete</button>
									</td>
								</tr>
								<?php endwhile; } ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
			<!-- Table Panel -->
		</div>
	</div>	
</div>
<style>
	td {
		vertical-align: middle !important;
	}
	td p {
		margin: unset;
	}
</style>
<script>
	function _reset(){
		$('[name="id"]').val('');
		$('#manage-loan-type').get(0).reset();
	}
	
	$('#manage-loan-type').submit(function(e){
		e.preventDefault();
		start_load();
		$.ajax({
			url: 'ajax.php?action=save_loan_type',
			data: new FormData($(this)[0]),
		    cache: false,
		    contentType: false,
		    processData: false,
		    method: 'POST',
		    type: 'POST',
			success: function(resp){
				if(resp == 1){
					alert_toast("Loan type successfully saved", 'success');
					setTimeout(function(){
						location.reload();
					}, 1500);
				} else {
					alert_toast("An error occurred while saving loan type", 'danger');
					end_load();
				}
			},
			error: function(){
				alert_toast("Request failed", 'danger');
				end_load();
			}
		});
	});

	$('.edit_ltype').click(function(){
		start_load();
		var cat = $('#manage-loan-type');
		cat.get(0).reset();
		cat.find("[name='id']").val($(this).attr('data-id'));
		cat.find("[name='type_name']").val($(this).attr('data-type_name'));
		cat.find("[name='description']").val($(this).attr('data-description'));
		end_load();
	});

	$('.delete_ltype').click(function(){
		_conf("Are you sure you want to delete this Loan Type?", "delete_ltype", [$(this).attr('data-id')]);
	});

	function delete_ltype($id){
		start_load();
		$.ajax({
			url: 'ajax.php?action=delete_loan_type',
			method: 'POST',
			data: { id: $id, csrf_token: $('meta[name="csrf-token"]').attr('content') },
			success: function(resp){
				if(resp == 1){
					alert_toast("Loan type successfully deleted", 'success');
					setTimeout(function(){
						location.reload();
					}, 1500);
				} else {
					alert_toast("An error occurred while deleting loan type", 'danger');
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
