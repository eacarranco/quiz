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

$conn->query("CREATE TABLE IF NOT EXISTS faculty_levels (
	id INT NOT NULL AUTO_INCREMENT,
	faculty_id INT NOT NULL,
	level_id INT NOT NULL,
	date_updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	UNIQUE KEY uq_faculty_level (faculty_id, level_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$level_ids = isset($_POST['level_ids']) && is_array($_POST['level_ids']) ? $_POST['level_ids'] : array();
$valid_level_ids = array();
foreach ($level_ids as $lid) {
	$lid = intval($lid);
	if ($lid > 0) {
		$valid_level_ids[$lid] = $lid;
	}
}

$subject_sql = $conn->real_escape_string($subject);

function saveFacultyLevels($conn, $faculty_id, $valid_level_ids) {
	if (!$conn->query("DELETE FROM faculty_levels WHERE faculty_id = " . intval($faculty_id))) {
		return false;
	}

	if (count($valid_level_ids) === 0) {
		return true;
	}

	foreach ($valid_level_ids as $lid) {
		$chk = $conn->query("SELECT id FROM levels WHERE id = " . intval($lid) . " AND state = 1 LIMIT 1");
		if (!$chk || $chk->num_rows === 0) {
			continue;
		}
		if (!$conn->query("INSERT IGNORE INTO faculty_levels (faculty_id, level_id) VALUES (" . intval($faculty_id) . ", " . intval($lid) . ")")) {
			return false;
		}
	}

	return true;
}

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
		$insert_faculty =$conn->query("INSERT INTO faculty set user_id = '".$id."', subject='".$subject_sql."' ");
		if($insert_faculty){
			$faculty_id = $conn->insert_id;
			if(saveFacultyLevels($conn, $faculty_id, $valid_level_ids)){
				echo json_encode(array('status'=>1));
			}else{
				echo json_encode(array('status'=>2,'msg'=>'No se pudieron guardar los niveles del profesor'));
			}
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
		$update_faculty =$conn->query("UPDATE faculty set subject='".$subject_sql."' where id = '".$id."' ");
		if($update_faculty){
			if(saveFacultyLevels($conn, intval($id), $valid_level_ids)){
				echo json_encode(array('status'=>1));
			}else{
				echo json_encode(array('status'=>2,'msg'=>'No se pudieron actualizar los niveles del profesor'));
			}
		}
	}
}