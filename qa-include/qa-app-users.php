<?php

/*
	Question2Answer 1.4.1 (c) 2011, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-app-users.php
	Version: 1.4.1
	Date: 2011-07-10 06:58:57 GMT
	Description: User management (application level) for basic user operations


	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	More about this license: http://www.question2answer.org/license.php
*/

	if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
		header('Location: ../');
		exit;
	}


	define('QA_USER_LEVEL_BASIC', 0);
	define('QA_USER_LEVEL_EXPERT', 20);
	define('QA_USER_LEVEL_EDITOR', 50);
	define('QA_USER_LEVEL_MODERATOR', 80);
	define('QA_USER_LEVEL_ADMIN', 100);
	define('QA_USER_LEVEL_SUPER', 120);
	
	define('QA_USER_FLAGS_EMAIL_CONFIRMED', 1);
	define('QA_USER_FLAGS_USER_BLOCKED', 2);
	define('QA_USER_FLAGS_SHOW_AVATAR', 4);
	define('QA_USER_FLAGS_SHOW_GRAVATAR', 8);
	define('QA_USER_FLAGS_NO_MESSAGES', 16);
	
	define('QA_FIELD_FLAGS_MULTI_LINE', 1);
	define('QA_FIELD_FLAGS_LINK_URL', 2);

	
	if (QA_FINAL_EXTERNAL_USERS) {

	//	If we're using single sign-on integration (WordPress or otherwise), load PHP file for that

		if (defined('QA_FINAL_WORDPRESS_INTEGRATE_PATH'))
			require_once QA_INCLUDE_DIR.'qa-external-users-wp.php';
		else
			require_once QA_EXTERNAL_DIR.'qa-external-users.php';
		

	//	Access functions for user information
	
		function qa_get_logged_in_user_cache()
	/*
		Return array of information about the currently logged in user, cache to ensure only one call to external code
	*/
		{
			global $qa_cached_logged_in_user;
			
			if (!isset($qa_cached_logged_in_user)) {
				$user=qa_get_logged_in_user();
				$qa_cached_logged_in_user=isset($user) ? $user : false; // to save trying again
			}
			
			return @$qa_cached_logged_in_user;
		}
		
		
		function qa_get_logged_in_user_field($field)
	/*
		Return $field of the currently logged in user, or null if not available
	*/
		{
			$user=qa_get_logged_in_user_cache();
			
			return @$user[$field];
		}


		function qa_get_logged_in_userid()
	/*
		Return the userid of the currently logged in user, or null if none
	*/
		{
			return qa_get_logged_in_user_field('userid');
		}
		
		
		function qa_get_logged_in_points()
	/*
		Return the number of points of the currently logged in user, or null if none is logged in
	*/
		{
			global $qa_cached_logged_in_points;
			
			if (!isset($qa_cached_logged_in_points))
				$qa_cached_logged_in_points=qa_db_select_with_pending(qa_db_user_points_selectspec(qa_get_logged_in_userid(), true));
			
			return $qa_cached_logged_in_points['points'];
		}
		
		
	} else {
		
		function qa_start_session()
	/*
		Open a PHP session if one isn't opened already
	*/
		{
			@ini_set('session.gc_maxlifetime', 86400); // worth a try, but won't help in shared hosting environment
			@ini_set('session.use_trans_sid', false); // sessions need cookies to work, since we redirect after login
			@ini_set('session.cookie_domain', QA_COOKIE_DOMAIN);

			if (!isset($_SESSION))
				session_start();
		}
		
		
		function qa_session_var_suffix()
	/*
		Returns a suffix to be used for names of session variables to prevent them being shared between multiple QA sites on the same server
	*/
		{
			return md5(QA_FINAL_MYSQL_HOSTNAME.'/'.QA_FINAL_MYSQL_USERNAME.'/'.QA_FINAL_MYSQL_PASSWORD.'/'.QA_FINAL_MYSQL_DATABASE.'/'.QA_MYSQL_TABLE_PREFIX);
		}
		
		
		function qa_session_verify_code($userid)
	/*
		Returns a verification code used to ensure that a user session can't be generated by another PHP script running on the same server
	*/
		{
			return sha1($userid.'/'.QA_MYSQL_TABLE_PREFIX.'/'.QA_FINAL_MYSQL_DATABASE.'/'.QA_FINAL_MYSQL_PASSWORD.'/'.QA_FINAL_MYSQL_USERNAME.'/'.QA_FINAL_MYSQL_HOSTNAME);
		}

		
		function qa_set_session_cookie($handle, $sessioncode, $remember)
	/*
		Set cookie in browser for username $handle with $sessioncode (in database).
		Pass true if user checked 'Remember me' (either now or previously, as learned from cookie).
	*/
		{
			// if $remember is true, store in browser for a month, otherwise store only until browser is closed
			setcookie('qa_session', $handle.'/'.$sessioncode.'/'.($remember ? 1 : 0), $remember ? (time()+2592000) : 0, '/', QA_COOKIE_DOMAIN);
		}

		
		function qa_clear_session_cookie()
	/*
		Remove session cookie from browser
	*/
		{
			setcookie('qa_session', false, 0, '/', QA_COOKIE_DOMAIN);
		}

		
		function qa_set_logged_in_user($userid, $handle='', $remember=false, $source=null)
	/*
		Call for successful log in by $userid and $handle or successful log out with $userid=null.
		$remember states if 'Remember me' was checked in the login form.
	*/
		{
			require_once QA_INCLUDE_DIR.'qa-app-cookies.php';
			
			qa_start_session();

			$suffix=qa_session_var_suffix();
			
			if (isset($userid)) {
				$_SESSION['qa_session_userid_'.$suffix]=$userid;
				$_SESSION['qa_session_source_'.$suffix]=$source;
				$_SESSION['qa_session_verify_'.$suffix]=qa_session_verify_code($userid);
					// prevents one account on a shared server being able to create a log in a user to Q2A on another account on same server
				
				// PHP sessions time out too quickly on the server side, so we also set a cookie as backup.
				// Logging in from a second browser will make the previous browser's 'Remember me' no longer
				// work - I'm not sure if this is the right behavior - could see it either way.

				require_once QA_INCLUDE_DIR.'qa-db-selects.php';
				
				$userinfo=qa_db_single_select(qa_db_user_account_selectspec($userid, true));
				
				// if we have logged in before, and are logging in the same way as before, we don't need to change the sessioncode/source
				// this means it will be possible to automatically log in (via cookies) to the same account from more than one browser
				
				if (empty($userinfo['sessioncode']) || ($source!==$userinfo['sessionsource'])) {
					$sessioncode=qa_db_user_rand_sessioncode();
					qa_db_user_set($userid, 'sessioncode', $sessioncode);
					qa_db_user_set($userid, 'sessionsource', $source);
				} else
					$sessioncode=$userinfo['sessioncode'];
				
				qa_db_user_logged_in($userid, qa_remote_ip_address());
				qa_set_session_cookie($handle, $sessioncode, $remember);
				
				qa_report_event('u_login', $userid, $userinfo['handle'], qa_cookie_get());

			} else {
				$olduserid=qa_get_logged_in_userid();
				$oldhandle=qa_get_logged_in_handle();

				qa_clear_session_cookie();

				unset($_SESSION['qa_session_userid_'.$suffix]);
				unset($_SESSION['qa_session_source_'.$suffix]);
				unset($_SESSION['qa_session_verify_'.$suffix]);

				qa_report_event('u_logout', $olduserid, $oldhandle, qa_cookie_get());
			}
		}
		
		
		function qa_log_in_external_user($source, $identifier, $fields)
	/*
		Call to log in a user based on an external identity provider $source with external $identifier
		A new user is created if it's a new combination of $source and $identifier, based on $fields
	*/
		{
			require_once QA_INCLUDE_DIR.'qa-db-users.php';
			
			$users=qa_db_user_login_find($source, $identifier);
			$countusers=count($users);
			
			if ($countusers>1)
				qa_fatal_error('External login mapped to more than one user'); // should never happen
			
			if ($countusers) // user exists so log them in
				qa_set_logged_in_user($users[0]['userid'], $users[0]['handle'], false, $source);
			
			else { // create and log in user
				require_once QA_INCLUDE_DIR.'qa-app-users-edit.php';
				
				$handle=qa_handle_make_valid(@$fields['handle']);
				
				$userid=qa_create_new_user((string)@$fields['email'], null /* no password */, $handle,
					isset($fields['level']) ? $fields['level'] : QA_USER_LEVEL_BASIC, @$fields['confirmed']);
				
				qa_db_user_login_add($userid, $source, $identifier);
				
				$profilefields=array('name', 'location', 'website', 'about');
				
				foreach ($profilefields as $fieldname)
					if (strlen(@$fields[$fieldname]))
						qa_db_user_profile_set($userid, $fieldname, $fields[$fieldname]);
						
				if (strlen(@$fields['avatar']))
					qa_set_user_avatar($userid, $fields['avatar']);
						
				qa_set_logged_in_user($userid, $handle, false, $source);
			}
		}

		
		function qa_get_logged_in_userid()
	/*
		Return the userid of the currently logged in user, or null if none logged in
	*/
		{
			global $qa_logged_in_userid_checked;
			
			$suffix=qa_session_var_suffix();
			
			if (!$qa_logged_in_userid_checked) { // only check once
				qa_start_session(); // this will load logged in userid from the native PHP session, but that's not enough
				
				$sessionuserid=@$_SESSION['qa_session_userid_'.$suffix];
				
				if (isset($sessionuserid)) // check verify code matches
					if (@$_SESSION['qa_session_verify_'.$suffix] != qa_session_verify_code($sessionuserid)) {
						unset($_SESSION['qa_session_userid_'.$suffix]);
						unset($_SESSION['qa_session_source_'.$suffix]);
						unset($_SESSION['qa_session_verify_'.$suffix]);
					}
				
				if (!empty($_COOKIE['qa_session'])) {
					@list($handle, $sessioncode, $remember)=explode('/', $_COOKIE['qa_session']);
					
					if ($remember)
						qa_set_session_cookie($handle, $sessioncode, $remember); // extend 'remember me' cookies each time
	
					$sessioncode=trim($sessioncode); // trim to prevent passing in blank values to match uninitiated DB rows
	
					// Try to recover session from the database if PHP session has timed out
					if ( (!isset($_SESSION['qa_session_userid_'.$suffix])) && (!empty($handle)) && (!empty($sessioncode)) ) {
						require_once QA_INCLUDE_DIR.'qa-db-selects.php';
						
						$userinfo=qa_db_single_select(qa_db_user_account_selectspec($handle, false)); // don't get any pending
						
						if (strtolower(trim($userinfo['sessioncode'])) == strtolower($sessioncode)) {
							$_SESSION['qa_session_userid_'.$suffix]=$userinfo['userid'];
							$_SESSION['qa_session_source_'.$suffix]=$userinfo['sessionsource'];
							$_SESSION['qa_session_verify_'.$suffix]=qa_session_verify_code($userinfo['userid']);
						} else
							qa_clear_session_cookie(); // if cookie not valid, remove it to save future checks
					}
				}
				
				$qa_logged_in_userid_checked=true;
			}
			
			return @$_SESSION['qa_session_userid_'.$suffix];
		}
		
		
		function qa_get_logged_in_source()
	/*
		Get the source of the currently logged in user, from call to qa_log_in_external_user() or null if logged in normally
	*/
		{
			$userid=qa_get_logged_in_userid();
			$suffix=qa_session_var_suffix();
			
			if (isset($userid))
				return @$_SESSION['qa_session_source_'.$suffix];
		}
		
		
		function qa_logged_in_user_selectspec()
	/*
		Return selectspec array (see qa-db.php) to get information about currently logged in user
	*/
		{
			global $qa_cached_logged_in_user;
			
			$userid=qa_get_logged_in_userid();

			if (isset($userid) && !isset($qa_cached_logged_in_user)) {
				require_once QA_INCLUDE_DIR.'qa-db-selects.php';
				return qa_db_user_account_selectspec($userid, true);
			}
			
			return null;
		}
		
		
		function qa_logged_in_user_load($selectspec, $gotuser)
	/*
		Called after the information specified by qa_logged_in_user_selectspec() was retrieved
		from the database using $selectspec which returned $gotuser
	*/
		{
			global $qa_cached_logged_in_user;
			
			$qa_cached_logged_in_user=is_array($gotuser) ? $gotuser : false;
		}
		
		
		function qa_get_logged_in_user_field($field)
	/*
		Return $field of the currently logged in user, cache to ensure only one call to external code
	*/
		{
			global $qa_cached_logged_in_user, $qa_logged_in_pending;
			
			$userid=qa_get_logged_in_userid();
			
			if (isset($userid) && !isset($qa_cached_logged_in_user)) {
				require_once QA_INCLUDE_DIR.'qa-db-selects.php';
				$qa_logged_in_pending=true;
				qa_db_select_with_pending(); // if not yet loaded, retrieve via standard mechanism
			}
			
			return @$qa_cached_logged_in_user[$field];
		}
		
		
		function qa_get_logged_in_points()
	/*
		Return the number of points of the currently logged in user, or null if none is logged in
	*/
		{
			return qa_get_logged_in_user_field('points');
		}

		
		function qa_get_mysql_user_column_type()
	/*
		Return column type to use for users (if not using single sign-on integration)
	*/
		{
			return 'INT UNSIGNED';
		}


		function qa_get_one_user_html($handle, $microformats)
	/*
		Return HTML to display for user with username $handle
	*/
		{
			return strlen($handle) ? ('<A HREF="'.qa_path_html('user/'.$handle).
				'" CLASS="qa-user-link'.($microformats ? ' url nickname' : '').'">'.qa_html($handle).'</A>') : '';
		}
		
		
		function qa_get_user_avatar_html($flags, $email, $handle, $blobid, $width, $height, $size, $padding=false)
	/*
		Return HTML to display for the user's avatar, constrained to $size pixels, with optional $padding to that size
		Pass the user's fields $flags, $email, $handle, and avatar $blobid, $width and $height
	*/	
		{
			require_once QA_INCLUDE_DIR.'qa-app-format.php';
			
			if (qa_opt('avatar_allow_gravatar') && ($flags & QA_USER_FLAGS_SHOW_GRAVATAR))
				$html=qa_get_gravatar_html($email, $size);
			elseif (qa_opt('avatar_allow_upload') && (($flags & QA_USER_FLAGS_SHOW_AVATAR)) && isset($blobid))
				$html=qa_get_avatar_blob_html($blobid, $width, $height, $size, $padding);
			elseif ( (qa_opt('avatar_allow_gravatar')||qa_opt('avatar_allow_upload')) && qa_opt('avatar_default_show') && strlen(qa_opt('avatar_default_blobid')) )
				$html=qa_get_avatar_blob_html(qa_opt('avatar_default_blobid'), qa_opt('avatar_default_width'), qa_opt('avatar_default_height'), $size, $padding);
			else
				$html=null;
				
			return (isset($html) && strlen($handle)) ? ('<A HREF="'.qa_path_html('user/'.$handle).'" CLASS="qa-avatar-link">'.$html.'</A>') : $html;
		}
		

		function qa_get_user_email($userid)
	/*
		Return email address for user $userid (if not using single sign-on integration)
	*/
		{
			$userinfo=qa_db_select_with_pending(qa_db_user_account_selectspec($userid, true));

			return $userinfo['email'];
		}
		

		function qa_user_report_action($userid, $action, $questionid, $answerid, $commentid)
	/*
		Called after a database write $action performed by a user $userid, relating to $questionid,
		$answerid and/or $commentid (if not using single sign-on integration)
	*/
		{
			require_once QA_INCLUDE_DIR.'qa-db-users.php';
			
			qa_db_user_written($userid, qa_remote_ip_address());
		}

		
		function qa_user_level_string($level)
	/*
		Return textual representation of the user $level
	*/
		{
			if ($level>=QA_USER_LEVEL_SUPER)
				$string='users/level_super';
			elseif ($level>=QA_USER_LEVEL_ADMIN)
				$string='users/level_admin';
			elseif ($level>=QA_USER_LEVEL_MODERATOR)
				$string='users/level_moderator';
			elseif ($level>=QA_USER_LEVEL_EDITOR)
				$string='users/level_editor';
			elseif ($level>=QA_USER_LEVEL_EXPERT)
				$string='users/level_expert';
			else
				$string='users/registered_user';
			
			return qa_lang($string);
		}

		
		function qa_get_login_links($rooturl, $tourl)
	/*
		Return an array of links to login, register, email confirm and logout pages (if not using single sign-on integration)
	*/
		{
			return array(
				'login' => qa_path('login', isset($tourl) ? array('to' => $tourl) : null, $rooturl),
				'register' => qa_path('register', isset($tourl) ? array('to' => $tourl) : null, $rooturl),
				'confirm' => qa_path('confirm', null, $rooturl),
				'logout' => qa_path('logout', null, $rooturl),
			);
		}

	} // end of: if (QA_FINAL_EXTERNAL_USERS) { ... } else { ... }


	function qa_get_logged_in_handle()
