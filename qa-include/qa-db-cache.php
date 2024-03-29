<?php
	
/*
	Question2Answer 1.4.1 (c) 2011, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-db-cache.php
	Version: 1.4.1
	Date: 2011-07-10 06:58:57 GMT
	Description: Database-level access to cache table


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


	require_once QA_INCLUDE_DIR.'qa-db-maxima.php';


	function qa_db_cache_set($type, $cacheid, $content)
/*
	Create (or replace) the item ($type, $cacheid) in the database cache table with $content
*/
	{
		qa_db_query_sub(
			'DELETE FROM ^cache WHERE lastread<NOW()-INTERVAL # SECOND',
			QA_DB_MAX_CACHE_AGE
		);

		qa_db_query_sub(
			'REPLACE ^cache (type, cacheid, content, created, lastread) VALUES ($, #, $, NOW(), NOW())',
			$type, $cacheid, $content
		);
	}
	
	
	function qa_db_cache_get($type, $cacheid)
/*
	Retrieve the item ($type, $cacheid) from the database cache table
*/
	{
		$content=qa_db_read_one_value(qa_db_query_sub(
			'SELECT content FROM ^cache WHERE type=$ AND cacheid=#',
			$type, $cacheid
		), true);
		
		if (isset($content))
			qa_db_query_sub(
				'UPDATE ^cache SET lastread=NOW() WHERE type=$ AND cacheid=#',
				$type, $cacheid
			);
		
		return $content;
	}
	

/*
	Omit PHP closing tag to help avoid accidental output
*/