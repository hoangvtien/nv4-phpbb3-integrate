<?php

/**
 * @Project NUKEVIET 3.1
 * @Author VINADES.,JSC (contact@vinades.vn)
 * @Copyright (C) 2011 VINADES.,JSC. All rights reserved
 * @Createdate 26/01/2011, 14:40
 */
if (! defined ( 'NV_IS_MOD_USER' ))
	die ( 'Stop!!!' );

define ( 'IN_PHPBB', true );

if (file_exists ( NV_ROOTDIR . '/' . DIR_FORUM . '/common.php' )) {
	$db_nkv = $db;
	$op_nkv = $op;
	
	global $user, $auth, $template, $cache, $db, $config, $phpEx, $phpbb_root_path;
	
	$phpEx = 'php';
	$phpbb_root_path = NV_ROOTDIR . '/' . DIR_FORUM . '/';
	
	include ($phpbb_root_path . 'common.' . $phpEx);
	
	$user->session_begin ();
	$auth->acl ( $user->data );
	$user->setup ();
	
	if (empty ( $nv_username )) {
		$nv_username = $nv_Request->get_title ( 'nv_login', 'post', '' );
	}
	if (empty ( $nv_password )) {
		$nv_password = $nv_Request->get_title ( 'nv_password', 'post', '' );
	}
	if (empty ( $nv_redirect )) {
		$nv_redirect = $nv_Request->get_title ( 'nv_redirect', 'post,get', '' );
	}
	
	$result = $auth->login ( $nv_username, $nv_password, $remember );
	
	$status = $result ['status'];
	
	$error = "";
	
	/*
	 * Login error codes
	 * define( 'LOGIN_CONTINUE', 1 );
	 * define( 'LOGIN_BREAK', 2 );
	 * define( 'LOGIN_SUCCESS', 3 );
	 * define( 'LOGIN_SUCCESS_CREATE_PROFILE', 20 );
	 * define( 'LOGIN_ERROR_USERNAME', 10 );
	 * define( 'LOGIN_ERROR_PASSWORD', 11 );
	 * define( 'LOGIN_ERROR_ACTIVE', 12 );
	 * define( 'LOGIN_ERROR_ATTEMPTS', 13 );
	 * define( 'LOGIN_ERROR_EXTERNAL_AUTH', 14 );
	 * define( 'LOGIN_ERROR_PASSWORD_CONVERT', 15 );
	 */
	
	define ( 'USER_NORMAL', 0 );
	define ( 'USER_FOUNDER', 3 );
	
	$db = $db_nkv;
	$op = $op_nkv;
	if ($status == 3) {
		$user_info = $result ['user_row'];
		$password_crypt = $crypt->hash ( $nv_password );
		$result = $db->query ( "SELECT * FROM " . $table_prefix . "users WHERE user_id='" . intval ( $user_info ['user_id'] ) . "'" );
		$row = $result->fetch ();
		$user_info ['active'] = 0;
		if ($row ['user_type'] == USER_NORMAL || $row ['user_type'] == USER_FOUNDER) {
			$user_info ['active'] = 1;
		}
		
		$user_info ['userid'] = intval ( $row ['user_id'] );
		$user_info ['username'] = $row ['username_clean'];
		$user_info ['email'] = $row ['user_email'];
		$user_info ['full_name'] = $row ['username'];
		$user_info ['birthday'] = intval ( strtotime ( $row ['user_birthday'] ) );
		$user_info ['regdate'] = intval ( $row ['user_regdate'] );
		$user_info ['sig'] = $row ['user_sig'];
		$user_info ['view_mail'] = intval ( $row ['user_allow_viewemail'] );
		
		$sql = "SELECT * FROM " . NV_USERS_GLOBALTABLE . " WHERE userid=" . intval ( $user_info ['userid'] );
		$result = $db->query ( $sql );
		$numrows = $result->fetch ();
		
		if ($numrows > 0) {
			$sql = "UPDATE " . NV_USERS_GLOBALTABLE . " SET 
                username = " . $db->quote ( $user_info ['username'] ) . ", 
                md5username = " . $db->quote ( md5 ( $user_info ['username'] ) ) . ", 
                password = " . $db->quote ( $password_crypt ) . ", 
                email = " . $db->quote ( $user_info ['email'] ) . ", 
                first_name = " . $db->quote ( $user_info ['full_name'] ) . ", 
                birthday=" . $user_info ['birthday'] . ", 
				sig=" . $db->quote ( $user_info ['sig'] ) . ", 
                regdate=" . $user_info ['regdate'] . ", 
                view_mail=" . $user_info ['view_mail'] . ",
                active=" . $user_info ['active'] . ",
                last_login=" . NV_CURRENTTIME . ", 
                last_ip=" . $db->quote ( $client_info ['ip'] ) . ", 
                last_agent=" . $db->quote ( $client_info ['agent'] ) . "
                WHERE userid=" . $user_info ['userid'];
		} else {
			$sql = "INSERT INTO " . NV_USERS_GLOBALTABLE . " 
                (userid, username, md5username, password, email, first_name, gender, photo, birthday, sig, 
                regdate, question, answer, passlostkey, 
                view_mail, remember, in_groups, active, checknum, last_login, last_ip, last_agent, last_openid) VALUES 
                (
                " . intval ( $user_info ['userid'] ) . ", 
                " . $db->quote ( $user_info ['username'] ) . ", 
                " . $db->quote ( md5 ( $user_info ['username'] ) ) . ", 
                " . $db->quote ( $password_crypt ) . ", 
                " . $db->quote ( $user_info ['email'] ) . ", 
                " . $db->quote ( $user_info ['full_name'] ) . ", 
                '', 
                '', 
                " . $user_info ['birthday'] . ", 
				" . $db->quote ( $user_info ['sig'] ) . ", 
                " . $user_info ['regdate'] . ", 
                '', '', '', 
                " . $user_info ['view_mail'] . ", 0, '', 
                " . $user_info ['active'] . ", '', 
                " . NV_CURRENTTIME . ", 
                " . $db->quote ( $client_info ['ip'] ) . ", 
                " . $db->quote ( $client_info ['agent'] ) . ", 
                '' 
                )";
			if ($db->query ( $sql )) {
				$error = "";
			} else {
				$error = $lang_module ['error_update_users_info'];
			}
		}
	} elseif ($status == 12) {
		$error = $lang_module ['login_no_active'];
	} else {
		$error = $lang_global ['loginincorrect'];
	}
} else {
	trigger_error ( "Error no forum phpbb", 256 );
}