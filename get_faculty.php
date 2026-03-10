<?php
include 'db_connect.php';

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
	
	$qry = $conn->query("SELECT f.*,u.name,u.id as uid,u.username,u.password from faculty f left join users u  on f.user_id = u.id where f.id='".$_GET['id']."' ");
	if($qry){
		$data = $qry->fetch_assoc();
		$level_ids = array();
		$lqry = $conn->query("SELECT level_id FROM faculty_levels WHERE faculty_id='".$_GET['id']."'");
		if($lqry && $lqry->num_rows > 0){
			while($lrow = $lqry->fetch_assoc()){
				$level_ids[] = intval($lrow['level_id']);
			}
		}
		$data['level_ids'] = $level_ids;
		echo json_encode($data);
	}
?>