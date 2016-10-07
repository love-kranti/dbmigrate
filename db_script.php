<?php
include("config.php");
        
$config_query  = "select * from config;";
$result_config = mysql_query_with_throw($config_query);
$data_set = mysql_fetch_assoc($result_config);

$user_fail_id = $data_set['other_fail_id'];
$con_id= $data_set['id'];
$start_user_id = $data_set['start_user_id'];
$end_user_id = $data_set['end_user_id'];

$select_user = "SELECT id,firstname,middlename,lastname,email,mobile FROM 
                tbl_userdetails where id between $start_user_id and $end_user_id 
                and id in (select user_id as id from tbl_getaccounts group by user_id);";
$result_user = mysql_query_with_throw($select_user);

// create user post create array
while($user_row = mysql_fetch_assoc($result_user)){
    $user_id = $user_row['id'];
    $user_name = $user_row['firstname'];
    $mobile = $user_row['mobile'];
    $user_create_array  = array('firstName'=>$user_row['firstname'],
                                'middleName'=>$user_row['middlename'],
                                'lastName'=>$user_row['lastname'],
                                'email'=>$user_row['email'],
                                'phoneNumber'=>$user_row['mobile'],
                                 );
    global $java_base_url;
    $url = $java_base_url."/user";
    $data = json_encode($user_create_array);
    $headers = array('Content-Type: application/json');
    $create_migrate_entry = "insert into log_request (`user_id`,`name`,`status`,`mobile`) 
                                values ('$user_id','$user_name','Started','$mobile');";
    $create_request = mysql_query_with_throw($create_migrate_entry);
    $log_id = mysql_insert_id();
    $user_create_response = httpUtilityPost($url,$data,$headers);
    
    $api_status_code = $user_create_response['status'];
    $response = $user_create_response['api_response'];
    
    $update_status = check_error_code_log_response($api_status_code,$response,$log_id,
                                    $user_name,"Create User",$con_id,$user_fail_id,$user_id);
    if(!$update_status){
        continue;
    }
    $java_user = json_decode($response);
    $java_user_id = $java_user->id;
    
    $user_login_array  = array('userId'=>$java_user_id,
                                'password'=>"12345"
                                 );
    $headers = array('Content-Type: application/json');
    $data = json_encode($user_login_array);
    
    $url = $java_base_url."/authenticate";
    $user_login_response = httpUtilityPost($url,$data,$headers);
    $api_status_code = $user_login_response['status'];
    $response = $user_login_response['api_response'];
    
    $update_status = check_error_code_log_response($api_status_code,$response,$log_id,
                                    $user_name,"User Login",$con_id,$user_fail_id,$user_id);
    if(!$update_status){
        continue;
    }
    $java_user_login_response = json_decode($response);
    $java_user_session_id = $java_user_login_response->sessionId;
    // experian start entry
    $exp_start_query = "SELECT email_id,dob,mobile,first_name,middle_name,last_name,city,flatno,building,stateid,
                         pincode,pan,telephone,telephone_type,passport,voterid,aadhar,licence,gender from 
                         tbl_getscore where user_id=$user_id order by id desc limit 1;";
    $result_exp_data  = mysql_query_with_throw($exp_start_query);
    $exp_data = mysql_fetch_assoc($result_exp_data);
    
    if($exp_data['gender']==1){
        $gender = "Male";
    }
    else{
        $gender = "Female";
    }  
    
    $exp_start_array = array('city'=>$exp_data['city'],
                                'email'=>$exp_data['email_id'],
                                'firstName'=>$exp_data['first_name'],
                                'dateOfBirth'=>$exp_data['dob'],
                                'mobileNumber'=>$exp_data['mobile'],
                                'addressLine1'=>$exp_data['flatno'],
                                'addressLine2'=>$exp_data['building'],
                                'stateId'=>$exp_data['stateid'],
                                'pinCode'=>$exp_data['pincode'],
                                'panNumber'=>$exp_data['pan'],
                                'middleName'=>$exp_data['middle_name'],
                                'surname'=>$exp_data['last_name'],
                                'telephoneNumber'=>$exp_data['telephone'],
                                'telephoneTypeId'=>$exp_data['telephone_type'],
                                'passportNumber'=>$exp_data['passport'],
                                'voterIdNumber'=>$exp_data['voterid'],
                                'universalIdNumber'=>$exp_data['aadhar'],
                                'driverLicenseNumber'=>$exp_data['licence'],
                                'gender'=>$gender
                                 );
                                 
    $url = $java_base_url."/experian/start?authtoken=".$java_user_session_id;
    $data = json_encode($exp_start_array);
    $headers = array('Content-Type: application/json');
                    
                    
    $user_exp_response = httpUtilityPost($url,$data,$headers);
    
    $api_status_code = $user_exp_response['status'];
    $response = $user_exp_response['api_response'];
    
    $update_status = check_error_code_log_response($api_status_code,$response,$log_id,
                                    $user_name,"Experian Start Attempt",$con_id,$user_fail_id,$user_id);
    
    // upload multipart html file
    $url = $java_base_url."/file/ExperianReport?authtoken=".$java_user_session_id;
    
                                 
    $headers = array("Content-Type:multipart/form-data"); // cURL headers for file uploading
    
    $html_file_query = "SELECT `file_name` FROM `tbl_experian_data` WHERE user_id =$user_id order by id desc limit 1;";
    $result_file = mysql_query_with_throw($html_file_query);
    $file_data = mysql_fetch_assoc($result_file);
    $user_file_path = $file_data['file_name'];
    if(!file_exists($user_file_path)){
     $update_status = check_error_code_log_response(404,$user_file_path,$log_id,
                                    $user_name,"Html File not found",$con_id,$user_fail_id,$user_id);
    continue;
    }
    $postfields = array("The file being uploaded" => '@'.$user_file_path,);
                     
    $user_exp_response = httpUtilityPost($url,$postfields,$headers);
    $api_status_code = $user_exp_response['status'];
    $response = $user_exp_response['api_response'];
    $update_status = check_error_code_log_response($api_status_code,$response,$log_id,
                                    $user_name,"Html File Upload",$con_id,$user_fail_id,$user_id);
    if(!$update_status){
        continue;
    }
    $java_user_file_response = json_decode($response);
    $java_user_report_file_id = $java_user_file_response->id;
    
    global $java_admin,$java_pswd;
    $admin_login_array  = array('userId'=>$java_admin,
                                'password'=>$java_pswd
                                 );
    $headers = array('Content-Type: application/json');
    $data = json_encode($admin_login_array);
    
    $url = $java_base_url."/authenticate";
    $admin_login_response = httpUtilityPost($url,$data,$headers);
    $api_status_code = $admin_login_response['status'];
    $response = $admin_login_response['api_response'];
    
    
    $update_status = check_error_code_log_response($api_status_code,$response,$log_id,
                                    $user_name,"Admin Login Process Report",$con_id,$user_fail_id,$user_id);
    if(!$update_status){
        continue;
    }
    
    
    
    // process report through admin access
    
    $java_admin_login_response = json_decode($response);
    $java_admin_session_id = $java_admin_login_response->sessionId;
    $url = $java_base_url."/experian/processreport/".$java_user_report_file_id."?authtoken=".$java_admin_session_id;
    $process_array = array();
    $data = json_encode($process_array);
    $headers = array('Content-Type: application/json');
                    
    $user_exp_response = httpUtilityPost($url,$data,$headers);
    $update_status = check_error_code_log_response($api_status_code,$response,$log_id,
                                    $user_name,"Process Report",$con_id,$user_fail_id,$user_id);
    
    
    mysql_query_with_throw("update log_request set status='Success' where user_id = $user_id;");
    
    // check offer and post offer 
        
}
                                   
?>