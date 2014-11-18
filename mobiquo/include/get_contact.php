<?php

defined('IN_MOBIQUO') or exit;

require_once MYBB_ROOT."inc/functions_modcp.php";
require_once MYBB_ROOT."inc/class_parser.php";
include_once TT_ROOT."include/function.php";
$parser = new postParser;


function get_contact_func($xmlrpc_params)
{
    global $db, $lang, $theme, $plugins, $mybb, $session, $settings, $cache, $time, $mybbgroups, $parser, $displaygroupfields;

    $lang->load("member");

    $input = Tapatalk_Input::filterXmlInput(array(
        'user_id' => Tapatalk_Input::STRING,
    ), $xmlrpc_params);



    if (isset($input['user_id']) && !empty($input['user_id'])) {
        $uid = $input['user_id'];
    }  else {
        $uid = $mybb->user['uid'];
    }

    if($mybb->user['uid'] != $uid)
    {
        $member = get_user($uid);
    }
    else
    {
        $member = $mybb->user;
    }
	
    if(!$member['uid'])
    {
        error($lang->error_nomember);
    }
    
	// Guests or those without permission can't email other users
	if($mybb->usergroup['cansendemail'] == 0 || !$mybb->user['uid'])
	{
		error_no_permission();
	}
	
	
	if($member['hideemail'] != 0)
	{
		error($lang->error_hideemail);
	}
	
	
	$user_info = array(
    	'result'             => new xmlrpcval(true, 'boolean'),
        'user_id'            => new xmlrpcval($member['uid']),
        'display_name'       => new xmlrpcval(basic_clean($member['username']), 'base64'),
		'enc_email'          => new xmlrpcval(base64_encode(encrypt($member['email'], loadAPIKey()))),
    );
    
    $xmlrpc_user_info = new xmlrpcval($user_info, 'struct');
    return new xmlrpcresp($xmlrpc_user_info);
}
