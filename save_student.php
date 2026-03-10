<?php 

include 'db_connect.php';

extract($_POST);

$conn->query("CREATE TABLE IF NOT EXISTS levels (
	id INT NOT NULL AUTO_INCREMENT,
	level_name VARCHAR(100) NOT NULL,
	state TINYINT(1) NOT NULL DEFAULT 1,
	date_updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	UNIQUE KEY uq_level_name (level_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$has_level_id = $conn->query("SHOW COLUMNS FROM students LIKE 'level_id'");
if ($has_level_id && $has_level_id->num_rows === 0) {
	$conn->query("ALTER TABLE students ADD COLUMN level_id INT NULL AFTER user_id");
}

$default_chk = $conn->query("SELECT id FROM levels WHERE level_name = 'Default' LIMIT 1");
if (!$default_chk || $default_chk->num_rows === 0) {
	$conn->query("UPDATE levels SET level_name = 'Default' WHERE level_name = '1A' LIMIT 1");
}

$conn->query("INSERT IGNORE INTO levels (level_name, state) VALUES ('Default', 1)");

$default_row = $conn->query("SELECT id FROM levels WHERE level_name = 'Default' LIMIT 1");
$default_id = ($default_row && $default_row->num_rows > 0) ? intval($default_row->fetch_assoc()['id']) : 0;
if ($default_id > 0) {
	$conn->query("UPDATE students SET level_id = {$default_id}, level_section = 'Default' WHERE TRIM(level_section) = '1A'");
	$conn->query("UPDATE students SET level_id = {$default_id}, level_section = 'Default' WHERE (level_id IS NULL OR level_id = 0)");
}

$level_id = isset($_POST['level_id']) ? intval($_POST['level_id']) : 0;
if ($level_id < 1) {
	echo json_encode(array('status'=>2,'msg'=>'Seleccione un nivel para el estudiante'));
	exit;
}

$level_chk = $conn->query("SELECT level_name FROM levels WHERE id = {$level_id} AND state = 1 LIMIT 1");
if (!$level_chk || $level_chk->num_rows === 0) {
	echo json_encode(array('status'=>2,'msg'=>'Nivel inválido o inactivo'));
	exit;
}

$level_name = $level_chk->fetch_assoc()['level_name'];
$level_name_sql = $conn->real_escape_string($level_name);

if(empty($id)){
	$data=  " name='".$name."'";
	$data .=  ", username='".$username."'";
	$data .=  ", user_type='".$user_type."'";
	$data .=  ", password='".$password."'";
	$chk = $conn->query("SELECT * FROM users where username = '".$username."' ")->num_rows;
	if($chk > 0){
			echo json_encode(array('status'=>2,'msg'=>'Username already exist'));
			exit;
	}
	$insert_user = $conn->query('INSERT INTO users set  '.$data);

	if($insert_user){
		$id = $conn->insert_id;
		$insert_students =$conn->query("INSERT INTO students set user_id = '".$id."', level_id='".$level_id."', level_section='".$level_name_sql."' ");
		if($insert_students){
			echo json_encode(array('status'=>1));
		}
	}
}else{
	$data=  " name='".$name."'";
	$data .=  ", username='".$username."'";
	$data .=  ", user_type='".$user_type."'";
	$data .=  ", password='".$password."'";
	$chk = $conn->query("SELECT * FROM users where username = '".$username."' and id !='".$uid."' ")->num_rows;
	if($chk > 0){
			echo json_encode(array('status'=>2,'msg'=>'Username already exist'));
			exit;
	}
	$update_user = $conn->query('UPDATE users set  '.$data.' where id ='.$uid);

	if($update_user){
		$update_students =$conn->query("UPDATE students set level_id='".$level_id."', level_section='".$level_name_sql."' where id = '".$id."' ");
		if($update_students){
			echo json_encode(array('status'=>1));
		}
	}
}