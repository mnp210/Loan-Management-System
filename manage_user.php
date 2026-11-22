<?php 
require_once __DIR__ . '/db_connect.php';
require_admin();

$meta = [];
if(isset($_GET['id'])){
	$stmt = $conn->prepare("SELECT id, name, username, type FROM users WHERE id = ?");
	$u_id = intval($_GET['id']);
	$stmt->bind_param("i", $u_id);
	$stmt->execute();
	$res = $stmt->get_result();
	if($res && $res->num_rows > 0){
		$meta = $res->fetch_assoc();
	}
	$stmt->close();
}
?>
<div class="container-fluid">
	<form action="" id="manage-user">
		<input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
		<input type="hidden" name="id" value="<?php echo escape($meta['id'] ?? ''); ?>">
		<div class="form-group">
			<label for="name">Name</label>
			<input type="text" name="name" id="name" class="form-control" value="<?php echo escape($meta['name'] ?? ''); ?>" required>
		</div>
		<div class="form-group">
			<label for="username">Username</label>
			<input type="text" name="username" id="username" class="form-control" value="<?php echo escape($meta['username'] ?? ''); ?>" required>
		</div>
		<div class="form-group">
			<label for="password">Password</label>
			<input type="password" name="password" id="password" class="form-control" value="" <?php echo empty($meta['id']) ? 'required' : ''; ?>>
			<?php if(!empty($meta['id'])): ?>
				<small class="text-muted">Leave blank if you do not wish to change the password.</small>
			<?php endif; ?>
		</div>
		<div class="form-group">
			<label for="type">User Role</label>
			<select name="type" id="type" class="custom-select" required>
				<option value="1" <?php echo (isset($meta['type']) && $meta['type'] == 1) ? 'selected': ''; ?>>Admin</option>
				<option value="2" <?php echo (!isset($meta['type']) || $meta['type'] == 2) ? 'selected': ''; ?>>Staff</option>
			</select>
		</div>
	</form>
</div>
<script>
	$('#manage-user').submit(function(e){
		e.preventDefault();
		start_load();
		$.ajax({
			url: 'ajax.php?action=save_user',
			method: 'POST',
			data: $(this).serialize(),
			success: function(resp){
				if(resp == 1){
					alert_toast("User successfully saved", 'success');
					setTimeout(function(){
						location.reload();
					}, 1500);
				} else {
					alert_toast("An error occurred while saving user.", 'danger');
					end_load();
				}
			},
			error: function(){
				alert_toast("Request failed.", 'danger');
				end_load();
			}
		});
	});
</script>
