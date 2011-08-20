<?php

/*
	Question2Answer 1.4.1 (c) 2011, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-answers.php
	Version: 1.4.1
	Date: 2011-07-10 06:58:57 GMT
	Description: Controller for page listing recent answers on questions


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

	require_once QA_INCLUDE_DIR.'qa-db-selects.php';
	require_once QA_INCLUDE_DIR.'qa-app-format.php';
	require_once QA_INCLUDE_DIR.'qa-app-q-list.php';
	
	$categoryslugs=$pass_subrequests;
	$countslugs=count($categoryslugs);


//	Get list of answers with related questions, plus category information

	@list($questions, $categories, $categoryid)=qa_db_select_with_pending(
		qa_db_recent_a_qs_selectspec($qa_login_userid, 0, $categoryslugs),
		qa_db_category_nav_selectspec($categoryslugs, false),
		$countslugs ? qa_db_slugs_to_category_id_selectspec($categoryslugs) : null
	);
	
	if ($countslugs) {
		if (!isset($categoryid))
			return include QA_INCLUDE_DIR.'qa-page-not-found.php';
	
		$categorytitlehtml=qa_html($categories[$categoryid]['title']);
		$sometitle=qa_lang_html_sub('main/recent_as_in_x', $categorytitlehtml);
		$nonetitle=qa_lang_html_sub('main/no_answers_in_x', $categorytitlehtml);

	} else {
		$sometitle=qa_lang_html('main/recent_as_title');
		$nonetitle=qa_lang_html('main/no_answers_found');
	}

	
//	Prepare and return content for theme

	return qa_q_list_page_content(
		qa_any_sort_and_dedupe($questions), // questions
		qa_opt('page_size_activity'), // questions per page
		0, // start offset
		null, // total count (null to hide page links)
		$sometitle, // title if some questions
		$nonetitle, // title if no questions
		$categories, // categories for navigation
		$categoryid, // selected category id
		false, // show question counts in category navigation
		'answers/', // prefix for links in category navigation
		qa_opt('feed_for_activity') ? 'answers' : null, // prefix for RSS feed paths (null to hide)
		qa_html_suggest_qs_tags(qa_using_tags(), qa_category_path_request($categories, $categoryid)) // suggest what to do next
	);


/*
	Omit PHP closing tag to help avoid accidental output
*/