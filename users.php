<?php 
require_once __DIR__ . '/db_connect.php';
require_admin(); // Security guard: only Admin can access users
?>

<div class="container-fluid">
	<div class="row">
		<div class="col-lg-12">
			<button class="btn btn-primary float-right btn-sm" id="new_user"><i class="fa fa-plus"></i> New User</button>
		</div>
	</div>
	<br>
	<div class="row">
		<div class="card col-lg-12">
			<div class="card-body">
				<table class="table table-striped table-bordered col-md-12" id="user-list">
					<thead>
						<tr>
							<th class="text-center">#</th>
							<th class="text-center">Name</th>
							<th class="text-center">Username</th>
							<th class="text-center">Role</th>
							<th class="text-center">Action</th>
						</tr>
					</thead>
					<tbody>
						<?php
							$users = $conn->query("SELECT * FROM users order by name asc");
							$i = 1;
							if ($users) {
								while($row = $users->fetch_assoc()):
									$role_name = ($row['type'] == 1) ? 'Admin' : (($row['type'] == 2) ? 'Staff' : 'Customer');
						?>
						 <tr>
						 	<td class="text-center"><?php echo $i++; ?></td>
						 	<td><?php echo escape($row['name']); ?></td>
						 	<td><?php echo escape($row['username']); ?></td>
						 	<td class="text-center">
						 		<?php if($row['type'] == 1): ?>
						 			<span class="badge badge-primary">Admin</span>
						 		<?php elseif($row['type'] == 2): ?>
						 			<span class="badge badge-secondary">Staff</span>
						 		<?php else: ?>
						 			<span class="badge badge-info">Customer</span>
						 		<?php endif; ?>
						 	</td>
						 	<td class="text-center">
								<div class="btn-group">
								  <button type="button" class="btn btn-primary btn-sm">Action</button>
								  <button type="button" class="btn btn-primary btn-sm dropdown-toggle dropdown-toggle-split" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
								    <span class="sr-only">Toggle Dropdown</span>
								  </button>
								  <div class="dropdown-menu">
								    <a class="dropdown-item edit_user" href="javascript:void(0)" data-id="<?php echo $row['id']; ?>">Edit</a>
								    <?php if($_SESSION['login_id'] != $row['id']): ?>
								    <div class="dropdown-divider"></div>
								    <a class="dropdown-item delete_user" href="javascript:void(0)" data-id="<?php echo $row['id']; ?>">Delete</a>
								    <?php endif; ?>
								  </div>
								</div>
						 	</td>
						 </tr>
						<?php endwhile; } ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>
<script>
$('#user-list').dataTable();
$('#new_user').click(function(){
	uni_modal('New User', 'manage_user.php');
});
$('.edit_user').click(function(){
	uni_modal('Edit User', 'manage_user.php?id=' + $(this).attr('data-id'));
});
$('.delete_user').click(function(){
	_conf("Are you sure you want to delete this user?", "delete_user", [$(this).attr('data-id')]);
});
function delete_user($id){
	start_load();
	$.ajax({
		url: 'ajax.php?action=delete_user',
		method: 'POST',
		data: { id: $id, csrf_token: $('meta[name="csrf-token"]').attr('content') },
		success: function(resp){
			if(resp == 1){
				alert_toast("User successfully deleted", 'success');
				setTimeout(function(){
					location.reload();
				}, 1500);
			} else if(resp == 2){
				alert_toast("You cannot delete your own active account.", 'warning');
				end_load();
			} else {
				alert_toast("An error occurred while deleting user.", 'danger');
				end_load();
			}
		},
		error: function(){
			alert_toast("Request failed.", 'danger');
			end_load();
		}
	});
}
</script>
