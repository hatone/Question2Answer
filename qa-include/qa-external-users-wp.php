<?php

/*
	Question2Answer 1.4.1 (c) 2011, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-external-users-wp.php
	Version: 1.4.1
	Date: 2011-07-10 06:58:57 GMT
	Description: External user functions for WordPress integration


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


	function qa_get_mysql_user_column_type()
	{
		return 'BIGINT UNSIGNED';
	}


	function qa_get_login_links($relative_url_prefix, $redirect_back_to_url)
	{
		return array(
			'login' => wp_login_url(qa_opt('site_url').$redirect_back_to_url),
			'register' => site_url('wp-login.php?action=register'),
			'logout' => strtr(wp_logout_url(), array('&amp;' => '&')),
		);
	}
	

	function qa_get_logged_in_user()
	{
		$wordpressuser=wp_get_current_user();
		
		if ($wordpressuser->ID==0)
			return null;

		else {
			if (current_user_can('administrator'))
				$level=QA_USER_LEVEL_ADMIN;
			elseif (current_user_can('editor'))
				$level=QA_USER_LEVEL_EDITOR;
			elseif (current_user_can('contributor'))
				$level=QA_USER_LEVEL_EXPERT;
			else
				$level=QA_USER_LEVEL_BASIC;
			
			return array(
				'userid' => $wordpressuser->ID,
				'publicusername' => $wordpressuser->user_nicename,
				'email' => $wordpressuser->user_email,
				'level' => $level,
			);
		}
	}

	
	function qa_get_user_email($userid)
	{
		$user=get_userdata($userid);
		
		return @$user->user_email;
	}
	

	function qa_get_userids_from_public($publicusernames)
	{
		global $table_prefix;
		
		if (count($publicusernames))
			return qa_db_read_all_assoc(qa_db_query_sub(
				'SELECT user_nicename, ID FROM '.$table_prefix.'users WHERE user_nicename IN ($)',
				$publicusernames
			), 'user_nicename', 'ID');
		else
			return array();
	}


	function qa_get_public_from_userids($userids)
	{
		global $table_prefix;
		
		if (count($userids))
			return qa_db_read_all_assoc(qa_db_query_sub(
				'SELECT user_nicename, ID FROM '.$table_prefix.'users WHERE ID IN (#)',
				$userids
			), 'ID', 'user_nicename');
		else
			return array();
	}


	function qa_get_logged_in_user_html($logged_in_user, $relative_url_prefix)
	{
		$publicusername=$logged_in_user['publicusername'];
		
		return '<A HREF="'.htmlspecialchars($relative_url_prefix.'user/'.urlencode($publicusername)).
			'" CLASS="qa-user-link">'.htmlspecialchars($publicusername).'</A>';
	}


	function qa_get_users_html($userids, $should_include_link, $relative_url_prefix)
	{
		$useridtopublic=qa_get_public_from_userids($userids);
		
		$usershtml=array();

		foreach ($userids as $userid) {
			$publicusername=$useridtopublic[$userid];
			
			$usershtml[$userid]=htmlspecialchars($publicusername);
			
			if ($should_include_link)
				$usershtml[$userid]='<A HREF="'.htmlspecialchars($relative_url_prefix.'user/'.urlencode($publicusername)).
					'" CLASS="qa-user-link">'.$usershtml[$userid].'</A>';
		}
			
		return $usershtml;
	}


	function qa_user_report_action($userid, $action, $questionid, $answerid, $commentid)
	{
	}


/*
	Omit PHP closing tag to help avoid accidental output
*/