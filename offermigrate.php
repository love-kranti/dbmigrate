
<?php
include("config.php");
        
$success_offer_query  = "select * from log_request where status='Success';";
$result_offer = mysql_query_with_throw($success_offer_query);


while($data_set = mysql_fetch_assoc($result_offer)){
    $user_id  = $data_set['user_id'];
    $user_mobile = $data_set['mobile'];
    $user_name = $data_set['name'];
    $log_id  = $data_set['id'];
    $user_login_array  = array('userId'=>"DEFAULT:".$user_mobile."_",
                                'password'=>"12345"
                                 );
    $headers = array('Content-Type: application/json');
    $data = json_encode($user_login_array);
    
    $url = $java_base_url."/authenticate";
    $user_login_response = httpUtilityPost($url,$data,$headers);
    
    $api_status_code = $user_login_response['status'];
    $response = $user_login_response['api_response'];
    $update_status = check_error_code_log_response($api_status_code,$response,$log_id,
                                    $user_name,"User Login for report get",'','',$user_id);
    if(!$update_status){
        continue;
    }
    $java_user_login_response = json_decode($response);
    $java_user_session_id = $java_user_login_response->sessionId;
    
    
    $url = $java_base_url."reports?authtoken=".$java_user_session_id;
    $admin_report_response = httpGetUtility($url);
    $api_status_code = $admin_report_response['status'];
    $response = $admin_report_response['api_response'];
    $update_status = check_error_code_log_response($api_status_code,$response,$log_id,
                                    $user_name,"User get report",'','',$user_id);
    if(!$update_status){
        continue;
    }
    $get_report_response = json_decode($response);
    $array = json_decode(json_encode($get_report_response), True);
    $report_key = '';
    foreach($array as $key => $value)
    {
      $report_key = $key;
    }    
    $report_tradelines = $array[$report_key]['reportTradelines'];
    
    //
   for($x=0; $x<sizeof($report_tradelines); $x++){
        $java_account_number = $report_tradelines[$x]['accountNumber'];
        $javatradeLine_id = $report_tradelines[$x]['id'];
        $select_account_query = "SELECT principal,interest,other_charges,super_amount 
                                    FROM tbl_accounts where userid='$user_id' 
                                    and account_no='$java_account_number';";
        $run_query = mysql_query_with_throw($select_account_query);
        if(mysql_num_rows($run_query)>0){
            // continue loop upto principal 0 or null and super_amount 0 or null
            while ($row = mysql_fetch_assoc($run_query)) {
                if($row['principal']!=0 || $row['super_amount']!=0.00){
                    $post_amount;
                    // post offer
                    if($row['super_amount']!=0.00){
                        // post super amount as first offer
                        $post_amount = $row['super_amount'];
                    }
                    else{
                        // add pic and send 
                        $post_amount = $row['principal']+$row['interest']+$row['other_charges'];
                        
                    }
                        // login as admin
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
                                    $user_name,"Admin login for placing post offer",'','',$user_id);
                        if(!$update_status){
                            continue;
                        }
                        $java_admin_login_response = json_decode($response);
                        $java_admin_session_id = $java_admin_login_response->sessionId;
                        // post offer as admin
                        $post_offer_array  = array('amount'=>$post_amount,
                                                    'tradelinId'=>$javatradeLine_id
                                                     );
                        $headers = array('Content-Type: application/json');
                        $data = json_encode($post_offer_array);
                        
                        $url = $java_base_url."/offer?authtoken=".$java_admin_session_id;
                        $admin_offer_post_response = httpUtilityPost($url,$data,$headers);
                        $api_status_code = $admin_offer_post_response['status'];
                        $response = $admin_offer_post_response['api_response'];
                        $update_status = check_error_code_log_response($api_status_code,$response,$log_id,
                                    $user_name,"Admin placing post offer",'','',$user_id);
                        if(!$update_status){
                            continue;
                        }
                        break;
                        
                }
            }
            
        }
        
    }
}
?>