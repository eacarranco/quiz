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

$has_level_id = $conn->query("SHOW COLUMNS FROM students LIKE 'level_id'");
if ($has_level_id && $has_level_id->num_rows === 0) {
	$conn->query("ALTER TABLE students ADD COLUMN level_id INT NULL AFTER user_id");
}
	
	$qry = $conn->query("SELECT s.*,u.name,u.id as uid,u.username,u.password from students s left join users u  on s.user_id = u.id where s.id='".$_GET['id']."' ");
	if($qry){
		echo json_encode($qry->fetch_array());
	}
?>