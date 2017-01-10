<?php
$java_admin = "admin";
$java_pswd = "password";
// $link=mysql_connect("bluepiclear.ceofrws5syj6.ap-southeast-1.rds.amazonaws.com:3306", "bluepiroot", "Plat!num9enL");
    // if($link == FALSE) throw new Exception('Error Connecting');
// 
// $db=mysql_select_db("clearmyd_gtndbptl_new"); 
// if($db == FALSE) throw new Exception('Error selecting database.');

$log_file_name = "/home/ubuntu/db_migrate.log";
if(!file_exists($log_file_name)){
    $f = fopen($log_file_name, "a+");
    fclose($f);
}
$java_base_url = "https://clearmydues.com/java";
$java_admin_user_name = "admin";
$java_admin_password = "password";
$file_path = "/home/ubuntu/";
include("functions.php");

?>
