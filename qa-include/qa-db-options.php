<?php

/*
	Question2Answer 1.4.1 (c) 2011, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-db-options.php
	Version: 1.4.1
	Date: 2011-07-10 06:58:57 GMT
	Description: Database-level access to table containing admin options


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


	function qa_db_set_option($name, $value)
/*
	Set option $name to $value in the database
*/
	{
		qa_db_query_sub(
			'REPLACE ^options (title, content) VALUES ($, $)',
			$name, $value
		);
	}


/*
	Omit PHP closing tag to help avoid accidental output
*/