/*
	Return displayable handle/username of currently logged in user, or null if none
*/
	{
		return qa_get_logged_in_user_field(QA_FINAL_EXTERNAL_USERS ? 'publicusername' : 'handle');
	}


	function qa_get_logged_in_email()
/*
	Return email of currently logged in user, or null if none
*/
	{
		return qa_get_logged_in_user_field('email');
	}


	function qa_get_logged_in_level()
/*
	Return level of currently logged in user, or null if none
*/
	{
		return qa_get_logged_in_user_field('level');
	}

	
	function qa_get_logged_in_flags()
/*
	Return flags (see QA_USER_FLAGS_*) of currently logged in user, or null if none
*/
	{
		return QA_FINAL_EXTERNAL_USERS ? 0 : qa_get_logged_in_user_field('flags');
	}

	
	function qa_user_permit_error($permitoption=null, $actioncode=null)
/*
	Check whether the logged in user has permission to perform $permitoption.
	If $permitoption is null, this simply checks whether the user is blocked.
	Optionally provide an $actioncode to also check against user or IP rate limits.

	Possible results, in order of priority (i.e. if more than one reason, first given):
	'level' => a special privilege level (e.g. expert) or minimum number of points is required
	'login' => the user should login or register
	'userblock' => the user has been blocked
	'ipblock' => the ip address has been blocked
	'confirm' => the user should confirm their email address
	'limit' => the user or IP address has reached a rate limit (if $actioncode specified)
	false => the operation can go ahead
*/
	{
		require_once QA_INCLUDE_DIR.'qa-app-limits.php';

		$error=qa_permit_error($permitoption, qa_get_logged_in_userid(), qa_get_logged_in_level(), qa_get_logged_in_flags());
		
		if ((!$error) && qa_is_ip_blocked())
			$error='ipblock';
		
		if (isset($actioncode) && !$error)
			if (qa_limits_remaining(qa_get_logged_in_userid(), $actioncode)<=0)
				$error='limit';
		
		return $error;
	}
	
	
	function qa_permit_error($permitoption, $userid, $userlevel, $userflags, $userpoints=null)
