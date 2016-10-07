<?php

set_exception_handler('createExceptionLog');
function createExceptionLog($exception){
    global $file_name;
    global $user_id;
    global $score_id;    
    $message = date('Y-m-d h:i:s a').'~'.$exception->getFile().'~'.$exception->getLine().'~'.$exception->getMessage().'~'.$exception->getTraceAsString();
    $write_log = error_log($message, 3, $file_name);
}

function createLogs($filename, $linenumber, $msg){
    
    $message = date('Y-m-d h:i:s a')."~".$filename."~".$linenumber."~".$msg."\r\n";
    global $log_file_name;
   
    $write_log = error_log($message, 3, $log_file_name);
}
function mysql_query_with_throw($query){
   $result =  mysql_query($query);
   if($result ==  FALSE){
      throw new Exception( mysql_error());
      //creteLogs(mysql_err
   }
   return $result;
}

function httpUtilityPost($url,$data,$headers)
{
    $ch = curl_init();
    createLogs(__FILE__,__LINE__,$url.$data."http post api request");
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    // we want headers
    curl_setopt($ch, CURLOPT_NOBODY, false);
    // we don't need body
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    $response = curl_exec($ch);
    $http_status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    createLogs(__FILE__,__LINE__,$response."http post api response status code");
    $return_array = array("status" => $http_status_code,"api_response" => $response);
    curl_close($ch);
    return $return_array;
}
function log_response($log_id,$name,$response,$action,$status_code){
    $insert_response = "insert into response_log (`log_id`,`user_name`,`response`,`action`,`status_code`) 
                                values('$log_id','$name','$response','$action','$status_code');";
                                
    $data  = mysql_query_with_throw($insert_response);        
}

function check_error_code_log_response($api_status_code,$response,$log_id,
                                    $name,$action,$con_id,$user_fail_id,$user_id,$isOffer){
    
    $log_response = log_response($log_id, $name, $response, $action, $api_status_code);
    if($api_status_code==200){
        return TRUE;
    }
    else{
        if($user_fail_id == ""){
            $fail_id = $user_id.",";
        }
        else{
            $fail_id = $user_fail_id.$user_id.",";
        }
        if($isOffer){
            mysql_query_with_throw("update log_request set status='Error in Offer' where user_id = $user_id;");
        }
        else{
            update_user_error_entry($fail_id,$con_id,$user_id);
        }
        return FALSE;
    }
    
}
function update_user_error_entry($fail_id,$con_id,$user_id)
{
    mysql_query_with_throw("update config set other_fail_id='$fail_id' where id =$con_id;");
    mysql_query_with_throw("update log_request set status='Error' where user_id = $user_id;");
}

function httpGetUtility($url){
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
    $response = curl_exec($ch);
    
    
    $http_status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    createLogs(__FILE__,__LINE__,$response."http post api response status code");
    $return_array = array("status" => $http_status_code,"api_response" => $response);
    curl_close($ch);
    return $return_array;
}       
?>