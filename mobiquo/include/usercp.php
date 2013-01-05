<?php
defined('IN_MOBIQUO') or exit;

require_once MYBB_ROOT."inc/functions_post.php";
require_once MYBB_ROOT."inc/functions_user.php";
require_once MYBB_ROOT."inc/class_parser.php";
$parser = new postParser;
$result = false;
$result_text = '';
// Load global language phrases
$lang->load("usercp");
if(isset($_POST['tt_token']) && isset($_POST['tt_code']))
{
	$result_email = tt_register_verify($_POST['tt_token'], $_POST['tt_code']); 
	$query = $db->simple_select("users", "*", "email='".$result_email."'");
	$user = $db->fetch_array($query);
	$mybb->user = $user;
}
if(!$mybb->user['pmfolders'])
{
	$mybb->user['pmfolders'] = "1**".$lang->folder_inbox."$%%$2**".$lang->folder_sent_items."$%%$3**".$lang->folder_drafts."$%%$4**".$lang->folder_trash;
	$db->update_query("users", array('pmfolders' => $mybb->user['pmfolders']), "uid='".$mybb->user['uid']."'");
}
if($mybb->user['uid'] == 0 || $mybb->usergroup['canusercp'] == 0)
{
	error("No permission to change password !");
}
$errors = '';

usercp_menu();

$plugins->run_hooks("usercp_start");

if($mybb->input['action'] == "do_email" && $mybb->request_method == "post")
{

	$errors = array();

	$plugins->run_hooks("usercp_do_email_start");
	if(validate_password_from_uid($mybb->user['uid'], $mybb->input['password']) == false)
	{
		$errors[] = $lang->error_invalidpassword;
	}
	else
	{
		// Set up user handler.
		require_once "inc/datahandlers/user.php";
		$userhandler = new UserDataHandler("update");

		$user = array(
			"uid" => $mybb->user['uid'],
			"email" => $mybb->input['email'],
			"email2" => $mybb->input['email2']
		);

		$userhandler->set_data($user);

		if(!$userhandler->validate_user())
		{
			$errors = $userhandler->get_friendly_errors();
		}
		else
		{
			if($mybb->user['usergroup'] != "5" && $mybb->usergroup['cancp'] != 1)
			{
				$activationcode = random_str();
				$now = TIME_NOW;
				$db->delete_query("awaitingactivation", "uid='".$mybb->user['uid']."'");
				$newactivation = array(
					"uid" => $mybb->user['uid'],
					"dateline" => TIME_NOW,
					"code" => $activationcode,
					"type" => "e",
					"oldgroup" => $mybb->user['usergroup'],
					"misc" => $db->escape_string($mybb->input['email'])
				);
				$db->insert_query("awaitingactivation", $newactivation);

				$username = $mybb->user['username'];
				$uid = $mybb->user['uid'];
				$lang->emailsubject_changeemail = $lang->sprintf($lang->emailsubject_changeemail, $mybb->settings['bbname']);
				$lang->email_changeemail = $lang->sprintf($lang->email_changeemail, $mybb->user['username'], $mybb->settings['bbname'], $mybb->user['email'], $mybb->input['email'], $mybb->settings['bburl'], $activationcode, $mybb->user['username'], $mybb->user['uid']);
				my_mail($mybb->input['email'], $lang->emailsubject_changeemail, $lang->email_changeemail);

				$plugins->run_hooks("usercp_do_email_verify");
				$result_text = $lang->redirect_changeemail_activation;
				$result = true;
			}
			else
			{
				$userhandler->update_user();
				$plugins->run_hooks("usercp_do_email_changed");
				$result_text = $lang->redirect_emailupdated;
				$result = true;
			}
		}
	}
	if(count($errors) > 0)
	{
		error($errors[0]);
	}
}


if($mybb->input['action'] == "do_password" && $mybb->request_method == "post")
{

	$errors = array();

	$plugins->run_hooks("usercp_do_password_start");
	if(validate_password_from_uid($mybb->user['uid'], $mybb->input['oldpassword']) == false)
	{
		$errors[] = $lang->error_invalidpassword;
	}
	else
	{
		// Set up user handler.
		require_once "inc/datahandlers/user.php";
		$userhandler = new UserDataHandler("update");

		$user = array(
			"uid" => $mybb->user['uid'],
			"password" => $mybb->input['password'],
			"password2" => $mybb->input['password2']
		);

		$userhandler->set_data($user);

		if(!$userhandler->validate_user())
		{
			$errors = $userhandler->get_friendly_errors();
		}
		else
		{
			$userhandler->update_user();
			my_setcookie("mybbuser", $mybb->user['uid']."_".$userhandler->data['loginkey']);
			$plugins->run_hooks("usercp_do_password_end");
			$result = true;
		}
	}
	if(count($errors) > 0)
	{
		error($errors[0]);
	}
}