/*
	Check whether $userid (null for no user) can perform $permitoption. Result as for qa_user_permit_error(...).
	If appropriate, pass the user's level in $userlevel, flags in $userflags and points in $userpoints.
	If $userid is currently logged in, you can set $userpoints=null to retrieve them only if necessary.
*/
	{
		$permit=isset($permitoption) ? qa_opt($permitoption) : QA_PERMIT_ALL;

		if ( ($permit==QA_PERMIT_POINTS) || ($permit==QA_PERMIT_POINTS_CONFIRMED) ) { // deal with points threshold by converting as appropriate
			$permit=QA_PERMIT_EXPERTS; // by default, only special users pass this, but...
			
			if (isset($userid)) {
				if ( (!isset($userpoints)) && ($userid==qa_get_logged_in_userid()) )
					$userpoints=qa_get_logged_in_points(); // allow late retrieval of points (to avoid unnecessary DB query when using external users)
			
				if ($userpoints>=qa_opt($permitoption.'_points'))
					$permit=($permit==QA_PERMIT_POINTS_CONFIRMED) ? QA_PERMIT_CONFIRMED : QA_PERMIT_USERS; // convert if user has enough points
			}
		}
		
		if ($permit>=QA_PERMIT_ALL)
			$error=false;
			
		elseif ($permit>=QA_PERMIT_USERS)
			$error=isset($userid) ? false : 'login';
			
		elseif ($permit>=QA_PERMIT_CONFIRMED) {
			if (!isset($userid))
				$error='login';
			
			elseif (
				QA_FINAL_EXTERNAL_USERS || // not currently supported by single sign-on integration
				($userlevel>=QA_USER_LEVEL_EXPERT) || // if user assigned to a higher level, no need
				($userflags & QA_USER_FLAGS_EMAIL_CONFIRMED) || // actual confirmation
				(!qa_opt('confirm_user_emails')) // if this option off, we can't ask it of the user
			)
				$error=false;
			
			else
				$error='confirm';

		} elseif ($permit>=QA_PERMIT_EXPERTS)
			$error=(isset($userid) && ($userlevel>=QA_USER_LEVEL_EXPERT)) ? false : 'level';
			
		elseif ($permit>=QA_PERMIT_EDITORS)
			$error=(isset($userid) && ($userlevel>=QA_USER_LEVEL_EDITOR)) ? false : 'level';
			
		elseif ($permit>=QA_PERMIT_MODERATORS)
			$error=(isset($userid) && ($userlevel>=QA_USER_LEVEL_MODERATOR)) ? false : 'level';
			
		elseif ($permit>=QA_PERMIT_ADMINS)
			$error=(isset($userid) && ($userlevel>=QA_USER_LEVEL_ADMIN)) ? false : 'level';
			
		else
			$error=(isset($userid) && ($userlevel>=QA_USER_LEVEL_SUPER)) ? false : 'level';
		
		if (isset($userid) && ($userflags & QA_USER_FLAGS_USER_BLOCKED) && ($error!='level'))
			$error='userblock';
		
		return $error;
	}
	
	
	function qa_user_use_captcha($captchaoption)
