<?php
defined('IN_MOBIQUO') or exit;
require_once MYBB_ROOT."inc/functions_post.php";
require_once MYBB_ROOT."inc/functions_user.php";
require_once MYBB_ROOT."inc/class_parser.php";
function sign_in_func()
{
	global $db, $lang, $theme, $plugins, $mybb, $session, $settings, $cache, $time, $mybbgroups, $mobiquo_config,$user;
	// Load global language phrases
	$lang->load("member");
	$parser = new postParser;
	$token = trim($_POST['token']);
	$code = trim($_POST['code']);
	$username = $mybb->input['username'];
	$password = $mybb->input['password'];
	$post_email = $mybb->input['email'];
	$status = '';
	if(!empty($token) && !empty($code))
	{
		$result = tt_register_verify($token, $code);
		
		if($result->result && !empty($result->email))
		{
			$email = $result->email;
		    if(!empty($post_email) && $post_email != $email)
			{
				$status = 3;
			}
			else if($user = tt_get_user_by_email($email))
			{
				if(!empty($username) && $username != $user['username'])
				{
					$status = 3;
				}
				else 
				{
					return tt_login_success();
				}		
			}
			else if(!empty($username) && !empty($email))
			{
				$profile = $result->profile;
				if($mybb->settings['disableregs'] == 1)
				{
					error($lang->registrations_disabled);
				}
				
				// Set up user handler.
				require_once MYBB_ROOT."inc/datahandlers/user.php";
				$userhandler = new UserDataHandler("insert");
				
				$birthday_arr = explode('-', $profile->birthday);
				$bday = array(
					"day" => $birthday_arr[2],
					"month" => $birthday_arr[1],
					"year" => $birthday_arr[0],
				);
				$user_field = array(
					'fid3' => ucfirst($profile->gender),
					'fid1' => $profile->location,
					'fid2' => $profile->description,
				);
				// Set the data for the new user.
				$user = array(
					"username" => $mybb->input['username'],
					"password" => $mybb->input['password'],
					"password2" => $mybb->input['password'],
					"email" => $email,
					"email2" => $email,
					"usergroup" => 2,
					"referrer" => '',
					"timezone" => $mybb->settings['timezoneoffset'],
					"language" => '',
					"profile_fields" => '',
					"regip" => $session->ipaddress,
					"longregip" => my_ip2long($session->ipaddress),
					"coppa_user" => 0,
					"avatar" => $profile->avatar_url,
					"birthday" => $bday,
					"website" => $profile->link,
					"user_fields" => $user_field,
					"signature" => $profile->signature,
					"option" => array(),
				);						
				
				$userhandler->set_data($user);
				$userhandler->verify_birthday();
				$userhandler->verify_options();
				if($userhandler->verify_username_exists())
				{
					$status = 1;
				}
				else if(!$userhandler->verify_password() || !$userhandler->verify_username())
				{
					$errors = $userhandler->get_friendly_errors();
					error($errors[0]);
				}
				else
				{
					$userhandler->set_validated(true);
					$user = $userhandler->insert_user();
					return tt_login_success();
				}
			}
			else 
			{
				$status = 2;
			}
		}
		else if(!$result->result)
		{
			error("Tapatalk ID verify faile!");
		}
		
		if(!empty($status))
		{
			$response = new xmlrpcval(array(
		        'result'            => new xmlrpcval(0, 'boolean'),
		        'result_text'       => new xmlrpcval('', 'base64'),
			 	'status'          => new xmlrpcval($status, 'string'),
			 ), 'struct');
			return new xmlrpcresp($response);
		}
	}
	else
	{
		error("Invlaid params!");
	}
}