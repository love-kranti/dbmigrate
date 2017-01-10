<?php
include("config.php");
$file_name = $_GET['file_name'];
global $file_path;
 $csvFile = fopen($file_path.$file_name, 'r');
                
//skip first line
fgetcsv($csvFile);
while (($data = fgetcsv($csvFile, 1000, ",")) !== FALSE) {
    $num = count($data);
    for ($c=0; $c < $num; $c++) {
      $col[$c] = $data[$c];
    }
     $account_no = $col[0];
     $balance = $col[1];
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
    
     $java_user_login_response = json_decode($response);
    $java_user_session_id = $java_user_login_response->sessionId;
    
    
     $exp_start_array = array('tradelineId'=>$account_no,
                                'amount'=>$balance
                                 );
                                 
    $url = $java_base_url."/offer?authtoken=".$java_user_session_id;
    $data = json_encode($exp_start_array);
    $headers = array('Content-Type: application/json');
                    
                    
    $user_exp_response = httpUtilityPost($url,$data,$headers);
    
    $api_status_code = $user_exp_response['status'];
    $response = $user_exp_response['api_response'];
   
}

    
    
    ?>