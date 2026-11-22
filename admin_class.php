<?php
require_once __DIR__ . '/security.php';

Class Action {
	private $db;

	public function __construct() {
		ob_start();
		include 'db_connect.php';
		$this->db = $conn;
	}

	function __destruct() {
		if ($this->db && $this->db instanceof mysqli) {
			$this->db->close();
		}
		if (ob_get_level() > 0) {
			ob_end_flush();
		}
	}

	function login(){
		$username = trim($_POST['username'] ?? '');
		$password = (string)($_POST['password'] ?? '');

		if (empty($username) || empty($password)) {
			return 3;
		}

		$stmt = $this->db->prepare("SELECT * FROM users WHERE username = ?");
		$stmt->bind_param("s", $username);
		$stmt->execute();
		$result = $stmt->get_result();

		if ($result && $result->num_rows > 0) {
			$user = $result->fetch_assoc();
			$db_pass = $user['password'];

			$password_matches = false;
			$needs_rehash = false;

			// Check password: 1. standard hash, 2. legacy plaintext fallback, 3. legacy MD5 fallback
			if (password_verify($password, $db_pass)) {
				$password_matches = true;
				if (password_needs_rehash($db_pass, PASSWORD_DEFAULT)) {
					$needs_rehash = true;
				}
			} elseif ($password === $db_pass) {
				$password_matches = true;
				$needs_rehash = true;
			} elseif (md5($password) === $db_pass) {
				$password_matches = true;
				$needs_rehash = true;
			}

			if ($password_matches) {
				// Transparently upgrade legacy passwords to modern bcrypt
				if ($needs_rehash) {
					$new_hash = password_hash($password, PASSWORD_DEFAULT);
					$update_stmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
					$update_stmt->bind_param("si", $new_hash, $user['id']);
					$update_stmt->execute();
					$update_stmt->close();
				}

				foreach ($user as $key => $value) {
					if ($key != 'password' && !is_numeric($key)) {
						$_SESSION['login_' . $key] = $value;
					}
				}
				$stmt->close();
				return 1;
			}
		}
		if ($stmt) {
			$stmt->close();
		}
		return 3;
	}

	function login2(){
		$email = trim($_POST['email'] ?? '');
		$password = (string)($_POST['password'] ?? '');

		if (empty($email) || empty($password)) {
			return 3;
		}

		$stmt = $this->db->prepare("SELECT * FROM users WHERE username = ?");
		$stmt->bind_param("s", $email);
		$stmt->execute();
		$result = $stmt->get_result();

		if ($result && $result->num_rows > 0) {
			$user = $result->fetch_assoc();
			$db_pass = $user['password'];

			$password_matches = false;
			$needs_rehash = false;

			if (password_verify($password, $db_pass)) {
				$password_matches = true;
				if (password_needs_rehash($db_pass, PASSWORD_DEFAULT)) {
					$needs_rehash = true;
				}
			} elseif ($password === $db_pass) {
				$password_matches = true;
				$needs_rehash = true;
			} elseif (md5($password) === $db_pass) {
				$password_matches = true;
				$needs_rehash = true;
			}

			if ($password_matches) {
				if ($needs_rehash) {
					$new_hash = password_hash($password, PASSWORD_DEFAULT);
					$update_stmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
					$update_stmt->bind_param("si", $new_hash, $user['id']);
					$update_stmt->execute();
					$update_stmt->close();
				}

				foreach ($user as $key => $value) {
					if ($key != 'password' && !is_numeric($key)) {
						$_SESSION['login_' . $key] = $value;
					}
				}
				$stmt->close();
				return 1;
			}
		}
		if ($stmt) {
			$stmt->close();
		}
		return 3;
	}

	function logout(){
		session_destroy();
		foreach ($_SESSION as $key => $value) {
			unset($_SESSION[$key]);
		}
		header("location:login.php");
		exit;
	}

	function logout2(){
		session_destroy();
		foreach ($_SESSION as $key => $value) {
			unset($_SESSION[$key]);
		}
		header("location:../index.php");
		exit;
	}

	function save_user(){
		$id = !empty($_POST['id']) ? intval($_POST['id']) : null;
		$name = trim($_POST['name'] ?? '');
		$username = trim($_POST['username'] ?? '');
		$password = (string)($_POST['password'] ?? '');
		$type = intval($_POST['type'] ?? 2);

		if(empty($id)){
			$hashed = password_hash($password, PASSWORD_DEFAULT);
			$stmt = $this->db->prepare("INSERT INTO users (name, username, password, type) VALUES (?, ?, ?, ?)");
			$stmt->bind_param("sssi", $name, $username, $hashed, $type);
			$save = $stmt->execute();
			$stmt->close();
		} else {
			if(!empty($password)){
				$hashed = password_hash($password, PASSWORD_DEFAULT);
				$stmt = $this->db->prepare("UPDATE users SET name = ?, username = ?, password = ?, type = ? WHERE id = ?");
				$stmt->bind_param("sssii", $name, $username, $hashed, $type, $id);
			} else {
				$stmt = $this->db->prepare("UPDATE users SET name = ?, username = ?, type = ? WHERE id = ?");
				$stmt->bind_param("ssii", $name, $username, $type, $id);
			}
			$save = $stmt->execute();
			$stmt->close();
		}
		if($save){
			return 1;
		}
		return 0;
	}

	function delete_user(){
		$id = intval($_POST['id'] ?? 0);
		if($id <= 0){
			return 0;
		}
		if(isset($_SESSION['login_id']) && $_SESSION['login_id'] == $id){
			return 2; // Prevent deleting active session user
		}
		$stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
		$stmt->bind_param("i", $id);
		$delete = $stmt->execute();
		$stmt->close();
		if($delete){
			return 1;
		}
		return 0;
	}

	function signup(){
		$name = trim($_POST['name'] ?? '');
		$contact = trim($_POST['contact'] ?? '');
		$address = trim($_POST['address'] ?? '');
		$email = trim($_POST['email'] ?? '');
		$password = (string)($_POST['password'] ?? '');

		$chk_stmt = $this->db->prepare("SELECT id FROM users WHERE username = ?");
		$chk_stmt->bind_param("s", $email);
		$chk_stmt->execute();
		$chk_res = $chk_stmt->get_result();
		if($chk_res && $chk_res->num_rows > 0){
			$chk_stmt->close();
			return 2;
		}
		$chk_stmt->close();

		$hashed = password_hash($password, PASSWORD_DEFAULT);
		$type = 3;
		$stmt = $this->db->prepare("INSERT INTO users (name, contact, address, username, password, type) VALUES (?, ?, ?, ?, ?, ?)");
		$stmt->bind_param("sssssi", $name, $contact, $address, $email, $hashed, $type);
		$save = $stmt->execute();
		$new_id = $stmt->insert_id;
		$stmt->close();

		if($save){
			$stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
			$stmt->bind_param("i", $new_id);
			$stmt->execute();
			$res = $stmt->get_result();
			if($res && $res->num_rows > 0){
				foreach ($res->fetch_assoc() as $key => $value) {
					if($key != 'password' && !is_numeric($key)){
						$_SESSION['login_'.$key] = $value;
					}
				}
			}
			$stmt->close();
			return 1;
		}
		return 0;
	}

	function save_settings(){
		$name = trim($_POST['name'] ?? '');
		$email = trim($_POST['email'] ?? '');
		$contact = trim($_POST['contact'] ?? '');
		$about = trim($_POST['about'] ?? '');
		$cover_img = '';

		if(isset($_FILES['img']['tmp_name']) && $_FILES['img']['tmp_name'] != ''){
			$fname = strtotime(date('y-m-d H:i')).'_'.basename($_FILES['img']['name']);
			if(move_uploaded_file($_FILES['img']['tmp_name'], __DIR__ . '/assets/img/' . $fname)){
				$cover_img = $fname;
			}
		}

		$chk = $this->db->query("SELECT * FROM system_settings");
		if($chk && $chk->num_rows > 0){
			if(!empty($cover_img)){
				$stmt = $this->db->prepare("UPDATE system_settings SET name = ?, email = ?, contact = ?, about_content = ?, cover_img = ?");
				$stmt->bind_param("sssss", $name, $email, $contact, $about, $cover_img);
			} else {
				$stmt = $this->db->prepare("UPDATE system_settings SET name = ?, email = ?, contact = ?, about_content = ?");
				$stmt->bind_param("ssss", $name, $email, $contact, $about);
			}
		} else {
			$stmt = $this->db->prepare("INSERT INTO system_settings (name, email, contact, about_content, cover_img) VALUES (?, ?, ?, ?, ?)");
			$stmt->bind_param("sssss", $name, $email, $contact, $about, $cover_img);
		}
		$save = $stmt->execute();
		$stmt->close();

		if($save){
			$query = $this->db->query("SELECT * FROM system_settings limit 1");
			if($query && $query->num_rows > 0){
				foreach ($query->fetch_assoc() as $key => $value) {
					if(!is_numeric($key))
						$_SESSION['setting_'.$key] = $value;
				}
			}
			return 1;
		}
		return 0;
	}

	function save_loan_type(){
		$id = !empty($_POST['id']) ? intval($_POST['id']) : null;
		$type_name = trim($_POST['type_name'] ?? '');
		$description = trim($_POST['description'] ?? '');

		if(empty($id)){
			$stmt = $this->db->prepare("INSERT INTO loan_types (type_name, description) VALUES (?, ?)");
			$stmt->bind_param("ss", $type_name, $description);
		} else {
			$stmt = $this->db->prepare("UPDATE loan_types SET type_name = ?, description = ? WHERE id = ?");
			$stmt->bind_param("ssi", $type_name, $description, $id);
		}
		$save = $stmt->execute();
		$stmt->close();
		if($save) return 1;
		return 0;
	}

	function delete_loan_type(){
		$id = intval($_POST['id'] ?? 0);
		$stmt = $this->db->prepare("DELETE FROM loan_types WHERE id = ?");
		$stmt->bind_param("i", $id);
		$delete = $stmt->execute();
		$stmt->close();
		if($delete) return 1;
		return 0;
	}

	function save_plan(){
		$id = !empty($_POST['id']) ? intval($_POST['id']) : null;
		$months = intval($_POST['months'] ?? 0);
		$interest_percentage = floatval($_POST['interest_percentage'] ?? 0);
		$penalty_rate = floatval($_POST['penalty_rate'] ?? 0);

		if(empty($id)){
			$stmt = $this->db->prepare("INSERT INTO loan_plan (months, interest_percentage, penalty_rate) VALUES (?, ?, ?)");
			$stmt->bind_param("idd", $months, $interest_percentage, $penalty_rate);
		} else {
			$stmt = $this->db->prepare("UPDATE loan_plan SET months = ?, interest_percentage = ?, penalty_rate = ? WHERE id = ?");
			$stmt->bind_param("iddi", $months, $interest_percentage, $penalty_rate, $id);
		}
		$save = $stmt->execute();
		$stmt->close();
		if($save) return 1;
		return 0;
	}

	function delete_plan(){
		$id = intval($_POST['id'] ?? 0);
		$stmt = $this->db->prepare("DELETE FROM loan_plan WHERE id = ?");
		$stmt->bind_param("i", $id);
		$delete = $stmt->execute();
		$stmt->close();
		if($delete) return 1;
		return 0;
	}

	function save_borrower(){
		$id = !empty($_POST['id']) ? intval($_POST['id']) : null;
		$lastname = trim($_POST['lastname'] ?? '');
		$firstname = trim($_POST['firstname'] ?? '');
		$middlename = trim($_POST['middlename'] ?? '');
		$address = trim($_POST['address'] ?? '');
		$contact_no = trim($_POST['contact_no'] ?? '');
		$email = trim($_POST['email'] ?? '');
		$tax_id = trim($_POST['tax_id'] ?? '');

		if(empty($id)){
			$stmt = $this->db->prepare("INSERT INTO borrowers (lastname, firstname, middlename, address, contact_no, email, tax_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
			$stmt->bind_param("sssssss", $lastname, $firstname, $middlename, $address, $contact_no, $email, $tax_id);
		} else {
			$stmt = $this->db->prepare("UPDATE borrowers SET lastname = ?, firstname = ?, middlename = ?, address = ?, contact_no = ?, email = ?, tax_id = ? WHERE id = ?");
			$stmt->bind_param("sssssssi", $lastname, $firstname, $middlename, $address, $contact_no, $email, $tax_id, $id);
		}
		$save = $stmt->execute();
		$stmt->close();
		if($save) return 1;
		return 0;
	}

	function delete_borrower(){
		$id = intval($_POST['id'] ?? 0);
		$stmt = $this->db->prepare("DELETE FROM borrowers WHERE id = ?");
		$stmt->bind_param("i", $id);
		$delete = $stmt->execute();
		$stmt->close();
		if($delete) return 1;
		return 0;
	}

	function save_loan(){
		$id = !empty($_POST['id']) ? intval($_POST['id']) : null;
		$borrower_id = intval($_POST['borrower_id'] ?? 0);
		$loan_type_id = intval($_POST['loan_type_id'] ?? 0);
		$plan_id = intval($_POST['plan_id'] ?? 0);
		$amount = floatval($_POST['amount'] ?? 0);
		$purpose = trim($_POST['purpose'] ?? '');
		$status = isset($_POST['status']) ? intval($_POST['status']) : 0;

		if(empty($id)){
			// Generate unique ref_no
			$ref_no = (string)mt_rand(1, 99999999);
			$chk_ref = $this->db->prepare("SELECT id FROM loan_list WHERE ref_no = ?");
			while(true){
				$chk_ref->bind_param("s", $ref_no);
				$chk_ref->execute();
				$res = $chk_ref->get_result();
				if($res && $res->num_rows > 0){
					$ref_no = (string)mt_rand(1, 99999999);
				} else {
					break;
				}
			}
			$chk_ref->close();

			$stmt = $this->db->prepare("INSERT INTO loan_list (ref_no, loan_type_id, borrower_id, purpose, amount, plan_id, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
			$stmt->bind_param("siisddi", $ref_no, $loan_type_id, $borrower_id, $purpose, $amount, $plan_id, $status);
			$save = $stmt->execute();
			$id = $stmt->insert_id;
			$stmt->close();
		} else {
			$stmt = $this->db->prepare("UPDATE loan_list SET loan_type_id = ?, borrower_id = ?, purpose = ?, amount = ?, plan_id = ?, status = ? WHERE id = ?");
			$stmt->bind_param("iisddii", $loan_type_id, $borrower_id, $purpose, $amount, $plan_id, $status, $id);
			$save = $stmt->execute();
			$stmt->close();
		}

		if($save && $id){
			if($status == 2){
				// Loan is Released: generate schedules
				$plan_stmt = $this->db->prepare("SELECT months FROM loan_plan WHERE id = ?");
				$plan_stmt->bind_param("i", $plan_id);
				$plan_stmt->execute();
				$plan_res = $plan_stmt->get_result();
				$months = 0;
				if($plan_res && $plan_res->num_rows > 0){
					$months = intval($plan_res->fetch_assoc()['months']);
				}
				$plan_stmt->close();

				$sid = [];
				for($i = 1; $i <= $months; $i++){
					$date = date("Y-m-d", strtotime(date("Y-m-d") . " +" . $i . " months"));
					$chk_sch = $this->db->prepare("SELECT id FROM loan_schedules WHERE loan_id = ? AND date(date_due) = ?");
					$chk_sch->bind_param("is", $id, $date);
					$chk_sch->execute();
					$sch_res = $chk_sch->get_result();

					if($sch_res && $sch_res->num_rows > 0){
						$ls_id = $sch_res->fetch_assoc()['id'];
					} else {
						$ins_sch = $this->db->prepare("INSERT INTO loan_schedules (loan_id, date_due) VALUES (?, ?)");
						$ins_sch->bind_param("is", $id, $date);
						$ins_sch->execute();
						$ls_id = $ins_sch->insert_id;
						$ins_sch->close();
					}
					$chk_sch->close();
					$sid[] = $ls_id;
				}

				if(!empty($sid)){
					$sid_list = implode(",", array_map('intval', $sid));
					$this->db->query("DELETE FROM loan_schedules WHERE loan_id = $id AND id NOT IN ($sid_list)");
				}

				$rel_stmt = $this->db->prepare("UPDATE loan_list SET date_released = NOW() WHERE id = ?");
				$rel_stmt->bind_param("i", $id);
				$rel_stmt->execute();
				$rel_stmt->close();
			} else {
				// Status changed away from released: remove existing schedules
				$del_sch = $this->db->prepare("DELETE FROM loan_schedules WHERE loan_id = ?");
				$del_sch->bind_param("i", $id);
				$del_sch->execute();
				$del_sch->close();
			}
			return 1;
		}
		return 0;
	}

	function delete_loan(){
		$id = intval($_POST['id'] ?? 0);
		$del_sch = $this->db->prepare("DELETE FROM loan_schedules WHERE loan_id = ?");
		$del_sch->bind_param("i", $id);
		$del_sch->execute();
		$del_sch->close();

		$stmt = $this->db->prepare("DELETE FROM loan_list WHERE id = ?");
		$stmt->bind_param("i", $id);
		$delete = $stmt->execute();
		$stmt->close();
		if($delete) return 1;
		return 0;
	}

	function save_payment(){
		$id = !empty($_POST['id']) ? intval($_POST['id']) : null;
		$loan_id = intval($_POST['loan_id'] ?? 0);
		$payee = trim($_POST['payee'] ?? '');
		$amount = floatval($_POST['amount'] ?? 0);
		$penalty_amount = floatval($_POST['penalty_amount'] ?? 0);
		$overdue = intval($_POST['overdue'] ?? 0);

		if(empty($id)){
			$stmt = $this->db->prepare("INSERT INTO payments (loan_id, payee, amount, penalty_amount, overdue) VALUES (?, ?, ?, ?, ?)");
			$stmt->bind_param("isddi", $loan_id, $payee, $amount, $penalty_amount, $overdue);
		} else {
			$stmt = $this->db->prepare("UPDATE payments SET loan_id = ?, payee = ?, amount = ?, penalty_amount = ?, overdue = ? WHERE id = ?");
			$stmt->bind_param("isddii", $loan_id, $payee, $amount, $penalty_amount, $overdue, $id);
		}
		$save = $stmt->execute();
		$stmt->close();
		if($save) return 1;
		return 0;
	}

	function delete_payment(){
		$id = intval($_POST['id'] ?? 0);
		$stmt = $this->db->prepare("DELETE FROM payments WHERE id = ?");
		$stmt->bind_param("i", $id);
		$delete = $stmt->execute();
		$stmt->close();
		if($delete) return 1;
		return 0;
	}
}
