<?php
defined('IN_MOBIQUO') or exit;
require_once TT_ROOT . "lib/classTTJson.php";
require_once TT_ROOT . "lib/classConnection.php";

function set_api_key()
{
	global $db;
	$code = trim($_REQUEST['code']);
	$key = trim($_REQUEST['key']);
	$connection = new classFileManagement();
	$response = $connection->actionVerification($code,'set_api_key');
	if($response === true)
	{
		$updated_value = array('value' => $db->escape_string($key));
		$db->update_query("settings", $updated_value, "name='tapatalk_push_key'");
		rebuild_settings();
		echo 1;
	}
	else if(!empty($response))
	{
		echo $response;
	}
	else 
	{
		echo 0;
	}
}

function sync_user_func()
{
	global $db,$mybb;
	$code = trim($_POST['code']);
	$start = intval(isset($_POST['start']) ? $_POST['start'] : 0);
    $limit = intval(isset($_POST['limit']) ? $_POST['limit'] : 1000);
    $format = trim($_POST['format']);
    
    $connection = new classFileManagement();
	$response = $connection->actionVerification($code,'sync_user');
	
    if($response === true)
    {
    	$api_key = $mybb->settings['tapatalk_push_key'];	    		
	    // Get users...
	    $users = array();
	    $query = $db->simple_select("users", "uid,email,username,allownotices as allow_email,language", " email != '' and uid > $start ",array("order_by" => "uid", "order_dir" => "asc", "limit" => $limit));
	    
	    while ($member = $db->fetch_array($query))
	    {
	    	$member['encrypt_email'] = base64_encode(encrypt($member['email'],$api_key));
	    	if(empty($member['language']))
	    	{
	    		$member['language'] = $mybb->settings['bblanguage'];
	    	}
	        unset($member['email']);
	        $users[] = $member;
	    }
	    $data = array(
	        'result' => true,
	        'users' => $users,
	    );
    }
    else 
    {
    	$data = array(
            'result' => false,
            'result_text' => $response,
        );
    }
    
    $response = ($format == 'json') ? json_encode($data) : serialize($data);
    echo $response;
    exit;
}

function keyED($txt,$encrypt_key)
{
    $encrypt_key = md5($encrypt_key);
    $ctr=0;
    $tmp = "";
    for ($i=0;$i<strlen($txt);$i++)
    {
        if ($ctr==strlen($encrypt_key)) $ctr=0;
        $tmp.= substr($txt,$i,1) ^ substr($encrypt_key,$ctr,1);
        $ctr++;
    }
    return $tmp;
}
 
function encrypt($txt,$key)
{
    srand((double)microtime()*1000000);
    $encrypt_key = md5(rand(0,32000));
    $ctr=0;
    $tmp = "";
    for ($i=0;$i<strlen($txt);$i++)
    {
        if ($ctr==strlen($encrypt_key)) $ctr=0;
        $tmp.= substr($encrypt_key,$ctr,1) .
        (substr($txt,$i,1) ^ substr($encrypt_key,$ctr,1));
        $ctr++;
    }
    return keyED($tmp,$key);
}

function loadAPIKey()
{
    global $mybb;
    $mobi_api_key = $mybb->settings['tapatalk_push_key'];
    if(empty($mobi_api_key))
    {   
        $boardurl = $mybb->settings['bburl'];
        $boardurl = urlencode($boardurl);
        $response = getContentFromRemoteServer("http://directory.tapatalk.com/au_reg_verify.php?url=$boardurl", 10, $error);
        if($response)
        {
            $result = TTJson::decode($response, true);
            if(isset($result) && isset($result['result']))
            {
                $mobi_api_key = $result['api_key'];
                return $mobi_api_key;
            }
        } 
        return false;    
    }
    return $mobi_api_key;
}