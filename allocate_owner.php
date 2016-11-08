<?php
include("config.php");
  $exp_start_array = array('9'=>'00528000004NrfzAAC',
                            '12'=>'00528000004Nrg4AAC',
                            '10'=>'00528000004Nrg9AAC',
                            '7'=>'00528000004NrgEAAS',
                            '6'=>'00528000004NrgOAAS',
                            '8'=>'00528000004NrgTAAS',
                            '4'=>'00528000004NrgYAAS',
                            '1'=>'00528000004NrgdAAC'
                             );      
$config_query  = "select * from config;";
$result_config = mysql_query_with_throw($config_query);
$data_set = mysql_fetch_assoc($result_config);

$user_fail_id = $data_set['other_fail_id'];
$con_id= $data_set['id'];
$start_user_id = $data_set['start_user_id'];
$end_user_id = $data_set['end_user_id'];

$select_user = "SELECT distinct id,mobile,owner FROM tbl_userdetails where owner !='' and 
                id between ".$start_user_id." and ".$end_user_id.";";
               
$result_user = mysql_query_with_throw($select_user);

// create user post create array
while($user_row = mysql_fetch_assoc($result_user)){
    $user_id = $user_row['id'];
    $mobile = $user_row['mobile'];
    $owner_id = $user_row['owner'];
    
    $user_login_array  = array('userId'=>$mobile,
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
   
    
    $exp_start_array = array('ownerId'=>$exp_start_array[$owner_id]);
                                 
    $url = $java_base_url."/user/self?authtoken=".$java_user_session_id;
    $data = json_encode($exp_start_array);
    $headers = array('Content-Type: application/json');
                    
                    
    $user_exp_response = httpUtilityPut($url,$data,$headers);
    
    $api_status_code = $user_exp_response['status'];
    $response = $user_exp_response['api_response'];
    
    $update_status = check_error_code_log_response($api_status_code,$response,$log_id,
                                    $user_name,"Update owner",$con_id,$user_fail_id,$user_id);
    
   
    mysql_query_with_throw("update log_request set status='Success' where user_id =".$user_id." ;");
    
    // check offer and post offer 
//    sleep(10);    
}
                                   
?>
