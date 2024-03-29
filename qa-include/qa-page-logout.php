<?php

/*
	Question2Answer 1.4.1 (c) 2011, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-logout.php
	Version: 1.4.1
	Date: 2011-07-10 06:58:57 GMT
	Description: Controller for logout page (not much to do)


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

	if (QA_FINAL_EXTERNAL_USERS)
		qa_fatal_error('User logout is handled by external code');
	
	if (isset($qa_login_userid))
		qa_set_logged_in_user(null);
		
	qa_redirect(''); // back to home page
	

/*
	Omit PHP closing tag to help avoid accidental output
*/