/*
	Return whether a captcha should be presented for operation specified by $captchaoption
*/
	{
		$usecaptcha=false;
		
		if (qa_opt($captchaoption)) {
			$userid=qa_get_logged_in_userid();
			
			if ( (!isset($userid)) || !(
				QA_FINAL_EXTERNAL_USERS ||
				(!qa_opt('captcha_on_unconfirmed')) || // we might not care about unconfirmed users
				(!qa_opt('confirm_user_emails')) || // if this option off, we can't ask it of the user
				(qa_get_logged_in_level()>=QA_USER_LEVEL_EXPERT) || // if assigned to a higher level, no need
				(qa_get_logged_in_flags() & QA_USER_FLAGS_EMAIL_CONFIRMED) // actual confirmation
			))
				$usecaptcha=true;
		}
		
		return $usecaptcha;
	}
	
	
	function qa_user_userfield_label($userfield)
/*
	Return the label to display for $userfield as retrieved from the database, using default if no name set
*/
	{
		if (isset($userfield['content']))
			return $userfield['content'];
		
		else {
			$defaultlabels=array(
				'name' => 'users/full_name',
				'about' => 'users/about',
				'location' => 'users/location',
				'website' => 'users/website',
			);
			
			if (isset($defaultlabels[$userfield['title']]))
				return qa_lang($defaultlabels[$userfield['title']]);
		}
			
		return '';
	}
	

/*
	Omit PHP closing tag to help avoid accidental output
*/