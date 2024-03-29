<?php
	
/*
	Question2Answer 1.4.1 (c) 2011, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-db-post-update.php
	Version: 1.4.1
	Date: 2011-07-10 06:58:57 GMT
	Description:  Database functions for changing a question, answer or comment


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


	function qa_db_post_set_selchildid($questionid, $selchildid)
/*
	Update the selected answer in the database for $questionid to $selchildid
*/
	{
		qa_db_query_sub(
			'UPDATE ^posts SET selchildid=# WHERE postid=#',
			$selchildid, $questionid
		);
	}

	
	function qa_db_post_set_type($postid, $type, $lastuserid, $lastip)
/*
	Set the type in the database of $postid to $type, and record that $lastuserid did it
*/
	{
		if (isset($lastuserid) || isset($lastip))
			qa_db_query_sub(
				'UPDATE ^posts SET type=$, updated=NOW(), lastuserid=$, lastip=INET_ATON($) WHERE postid=#',
				$type, $lastuserid, $lastip, $postid
			);
		else
			qa_db_query_sub(
				'UPDATE ^posts SET type=$ WHERE postid=#',
				$type, $postid
			);
	}

	
	function qa_db_post_set_parent($postid, $parentid, $lastuserid=null, $lastip=null)
/*
	Set the parent in the database of $postid to $parentid, and optioanlly record that $lastuserid did it from $lastip (if at least one is specified)
*/
	{
		if (isset($lastuserid) || isset($lastip))
			qa_db_query_sub(
				'UPDATE ^posts SET parentid=#, updated=NOW(), lastuserid=$, lastip=INET_ATON($) WHERE postid=#',
				$parentid, $lastuserid, $lastip, $postid
			);
		else
			qa_db_query_sub(
				'UPDATE ^posts SET parentid=# WHERE postid=#',
				$parentid, $postid
			);
	}

	
	function qa_db_post_set_content($postid, $title, $content, $format, $tagstring, $notify, $lastuserid=null, $lastip=null)
/*
	Set the text fields in the database of $postid to $title, $content, $tagstring and $notify, and record
	that $lastuserid did it from $lastip (if at least one is specified)
*/
	{
		if (isset($lastuserid) || isset($lastip))
			qa_db_query_sub(
				'UPDATE ^posts SET title=$, content=$, format=$, tags=$, notify=$, updated=NOW(), lastuserid=$, lastip=INET_ATON($) WHERE postid=#',
				$title, $content, $format, $tagstring, $notify, $lastuserid, $lastip, $postid
			);
		else
			qa_db_query_sub(
				'UPDATE ^posts SET title=$, content=$, format=$, tags=$, notify=$ WHERE postid=#',
				$title, $content, $format, $tagstring, $notify, $postid
			);
	}

	
	function qa_db_post_set_userid($postid, $userid)
/*
	Set the author in the database of $postid to $userid, and set the lastuserid to $userid as well if appropriate
*/
	{
		qa_db_query_sub(
			'UPDATE ^posts SET userid=$, lastuserid=IF(updated IS NULL, lastuserid, COALESCE(lastuserid,$)) WHERE postid=#',
			$userid, $userid, $postid
		);
	}
	
	
	function qa_db_post_set_category($postid, $categoryid)
/*
	Set the (exact) category in the database of $postid to $categoryid
*/
	{
		qa_db_query_sub(
			'UPDATE ^posts SET categoryid=# WHERE postid=#',
			$categoryid, $postid
		);
	}
	
	
	function qa_db_posts_set_category_path($postids, $path)
/*
	Set the category path in the database of each of $postids to $path retrieved via qa_db_post_get_category_path()
*/
	{
		if (count($postids))
			qa_db_query_sub(
				'UPDATE ^posts SET categoryid=#, catidpath1=#, catidpath2=#, catidpath3=# WHERE postid IN (#)',
				$path['categoryid'], $path['catidpath1'], $path['catidpath2'], $path['catidpath3'], $postids
			); // requires QA_CATEGORY_DEPTH=4
	}
	
	
	function qa_db_post_delete($postid)
/*
	Deletes post $postid from the database (will also delete any votes on the post due to cascading)
*/
	{
		qa_db_query_sub(
			'DELETE FROM ^posts WHERE postid=#',
			$postid
		);
	}

	
	function qa_db_titlewords_get_post_wordids($postid)
/*
	Return an array of wordids that were indexed in the database for the title of $postid
*/
	{
		return qa_db_read_all_values(qa_db_query_sub(
			'SELECT wordid FROM ^titlewords WHERE postid=#',
			$postid
		));
	}

	
	function qa_db_titlewords_delete_post($postid)
/*
	Remove all entries in the database index of title words for $postid
*/
	{
		qa_db_query_sub(
			'DELETE FROM ^titlewords WHERE postid=#',
			$postid
		);
	}


	function qa_db_contentwords_get_post_wordids($postid)
/*
	Return an array of wordids that were indexed in the database for the content of $postid
*/
	{
		return qa_db_read_all_values(qa_db_query_sub(
			'SELECT wordid FROM ^contentwords WHERE postid=#',
			$postid
		));
	}

	
	function qa_db_contentwords_delete_post($postid)
/*
	Remove all entries in the database index of content words for $postid
*/
	{
		qa_db_query_sub(
			'DELETE FROM ^contentwords WHERE postid=#',
			$postid
		);
	}
	
	
	function qa_db_tagwords_get_post_wordids($postid)
/*
	Return an array of wordids that were indexed in the database for the individual words in tags of $postid
*/
	{
		return qa_db_read_all_values(qa_db_query_sub(
			'SELECT wordid FROM ^tagwords WHERE postid=#',
			$postid
		));
	}
	
	
	function qa_db_tagwords_delete_post($postid)
/*
	Remove all entries in the database index of individual words in tags of $postid
*/
	{
		qa_db_query_sub(
			'DELETE FROM ^tagwords WHERE postid=#',
			$postid
		);
	}


	function qa_db_posttags_get_post_wordids($postid)
/*
	Return an array of wordids that were indexed in the database for the whole tags of $postid
*/
	{
		return qa_db_read_all_values(qa_db_query_sub(
			'SELECT wordid FROM ^posttags WHERE postid=#',
			$postid
		));
	}

	
	function qa_db_posttags_delete_post($postid)
/*
	Remove all entries in the database index of whole tags for $postid
*/
	{
		qa_db_query_sub(
			'DELETE FROM ^posttags WHERE postid=#',
			$postid
		);
	}


/*
	Omit PHP closing tag to help avoid accidental output
*/