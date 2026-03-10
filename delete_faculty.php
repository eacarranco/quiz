<?php 
include 'db_connect.php';
extract($_GET);
$conn->query("CREATE TABLE IF NOT EXISTS faculty_levels (
	id INT NOT NULL AUTO_INCREMENT,
	faculty_id INT NOT NULL,
	level_id INT NOT NULL,
	date_updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	UNIQUE KEY uq_faculty_level (faculty_id, level_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
$get = $conn->query("SELECT * FROM faculty where id=$id ")->fetch_array();
$conn->query("DELETE FROM faculty_levels WHERE faculty_id = $id ");
$qry = $conn->query("DELETE FROM faculty where id = $id ");
$qry2 = $conn->query("DELETE FROM users where id = '".$get['user_id']."' ");
if($qry && $qry2)
	echo true;
?>