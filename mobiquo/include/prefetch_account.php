<?php
defined('IN_MOBIQUO') or exit;

function prefetch_account_func()
{
	global $mybb,$db,$lang;
	$lang->load("member");
	$user = tt_get_user_by_email($mybb->input['email']);
	if(empty($user['uid']))
	{
		$result = array (
			'result'            => new xmlrpcval(false, 'boolean'),
		    'result_text'       => new xmlrpcval($lang->error_nomember, 'base64'),
		);
	}
	else 
	{
		$result = array(
			'result'            => new xmlrpcval(true, 'boolean'),
			'result_text'       => new xmlrpcval('', 'base64'),
			'user_id'           => new xmlrpcval($user['uid'], 'string'),
			'login_name'        => new xmlrpcval(basic_clean($user['username']), 'base64'),
			'display_name'      => new xmlrpcval(basic_clean($user['username']), 'base64'),
			'avatar'            => new xmlrpcval(absolute_url($user['avatar']), 'string'),
		);
	}
	return new xmlrpcresp(new xmlrpcval($result, 'struct'));
}