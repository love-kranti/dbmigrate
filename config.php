
<?php
$java_admin = "admin";
$java_pswd = "password";

// $link=mysql_connect("localhost", "root", "root"); 
    // if($link == FALSE) throw new Exception('Error Connecting');
// 
// $db=mysql_select_db("clmdbl_new_live");
// if($db == FALSE) throw new Exception('Error selecting database.');
$log_file_name = "/home/love/db_migrate.log";
if(!file_exists($log_file_name)){
    $f = fopen($log_file_name, "a+");
    fclose($f);
}
$java_base_url = "http://javadev-370467541.ap-southeast-1.elb.amazonaws.com/java";
$java_admin_user_name = "admin";
$java_admin_password = "password";
$file_path = "/home/love/";
include("functions.php");

?>
