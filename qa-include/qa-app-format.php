<?php

/*
	Question2Answer 1.4.1 (c) 2011, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-app-format.php
	Version: 1.4.1
	Date: 2011-07-10 06:58:57 GMT
	Description: Common functions for creating theme-ready structures from data


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


	define('QA_PAGE_FLAGS_EXTERNAL', 1);
	define('QA_PAGE_FLAGS_NEW_WINDOW', 2);


	function qa_time_to_string($seconds)
/*
	Return textual representation of $seconds
*/
	{
		$seconds=max($seconds, 1);
		
		$scales=array(
			31557600 => array( 'main/1_year'   , 'main/x_years'   ),
			 2629800 => array( 'main/1_month'  , 'main/x_months'  ),
			  604800 => array( 'main/1_week'   , 'main/x_weeks'   ),
			   86400 => array( 'main/1_day'    , 'main/x_days'    ),
			    3600 => array( 'main/1_hour'   , 'main/x_hours'   ),
			      60 => array( 'main/1_minute' , 'main/x_minutes' ),
			       1 => array( 'main/1_second' , 'main/x_seconds' ),
		);
		
		foreach ($scales as $scale => $phrases)
			if ($seconds>=$scale) {
				$count=floor($seconds/$scale);
			
				if ($count==1)
					$string=qa_lang($phrases[0]);
				else
					$string=qa_lang_sub($phrases[1], $count);
					
				break;
			}
			
		return $string;
	}


	function qa_post_is_by_user($post, $userid, $cookieid)
/*
	Check if $post is by user $userid, or if post is anonymous and $userid not specified, then
	check if $post is by the anonymous user identified by $cookieid
*/
	{
		// In theory we should only test against NULL here, i.e. use isset($post['userid'])
		// but the risk of doing so is so high (if a bug creeps in that allows userid=0)
		// that I'm doing a tougher test. This will break under a zero user or cookie id.
		
		if (@$post['userid'] || $userid)
			return @$post['userid']==$userid;
		elseif (@$post['cookieid'])
			return strcmp($post['cookieid'], $cookieid)==0;
		
		return false;
	}

	
	function qa_userids_handles_html($useridhandles, $microformats=false)
/*
	Return array which maps the ['userid'] and/or ['lastuserid'] in each element of
	$useridhandles to its HTML representation. For internal user management, corresponding
	['handle'] and/or ['lasthandle'] are required in each element.
*/
	{
		require_once QA_INCLUDE_DIR.'qa-app-users.php';
		
		global $qa_root_url_relative;
			
		if (QA_FINAL_EXTERNAL_USERS) {
			$keyuserids=array();
	
			foreach ($useridhandles as $useridhandle) {
				if (isset($useridhandle['userid']))
					$keyuserids[$useridhandle['userid']]=true;

				if (isset($useridhandle['lastuserid']))
					$keyuserids[$useridhandle['lastuserid']]=true;
			}
	
			if (count($keyuserids))
				return qa_get_users_html(array_keys($keyuserids), true, $qa_root_url_relative, $microformats);
			else
				return array();
		
		} else {
			$usershtml=array();

			foreach ($useridhandles as $useridhandle) {
				if (isset($useridhandle['userid']) && $useridhandle['handle'])
					$usershtml[$useridhandle['userid']]=qa_get_one_user_html($useridhandle['handle'], $microformats);

				if (isset($useridhandle['lastuserid']) && $useridhandle['lasthandle'])
					$usershtml[$useridhandle['lastuserid']]=qa_get_one_user_html($useridhandle['lasthandle'], $microformats);
			}
		
			return $usershtml;
		}
	}
	

	function qa_tag_html($tag, $microformats=false)
/*
	Convert textual $tag to HTML representation
*/
	{
		return '<A HREF="'.qa_path_html('tag/'.$tag).'"'.($microformats ? ' rel="tag"' : '').' CLASS="qa-tag-link">'.qa_html($tag).'</A>';
	}

	
	function qa_category_path($navcategories, $categoryid)
/*
	Given $navcategories retrieved for $categoryid from the database (using qa_db_category_nav_selectspec(...)),
	return an array of elements from $navcategories for the hierarchy down to $categoryid.
*/
	{
		$upcategories=array();
		
		for ($upcategory=@$navcategories[$categoryid]; isset($upcategory); $upcategory=@$navcategories[$upcategory['parentid']])
			$upcategories[$upcategory['categoryid']]=$upcategory;
			
		return array_reverse($upcategories, true);
	}
	

	function qa_category_path_html($navcategories, $categoryid)
/*
	Given $navcategories retrieved for $categoryid from the database (using qa_db_category_nav_selectspec(...)),
	return some HTML that shows the category hierarchy down to $categoryid.
*/
	{
		$categories=qa_category_path($navcategories, $categoryid);
		
		$html='';
		foreach ($categories as $category)
			$html.=(strlen($html) ? ' / ' : '').qa_html($category['title']);
			
		return $html;
	}
	
	
	function qa_category_path_request($navcategories, $categoryid)
/*
	Given $navcategories retrieved for $categoryid from the database (using qa_db_category_nav_selectspec(...)),
	return a QA request string that represents the category hierarchy down to $categoryid.
*/
	{
		$categories=qa_category_path($navcategories, $categoryid);

		$request='';
		foreach ($categories as $category)
			$request.=(strlen($request) ? '/' : '').$category['tags'];
			
		return $request;
	}
	
	
	function qa_ip_anchor_html($ip, $anchorhtml=null)
/*
	Return HTML to use for $ip address, which links to appropriate page with $anchorhtml
*/
	{
		if (!strlen($anchorhtml))
			$anchorhtml=qa_html($ip);
		
		return '<A HREF="'.qa_path_html('ip/'.$ip).'" TITLE="'.qa_lang_html_sub('main/ip_address_x', qa_html($ip)).'" CLASS="qa-ip-link">'.$anchorhtml.'</A>';
	}
	
	
	function qa_post_html_fields($post, $userid, $cookieid, $usershtml, $dummy, $options=array())
/*
	Given $post retrieved from database, return array of mostly HTML to be passed to theme layer.
	$userid and $cookieid refer to the user *viewing* the page.
	$usershtml is an array of [user id] => [HTML representation of user] built ahead of time.
	$dummy is a placeholder (used to be $categories parameter but that's no longer needed)
	$options is an array of non-required elements which set what is displayed. It can contain true for keys:
	'tagsview', 'answersview', 'viewsview', 'voteview', 'flagsview', 'whatlink', 'whenview', 'whoview', 'ipview',
	'pointsview', 'showurllinks', 'microformats', 'isselected'. $options also has other optional elements:
	$options['blockwordspreg'] can be a pre-prepared regular expression fragment for censored words.
	$options['pointstitle'] can be an array of [points] => [user title] for custom user titles.
	$options['avatarsize'] can be the size in pixels of an avatar to be displayed.
	$options['categorypathprefix'] can be a prefix to use for category links (e.g. 'activity/').
	If something is missing from $post (e.g. ['content']), correponding HTML also omitted.
*/
	{
		if (isset($options['blockwordspreg']))
			require_once QA_INCLUDE_DIR.'qa-util-string.php';
		
		$fields=array();
		$fields['raw']=$post;
		
	//	Useful stuff used throughout function

		$postid=$post['postid'];
		$isquestion=($post['basetype']=='Q');
		$isanswer=($post['basetype']=='A');
		$isbyuser=qa_post_is_by_user($post, $userid, $cookieid);
		$anchor=urlencode(qa_anchor($post['basetype'], $postid));
		$microformats=@$options['microformats'];
		$isselected=@$options['isselected'];
		
	//	High level information

		$fields['hidden']=$post['hidden'];
		$fields['tags']='ID="'.$anchor.'"';
		
		if ($microformats)
			$fields['classes']='hentry '.($isquestion ? 'question' : ($isanswer ? ($isselected ? 'answer answer-selected' : 'answer') : 'comment'));
	
	//	Question-specific stuff (title, URL, tags, answer count, category)
	
		if ($isquestion) {
			if (isset($post['title'])) {
				if (isset($options['blockwordspreg']))
					$post['title']=qa_block_words_replace($post['title'], $options['blockwordspreg']);
				
				$fields['title']=qa_html($post['title']);
				if ($microformats)
					$fields['title']='<SPAN CLASS="entry-title">'.$fields['title'].'</SPAN>';
					
				$fields['url']=qa_path_html(qa_q_request($postid, $post['title']));
				
				/*if (isset($post['score'])) // useful for setting match thresholds
					$fields['title'].=' <SMALL>('.$post['score'].')</SMALL>';*/
			}
				
			if (@$options['tagsview'] && isset($post['tags'])) {
				$fields['q_tags']=array();
				
				$tags=qa_tagstring_to_tags($post['tags']);
				foreach ($tags as $tag) {
					if (isset($options['blockwordspreg']) && count(qa_block_words_match_all($tag, $options['blockwordspreg']))) // skip censored tags
						continue;
						
					$fields['q_tags'][]=qa_tag_html($tag, $microformats);
				}
			}
		
			if (@$options['answersview'] && isset($post['acount'])) {
				$fields['answers_raw']=$post['acount'];
				
				$fields['answers']=($post['acount']==1) ? qa_lang_html_sub_split('main/1_answer', '1', '1')
					: qa_lang_html_sub_split('main/x_answers', number_format($post['acount']));
					
				$fields['answer_selected']=isset($post['selchildid']);
			}
			
			if (@$options['viewsview'] && isset($post['views'])) {
				$fields['views_raw']=$post['views'];
				
				$fields['views']=($post['views']==1) ? qa_lang_html_sub_split('main/1_view', '1', '1') :
					qa_lang_html_sub_split('main/x_views', number_format($post['views']));
			}

			if (isset($post['categoryname']) && isset($post['categorybackpath']))
				$fields['where']=qa_lang_html_sub_split('main/in_category_x',
					'<A HREF="'.qa_path_html(@$options['categorypathprefix'].implode('/', array_reverse(explode('/', $post['categorybackpath'])))).
					'" CLASS="qa-category-link">'.qa_html($post['categoryname']).'</A>');
		}
		
	//	Answer-specific stuff (selection)
		
		if ($isanswer) {
			$fields['selected']=$isselected;
			
			if ($isselected)
				$fields['select_text']=qa_lang_html('question/select_text');
		}

	//	Post content
		
		if (!empty($post['content'])) {
			$viewer=qa_load_viewer($post['content'], $post['format']);
			
			$fields['content']=$viewer->get_html($post['content'], $post['format'], array(
				'blockwordspreg' => @$options['blockwordspreg'],
				'showurllinks' => @$options['showurllinks'],
				'linksnewwindow' => @$options['linksnewwindow'],
			));
			
			if ($microformats)
				$fields['content']='<SPAN CLASS="entry-content">'.$fields['content'].'</SPAN>';
			
			$fields['content']='<A NAME="'.qa_html($postid).'"></A>'.$fields['content'];
				// this is for backwards compatibility with any existing links using the old style of anchor
				// that contained the post id only (changed to be valid under W3C specifications)
		}
		
	//	Voting stuff
			
		if (@$options['voteview']) {
			$voteview=$options['voteview'];
		
		//	Calculate raw values and pass through
		
			$upvotes=(int)@$post['upvotes'];
			$downvotes=(int)@$post['downvotes'];
			$netvotes=(int)($upvotes-$downvotes);
			
			$fields['upvotes_raw']=$upvotes;
			$fields['downvotes_raw']=$downvotes;
			$fields['netvotes_raw']=$netvotes;

		//	Create HTML versions...
			
			$upvoteshtml=qa_html($upvotes);
			$downvoteshtml=qa_html($downvotes);

			if ($netvotes>=1)
				$netvoteshtml='+'.qa_html($netvotes);
			elseif ($netvotes<=-1)
				$netvoteshtml='&ndash;'.qa_html(-$netvotes);
			else
				$netvoteshtml='0';
				
		//	...with microformats if appropriate

			if ($microformats) {
				$netvoteshtml.='<SPAN CLASS="votes-up"><SPAN CLASS="value-title" TITLE="'.$upvoteshtml.'"></SPAN></SPAN>'.
					'<SPAN CLASS="votes-down"><SPAN CLASS="value-title" TITLE="'.$downvoteshtml.'"></SPAN></SPAN>';
				$upvoteshtml='<SPAN CLASS="votes-up">'.$upvoteshtml.'</SPAN>';
				$downvoteshtml='<SPAN CLASS="votes-down">'.$downvoteshtml.'</SPAN>';
			}
			
		//	Pass information on vote viewing
		
		//	$voteview will be one of: updown, net, updown-disabled-level, net-disabled-level, updown-disabled-page, net-disabled-page
				
			$fields['vote_view']=(substr($voteview, 0, 6)=='updown') ? 'updown' : 'net';
			
			$fields['upvotes_view']=($upvotes==1) ? qa_lang_html_sub_split('main/1_liked', $upvoteshtml, '1')
				: qa_lang_html_sub_split('main/x_liked', $upvoteshtml);
	
			$fields['downvotes_view']=($downvotes==1) ? qa_lang_html_sub_split('main/1_disliked', $downvoteshtml, '1')
				: qa_lang_html_sub_split('main/x_disliked', $downvoteshtml);
			
			$fields['netvotes_view']=(abs($netvotes)==1) ? qa_lang_html_sub_split('main/1_vote', $netvoteshtml, '1')
				: qa_lang_html_sub_split('main/x_votes', $netvoteshtml);
		
		//	Voting buttons
			
			$fields['vote_tags']='ID="voting_'.qa_html($postid).'"';
			$onclick='onClick="return qa_vote_click(this);"';
			
			if ($fields['hidden']) {
				$fields['vote_state']='disabled';
				$fields['vote_up_tags']='TITLE="'.qa_lang_html($isanswer ? 'main/vote_disabled_hidden_a' : 'main/vote_disabled_hidden_q').'"';
				$fields['vote_down_tags']=$fields['vote_up_tags'];
			
			} elseif ($isbyuser) {
				$fields['vote_state']='disabled';
				$fields['vote_up_tags']='TITLE="'.qa_lang_html($isanswer ? 'main/vote_disabled_my_a' : 'main/vote_disabled_my_q').'"';
				$fields['vote_down_tags']=$fields['vote_up_tags'];
				
			} elseif (strpos($voteview, '-disabled-')) {
				$fields['vote_state']=(@$post['uservote']>0) ? 'voted_up_disabled' : ((@$post['uservote']<0) ? 'voted_down_disabled' : 'disabled');
				
				if (strpos($voteview, '-disabled-page'))
					$fields['vote_up_tags']='TITLE="'.qa_lang_html('main/vote_disabled_q_page_only').'"';
				else
					$fields['vote_up_tags']='TITLE="'.qa_lang_html('main/vote_disabled_level').'"';
					
				$fields['vote_down_tags']=$fields['vote_up_tags'];

			} elseif (@$post['uservote']>0) {
				$fields['vote_state']='voted_up';
				$fields['vote_up_tags']='TITLE="'.qa_lang_html('main/voted_up_popup').'" NAME="'.qa_html('vote_'.$postid.'_0_'.$anchor).'"'.$onclick;
				$fields['vote_down_tags']=' ';

			} elseif (@$post['uservote']<0) {
				$fields['vote_state']='voted_down';
				$fields['vote_up_tags']=' ';
				$fields['vote_down_tags']='TITLE="'.qa_lang_html('main/voted_down_popup').'" NAME="'.qa_html('vote_'.$postid.'_0_'.$anchor).'" '.$onclick;
				
			} else {
				$fields['vote_state']='enabled';
				$fields['vote_up_tags']='TITLE="'.qa_lang_html('main/vote_up_popup').'" NAME="'.qa_html('vote_'.$postid.'_1_'.$anchor).'" '.$onclick;
				$fields['vote_down_tags']='TITLE="'.qa_lang_html('main/vote_down_popup').'" NAME="'.qa_html('vote_'.$postid.'_-1_'.$anchor).'" '.$onclick;
			}
		}
		
	//	Flag count
	
		if (@$options['flagsview'] && @$post['flagcount'])
			$fields['flags']=($post['flagcount']==1) ? qa_lang_html_sub_split('main/1_flag', '1', '1')
				: qa_lang_html_sub_split('main/x_flags', $post['flagcount']);
	
	//	Created when and by whom
		
		$fields['meta_order']=qa_lang_html('main/meta_order'); // sets ordering of meta elements which can be language-specific
		
		$fields['what']=qa_lang_html($isquestion ? 'main/asked' : ($isanswer ? 'main/answered' : 'main/commented'));
			
		if (@$options['whatlink'] && !$isquestion)
			$fields['what_url']='#'.qa_html(urlencode($anchor));
		
		if (isset($post['created']) && @$options['whenview']) {
			$whenhtml=qa_html(qa_time_to_string(qa_opt('db_time')-$post['created']));
			if ($microformats)
				$whenhtml='<SPAN CLASS="published"><SPAN CLASS="value-title" TITLE="'.gmdate('Y-m-d\TH:i:sO', $post['created']).'"></SPAN>'.$whenhtml.'</SPAN>';
			
			$fields['when']=qa_lang_html_sub_split('main/x_ago', $whenhtml);
		}
		
		if (@$options['whoview']) {
			$fields['who']=qa_who_to_html($isbyuser, @$post['userid'], $usershtml, @$options['ipview'] ? @$post['createip'] : null, $microformats);
			
			if (isset($post['points'])) {
				if (@$options['pointsview'])
					$fields['who']['points']=($post['points']==1) ? qa_lang_html_sub_split('main/1_point', '1', '1')
						: qa_lang_html_sub_split('main/x_points', qa_html(number_format($post['points'])));
				
				if (isset($options['pointstitle']))
					$fields['who']['title']=qa_get_points_title_html($post['points'], $options['pointstitle']);
			}
				
			if (isset($post['level']))
				$fields['who']['level']=qa_html(qa_user_level_string($post['level']));
		}

		if ((!QA_FINAL_EXTERNAL_USERS) && (@$options['avatarsize']>0))
			$fields['avatar']=qa_get_user_avatar_html($post['flags'], $post['email'], $post['handle'],
				$post['avatarblobid'], $post['avatarwidth'], $post['avatarheight'], $options['avatarsize']);

	//	Updated when and by whom
		
		if (isset($post['updated']) && ( // show the time/user who updated if...
			(!isset($post['created'])) || // ... we didn't show the created time (should never happen in practice)
			($post['hidden']) || // ... the post was actually hidden
			(abs($post['updated']-$post['created'])>300) || // ... or over 5 minutes passed between create and update times
			($post['lastuserid']!=$post['userid']) // ... or it was updated by a different user
		)) {
			if (@$options['whenview']) {
				$whenhtml=qa_html(qa_time_to_string(qa_opt('db_time')-$post['updated']));
				if ($microformats)
					$whenhtml='<SPAN CLASS="updated"><SPAN CLASS="value-title" TITLE="'.gmdate('Y-m-d\TH:i:sO', $post['updated']).'"></SPAN>'.$whenhtml.'</SPAN>';
				
				$fields['when_2']=qa_lang_html_sub_split($fields['hidden'] ? 'question/hidden_x_ago' : 'question/edited_x_ago', $whenhtml);
			
			} else
				$fields['when_2']['prefix']=qa_lang_html($fields['hidden'] ? 'question/hidden' : 'main/edited');
			
			if ( $fields['hidden'] && $post['flagcount'] && !isset($post['lastuserid']) )
				; // special case for posts hidden by community flagging
			else
				$fields['who_2']=qa_who_to_html(isset($userid) && ($post['lastuserid']==$userid), $post['lastuserid'], $usershtml, @$options['ipview'] ? $post['lastip'] : null, false);
		}
		
	//	That's it!

		return $fields;
	}
	

	function qa_who_to_html($isbyuser, $postuserid, $usershtml, $ip=null, $microformats=false)
/*
	Return array of split HTML (prefix, data, suffix) to represent author of post
*/
	{
		if (isset($postuserid) && isset($usershtml[$postuserid])) {
			$whohtml=$usershtml[$postuserid];
			if ($microformats)
				$whohtml='<SPAN CLASS="vcard author">'.$whohtml.'</SPAN>';

		} elseif ($isbyuser)
			$whohtml=qa_lang_html('main/me');

		else {
			$whohtml=qa_lang_html('main/anonymous');
			
			if (isset($ip))
				$whohtml=qa_ip_anchor_html($ip, $whohtml);
		}
			
		return qa_lang_html_sub_split('main/by_x', $whohtml);
	}
	

	function qa_other_to_q_html_fields($question, $userid, $cookieid, $usershtml, $dummy, $options)
/*
	Return array of mostly HTML to be passed to theme layer, to *link* to an answer, comment or edit on
	$question, as retrieved from database, with fields prefixed 'o' for the answer, comment or edit.
	$userid, $cookieid, $usershtml, $options are passed through to qa_post_html_fields().
*/
	{
		$fields=qa_post_html_fields($question, $userid, $cookieid, $usershtml, null, $options);
		
		switch ($question['obasetype']) {
			case 'Q':
				$fields['what']=@$question['oedited'] ? qa_lang_html('main/edited') : null;
				break;
				
			case 'A':
				$fields['what']=@$question['oedited'] ? qa_lang_html('main/answer_edited') : qa_lang_html('main/answered');
				break;
				
			case 'C':
				$fields['what']=@$question['oedited'] ? qa_lang_html('main/comment_edited') : qa_lang_html('main/commented');
				break;
		}
			
		if ($question['obasetype']!='Q')
			$fields['what_url']=$fields['url'].'#'.qa_html(urlencode(qa_anchor($question['obasetype'], $question['opostid'])));

		if (@$options['whenview'])
			$fields['when']=qa_lang_html_sub_split('main/x_ago', qa_html(qa_time_to_string(qa_opt('db_time')-$question['otime'])));
		
		if (@$options['whoview']) {
			$isbyuser=qa_post_is_by_user(array('userid' => $question['ouserid'], 'cookieid' => $question['ocookieid']), $userid, $cookieid);
		
			$fields['who']=qa_who_to_html($isbyuser, $question['ouserid'], $usershtml, @$options['ipview'] ? $question['oip'] : null, false);
	
			if (isset($question['opoints'])) {
				if (@$options['pointsview'])
					$fields['who']['points']=($question['opoints']==1) ? qa_lang_html_sub_split('main/1_point', '1', '1')
						: qa_lang_html_sub_split('main/x_points', qa_html(number_format($question['opoints'])));
						
				if (isset($options['pointstitle']))
					$fields['who']['title']=qa_get_points_title_html($question['opoints'], $options['pointstitle']);
			}

			if (isset($question['olevel']))
				$fields['who']['level']=qa_html(qa_user_level_string($question['olevel']));
		}
		
		unset($fields['flags']);
		if (@$options['flagsview'] && @$post['oflagcount'])
			$fields['flags']=($post['oflagcount']==1) ? qa_lang_html_sub_split('main/1_flag', '1', '1')
				: qa_lang_html_sub_split('main/x_flags', $post['oflagcount']);

		unset($fields['avatar']);
		if ((!QA_FINAL_EXTERNAL_USERS) && (@$options['avatarsize']>0))
			$fields['avatar']=qa_get_user_avatar_html($question['oflags'], $question['oemail'], $question['ohandle'],
				$question['oavatarblobid'], $question['oavatarwidth'], $question['oavatarheight'], $options['avatarsize']);
		
		return $fields;
	}
	
	
	function qa_any_to_q_html_fields($question, $userid, $cookieid, $usershtml, $dummy, $options)
/*
	Based on the elements in $question, return HTML to be passed to theme layer to link
	to the question, or to an associated answer, comment or edit.
*/
	{
		if (isset($question['opostid']))
			$fields=qa_other_to_q_html_fields($question, $userid, $cookieid, $usershtml, null, $options);
		else
			$fields=qa_post_html_fields($question, $userid, $cookieid, $usershtml, null, $options);

		return $fields;
	}
	

	function qa_any_sort_by_date($questions)
/*
	Each element in $questions represents a question and optional associated answer, comment or edit, as retrieved from database.
	Return it sorted by the date appropriate for each element, without removing duplicate references to the same question.
*/
	{
		require_once QA_INCLUDE_DIR.'qa-util-sort.php';
		
		foreach ($questions as $key => $question) // collect information about action referenced by each $question
			$questions[$key]['sort']=-(isset($question['opostid']) ? $question['otime'] : $question['created']);
		
		qa_sort_by($questions, 'sort');
		
		return $questions;
	}
	
	
	function qa_any_sort_and_dedupe($questions)
/*
	Each element in $questions represents a question and optional associated answer, comment or edit, as retrieved from database.
	Return it sorted by the date appropriate for each element, and keep only the first item related to each question.
*/
	{
		require_once QA_INCLUDE_DIR.'qa-util-sort.php';
		
		foreach ($questions as $key => $question) { // collect information about action referenced by each $question
			if (isset($question['opostid'])) {
				$questions[$key]['_time']=$question['otime'];
				$questions[$key]['_type']=$question['obasetype'];
				$questions[$key]['_userid']=$question['ouserid'];
			} else {
				$questions[$key]['_time']=$question['created'];
				$questions[$key]['_type']='Q';
				$questions[$key]['_userid']=$question['userid'];
			}

			$questions[$key]['sort']=-$questions[$key]['_time'];
		}
		
		qa_sort_by($questions, 'sort');
		
		$keepquestions=array(); // now remove duplicate references to same question
		foreach ($questions as $question) { // going in order from most recent to oldest
			$laterquestion=@$keepquestions[$question['postid']];
			
			if ((!isset($laterquestion)) || // keep this reference if there is no more recent one, or...
				(
					(@$laterquestion['oedited']) && // the more recent reference was an edit
					(!@$question['oedited']) && // this is not an edit
					($laterquestion['_type']==$question['_type']) && // the same part (Q/A/C) is referenced here 
					($laterquestion['_userid']==$question['_userid']) && // the same user made the later edit
					(abs($laterquestion['_time']-$question['_time'])<300) // the edit was within 5 minutes of creation
				)
			)
				$keepquestions[$question['postid']]=$question;
		}
				
		return $keepquestions;
	}

	
	function qa_any_get_userids_handles($questions)
/*
	Each element in $questions represents a question and optional associated answer, comment or edit, as retrieved from database.
	Return an array of elements (userid,handle) for the appropriate user for each element.
*/
	{
		$userids_handles=array();
		
		foreach ($questions as $question)
			if (isset($question['opostid']))
				$userids_handles[]=array(
					'userid' => @$question['ouserid'],
					'handle' => @$question['ohandle'],
				);
			
			else
				$userids_handles[]=array(
					'userid' => @$question['userid'],
					'handle' => @$question['handle'],
				);
			
		return $userids_handles;
	}


	function qa_html_convert_urls($html, $newwindow=false)
/*
	Return $html with any URLs converted into links (with nofollow and in a new window if $newwindow)
	URL regular expressions can get crazy: http://internet.ls-la.net/folklore/url-regexpr.html
	So this is something quick and dirty that should do the trick in most cases
*/
	{
		return trim(preg_replace('/([^A-Za-z0-9])((http|https|ftp):\/\/([^\s&<>"\'\.])+\.([^\s&<>"\']|&amp;)+)/i', '\1<A HREF="\2" rel="nofollow"'.($newwindow ? ' target="_blank"' : '').'>\2</A>', ' '.$html.' '));
	}

	
	function qa_url_to_html_link($url, $newwindow=false)
/*
	Return HTML representation of $url (if it appears to be an URL), linked with nofollow and in a new window if $newwindow
*/
	{
		if (is_numeric(strpos($url, '.'))) {
			$linkurl=$url;
			if (!is_numeric(strpos($linkurl, ':/')))
				$linkurl='http://'.$linkurl;
				
			return '<A HREF="'.qa_html($linkurl).'" rel="nofollow"'.($newwindow ? ' target="_blank"' : '').'>'.qa_html($url).'</A>';
		
		} else
			return qa_html($url);
	}

	
	function qa_insert_login_links($htmlmessage, $topage=null, $params=null)
/*
	Return $htmlmessage with ^1...^6 substituted for links to log in or register or confirm email and come back to $topage with $params
*/
	{
		require_once QA_INCLUDE_DIR.'qa-app-users.php';
		
		global $qa_root_url_relative;
		
		$userlinks=qa_get_login_links($qa_root_url_relative, isset($topage) ? qa_path($topage, $params, '') : null);
		
		return strtr(
			$htmlmessage,
			
			array(
				'^1' => empty($userlinks['login']) ? '' : '<A HREF="'.qa_html($userlinks['login']).'">',
				'^2' => empty($userlinks['login']) ? '' : '</A>',
				'^3' => empty($userlinks['register']) ? '' : '<A HREF="'.qa_html($userlinks['register']).'">',
				'^4' => empty($userlinks['register']) ? '' : '</A>',
				'^5' => empty($userlinks['confirm']) ? '' : '<A HREF="'.qa_html($userlinks['confirm']).'">',
				'^6' => empty($userlinks['confirm']) ? '' : '</A>',
			)
		);
	}

	
	function qa_html_page_links($request, $start, $pagesize, $count, $prevnext, $params=array(), $hasmore=false)
/*
	Return structure to pass through to theme layer to show linked page numbers for $request.
	QA uses offset-based paging, i.e. pages are referenced in the URL by a 'start' parameter.
	$start is current offset, there are $pagesize items per page and $count items in total
	(unless $hasmore is true in which case there are at least $count items).
	Show links to $prevnext pages before and after this one and include $params in the URLs.
*/
	{
		$thispage=1+floor($start/$pagesize);
		$lastpage=ceil(min($count, 1+QA_MAX_LIMIT_START)/$pagesize);
		
		if (($thispage>1) || ($lastpage>$thispage)) {
			$links=array('label' => qa_lang_html('main/page_label'), 'items' => array());
			
			$keypages[1]=true;
			
			for ($page=max(2, min($thispage, $lastpage)-$prevnext); $page<=min($thispage+$prevnext, $lastpage); $page++)
				$keypages[$page]=true;
				
			$keypages[$lastpage]=true;
			
			if ($thispage>1)
				$links['items'][]=array(
					'type' => 'prev',
					'label' => qa_lang_html('main/page_prev'),
					'page' => $thispage-1,
					'ellipsis' => false,
				);
				
			foreach (array_keys($keypages) as $page)
				$links['items'][]=array(
					'type' => ($page==$thispage) ? 'this' : 'jump',
					'label' => $page,
					'page' => $page,
					'ellipsis' => (($page<$lastpage) || $hasmore) && (!isset($keypages[$page+1])),
				);
				
			if ($thispage<$lastpage)
				$links['items'][]=array(
					'type' => 'next',
					'label' => qa_lang_html('main/page_next'),
					'page' => $thispage+1,
					'ellipsis' => false,
				);
				
			foreach ($links['items'] as $key => $link)
				if ($link['page']!=$thispage) {
					$params['start']=$pagesize*($link['page']-1);
					$links['items'][$key]['url']=qa_path_html($request, $params);
				}
				
		} else
			$links=null;
		
		return $links;
	}

	
	function qa_html_suggest_qs_tags($usingtags=false, $categoryrequest=null)
/*
	Return HTML that suggests browsing all questions (in the category specified by $categoryrequest, if
	it's not null) and also popular tags if $usingtags is true
*/
	{
		$hascategory=strlen($categoryrequest);
		
		$htmlmessage=$hascategory ? qa_lang_html('main/suggest_category_qs') :
			($usingtags ? qa_lang_html('main/suggest_qs_tags') : qa_lang_html('main/suggest_qs'));
		
		return strtr(
			$htmlmessage,
			
			array(
				'^1' => '<A HREF="'.qa_path_html('questions'.($hascategory ? ('/'.$categoryrequest) : '')).'">',
				'^2' => '</A>',
				'^3' => '<A HREF="'.qa_path_html('tags').'">',
				'^4' => '</A>',
			)
		);
	}

	
	function qa_html_suggest_ask($categoryid=null)
/*
	Return HTML that suggest getting things started by asking a question, in $categoryid if not null
*/
	{
		$htmlmessage=qa_lang_html('main/suggest_ask');
		
		return strtr(
			$htmlmessage,
			
			array(
				'^1' => '<A HREF="'.qa_path_html('ask', strlen($categoryid) ? array('cat' => $categoryid) : null).'">',
				'^2' => '</A>',
			)
		);
	}
	
	
	function qa_category_navigation($categories, $selectedid=null, $pathprefix='', $showqcount=true)
/*
	Return the navigation structure for the category hierarchical menu, with $selectedid selected,
	and links beginning with $pathprefix, and showing question counts if $showqcount
*/
	{
		$parentcategories=array();
		
		foreach ($categories as $category)
			$parentcategories[$category['parentid']][]=$category;
			
		$selecteds=qa_category_path($categories, $selectedid);
			
		return qa_category_navigation_sub($parentcategories, null, $selecteds, $pathprefix, $showqcount);
	}
	
	
	function qa_category_navigation_sub($parentcategories, $parentid, $selecteds, $pathprefix, $showqcount)
/*
	Recursion function used by qa_category_navigation(...) to build hierarchical category menu.
*/
	{
		$navigation=array();
		
		if (!isset($parentid))
			$navigation['all']=array(
				'url' => qa_path_html($pathprefix),
				'label' => qa_lang_html('main/all_categories'),
				'selected' => !count($selecteds),
				'categoryid' => null,
			);
		
		if (isset($parentcategories[$parentid]))
			foreach ($parentcategories[$parentid] as $category)
				$navigation[qa_html($category['tags'])]=array(
					'url' => qa_path_html($pathprefix.$category['tags']),
					'label' => qa_html($category['title']),
					'selected' => isset($selecteds[$category['categoryid']]),
					'note' => $showqcount ? ('('.qa_html(number_format($category['qcount'])).')') : null,
					'subnav' => qa_category_navigation_sub($parentcategories, $category['categoryid'], $selecteds, $pathprefix.$category['tags'].'/', $showqcount),
					'categoryid' => $category['categoryid'],
				);
		
		return $navigation;
	}
	
	
	function qa_users_sub_navigation()
/*
	Return the sub navigation structure for user pages
*/
	{
		global $qa_login_userid;
		
		if ((!QA_FINAL_EXTERNAL_USERS) && isset($qa_login_userid) && (qa_get_logged_in_level()>=QA_USER_LEVEL_MODERATOR)) {
			return array(
				'users$' => array(
					'url' => qa_path_html('users'),
					'label' => qa_lang_html('main/highest_users'),
				),
	
				'users/special' => array(
					'label' => qa_lang('users/special_users'),
					'url' => qa_path_html('users/special'),
				),
	
				'users/blocked' => array(
					'label' => qa_lang('users/blocked_users'),
					'url' => qa_path_html('users/blocked'),
				),
			);
			
		} else
			return null;
	}
	
	
	function qa_custom_page_url($page)
/*
	Return the url for $page retrieved from the database
*/
	{
		global $qa_root_url_relative;
		
		return ($page['flags'] & QA_PAGE_FLAGS_EXTERNAL)
			? (is_numeric(strpos($page['tags'], '://')) ? $page['tags'] : $qa_root_url_relative.$page['tags'])
			: qa_path($page['tags']);
	}
	
	
	function qa_navigation_add_page(&$navigation, $page)
/*
	Add an element to the $navigation array corresponding to $page retrieved from the database
*/
	{
		$navigation[($page['flags'] & QA_PAGE_FLAGS_EXTERNAL) ? ('custom-'.$page['pageid']) : $page['tags']]=array(
			'url' => qa_html(qa_custom_page_url($page)),
			'label' => qa_html($page['title']),
			'opposite' => ($page['nav']=='O'),
			'target' => ($page['flags'] & QA_PAGE_FLAGS_NEW_WINDOW) ? '_blank' : null,
		);
	}


	function qa_match_to_min_score($match)
/*
	Convert an admin option for matching into a threshold for the score given by database search
*/
	{
		return 10-2*$match;
	}

	
	function qa_set_display_rules(&$qa_content, $effects)
/*
	For each [target] => [source] in $effects, set up $qa_content so that the visibility of the DOM element ID
	target is equal to the checked state or boolean-casted value of the DOM element ID source. Each source can
	also combine multiple DOM IDs using JavaScript(=PHP) operators. This is twisted but rather convenient.
*/
	{
		$function='qa_display_rule_'.count(@$qa_content['script_lines']);
		
		$keysourceids=array();
		
		foreach ($effects as $target => $sources)
			if (preg_match_all('/[A-Za-z_][A-Za-z0-9_]*/', $sources, $matches)) // element names must be legal JS variable names
				foreach ($matches[0] as $element)
					$keysourceids[$element]=true;
		
		$funcscript=array("function ".$function."() {"); // build the Javascripts
		$loadscript=array();
		
		foreach ($keysourceids as $key => $dummy) {
			$funcscript[]="\tvar e=document.getElementById(".qa_js($key).");";
			$funcscript[]="\tvar ".$key."=e && (e.checked || (e.options && e.options[e.selectedIndex].value));";
			$loadscript[]="var e=document.getElementById(".qa_js($key).");";
			$loadscript[]="if (e) {";
			$loadscript[]="\t".$key."_oldonclick=e.onclick;";
			$loadscript[]="\te.onclick=function() {";
			$loadscript[]="\t\t".$function."();";
			$loadscript[]="\t\tif (typeof ".$key."_oldonclick=='function')";
			$loadscript[]="\t\t\t".$key."_oldonclick();";
			$loadscript[]="\t}";
			$loadscript[]="}";
		}
			
		foreach ($effects as $target => $sources) {
			$funcscript[]="\tvar e=document.getElementById(".qa_js($target).");";
			$funcscript[]="\tif (e) e.style.display=(".$sources.") ? '' : 'none';";
		}
		
		$funcscript[]="}";
		$loadscript[]=$function."();";
		
		$qa_content['script_lines'][]=$funcscript;
		$qa_content['script_onloads'][]=$loadscript;
	}

	
	function qa_set_up_tag_field(&$qa_content, &$field, $fieldname, $tags, $exampletags, $completetags, $maxtags)
/*
	Set up $qa_content and $field (with HTML name $fieldname) for tag auto-completion, where
	$exampletags are suggestions and $completetags are simply the most popular ones. Show up to $maxtags.
*/
	{
		$template='<A HREF="#" CLASS="qa-tag-link" onClick="return qa_tag_click(this);">^</A>';

		$qa_content['script_rel'][]='qa-content/qa-ask.js?'.QA_VERSION;
		$qa_content['script_var']['qa_tag_template']=$template;
		$qa_content['script_var']['qa_tag_onlycomma']=(int)qa_opt('tag_separator_comma');
		$qa_content['script_var']['qa_tags_examples']=qa_html(implode(',', $exampletags));
		$qa_content['script_var']['qa_tags_complete']=qa_html(implode(',', $completetags));
		$qa_content['script_var']['qa_tags_max']=(int)$maxtags;
		
		$separatorcomma=qa_opt('tag_separator_comma');
		
		$field['label']=qa_lang_html($separatorcomma ? 'question/q_tags_comma_label' : 'question/q_tags_label');
		$field['value']=qa_html(implode($separatorcomma ? ', ' : ' ', $tags));
		$field['tags']='NAME="'.$fieldname.'" ID="tags" AUTOCOMPLETE="off" onKeyUp="qa_tag_hints();" onMouseUp="qa_tag_hints();"';
		
		$sdn=' STYLE="display:none;"';
		
		$field['note']=
			'<SPAN ID="tag_examples_title"'.(count($exampletags) ? '' : $sdn).'>'.qa_lang_html('question/example_tags').'</SPAN>'.
			'<SPAN ID="tag_complete_title"'.$sdn.'>'.qa_lang_html('question/matching_tags').'</SPAN><SPAN ID="tag_hints">';

		foreach ($exampletags as $tag)
			$field['note'].=str_replace('^', qa_html($tag), $template).' ';

		$field['note'].='</SPAN>';
	}
	
	
	function qa_get_tags_field_value($fieldname)
/*
	Get a list of user-entered tags submitted from a field that was created with qa_set_up_tag_field(...)
*/
	{
		require_once QA_INCLUDE_DIR.'qa-util-string.php';
		
		$text=qa_post_text($fieldname);
		
		if (qa_opt('tag_separator_comma'))
			return array_unique(preg_split('/\s*,\s*/', trim(qa_strtolower(strtr($text, '/', ' '))), -1, PREG_SPLIT_NO_EMPTY));
		else
			return array_unique(qa_string_to_words($text, true, false, false, false));
	}
	
	
	function qa_set_up_category_field(&$qa_content, &$field, $fieldname, $navcategories, $categoryid, $allownone, $allownosub, $maxdepth=null, $excludecategoryid=null)
/*
	Set up $qa_content and $field (with HTML name $fieldname) for hierarchical category navigation, with the initial value
	set to $categoryid (and $navcategories retrieved for $categoryid using qa_db_category_nav_selectspec(...)).
	If $allownone is true, it will allow selection of no category. If $allownosub is true, it will allow a category to be
	selected without selecting a subcategory within. Set $maxdepth to the maximum depth of category that can be selected
	(or null for no maximum) and $excludecategoryid to a category that should not be included.
*/
	{
		$pathcategories=qa_category_path($navcategories, $categoryid);

		$startpath='';
		foreach ($pathcategories as $category)
			$startpath.='/'.$category['categoryid'];
		
		if (!isset($maxdepth))
			$maxdepth=QA_CATEGORY_DEPTH;
		$maxdepth=min(QA_CATEGORY_DEPTH, $maxdepth);

		$qa_content['script_rel'][]='qa-content/qa-ask.js?'.QA_VERSION;
		$qa_content['script_onloads'][]='qa_category_select('.qa_js($fieldname).', '.qa_js($startpath).');';
		
		$qa_content['script_var']['qa_cat_exclude']=$excludecategoryid;	
		$qa_content['script_var']['qa_cat_allownone']=(int)$allownone;
		$qa_content['script_var']['qa_cat_allownosub']=(int)$allownosub;
		$qa_content['script_var']['qa_cat_maxdepth']=$maxdepth;

		$field['type']='select';
		$field['tags']='NAME="'.$fieldname.'_0" ID="'.$fieldname.'_0" onChange="qa_category_select('.qa_js($fieldname).');"';
		$field['options']=array();
		
		// create the menu that will be shown if Javascript is disabled
		
		if ($allownone)
			$field['options']['']=qa_lang_html('main/no_category'); // this is also copied to first menu created by Javascript
		
		$keycategoryids=array();
		
		if ($allownosub) {
			$category=@$navcategories[$categoryid];
			$upcategory=$category;

			while (true) { // first get supercategories
				$upcategory=@$navcategories[$upcategory['parentid']];
				
				if (!isset($upcategory))
					break;
				
				$keycategoryids[$upcategory['categoryid']]=true;
			}
			
			$keycategoryids=array_reverse($keycategoryids, true);

			$depth=count($keycategoryids); // number of levels above
			
			if (isset($category)) {
				$depth++; // to count category itself
				
				foreach ($navcategories as $navcategory) // now get siblings and self
					if (!strcmp($navcategory['parentid'], $category['parentid']))
						$keycategoryids[$navcategory['categoryid']]=true;
			}
	
			if ($depth<$maxdepth)
				foreach ($navcategories as $navcategory) // now get children, if not too deep
					if (!strcmp($navcategory['parentid'], $categoryid))
						$keycategoryids[$navcategory['categoryid']]=true;

		} else {
			$haschildren=false;
			
			foreach ($navcategories as $navcategory) // check if it has any children
				if (!strcmp($navcategory['parentid'], $categoryid))
					$haschildren=true;
			
			if (!$haschildren)
				$keycategoryids[$categoryid]=true; // show this category if it has no children
		}
		
		foreach ($keycategoryids as $keycategoryid => $dummy)
			if (strcmp($keycategoryid, $excludecategoryid))
				$field['options'][$keycategoryid]=qa_category_path_html($navcategories, $keycategoryid);
			
		$field['value']=@$field['options'][$categoryid];
		$field['note']='<NOSCRIPT STYLE="color:red;">'.qa_lang_html('question/category_js_note').'</NOSCRIPT> ';
	}
	
	
	function qa_get_category_field_value($fieldname)
/*
	Get the user-entered category id submitted from a field that was created with qa_set_up_category_field(...)
*/
	{
		for ($level=QA_CATEGORY_DEPTH; $level>=1; $level--) {
			$levelid=qa_post_text($fieldname.'_'.$level);
			if (strlen($levelid))
				return $levelid;
		}
		
		if (!isset($levelid)) { // no Javascript-generated menu was present so take original menu
			$levelid=qa_post_text($fieldname.'_0');
			if (strlen($levelid))
				return $levelid;
		}
		
		return null;
	}

	
	function qa_set_up_notify_fields(&$qa_content, &$fields, $basetype, $login_email, $innotify, $inemail, $errors_email)
/*
	Set up $qa_content and add to $fields to allow user to set if they want to be notified regarding their post.
	$basetype is 'Q', 'A' or 'C' for question, answer or comment. $login_email is the email of logged in user,
	or null if this is an anonymous post. $innotify, $inemail and $errors_email are from previous submission/validation.
*/
	{
		$fields['notify']=array(
			'tags' => 'NAME="notify"',
			'type' => 'checkbox',
			'value' => qa_html($innotify),
		);

		switch ($basetype) {
			case 'Q':
				$labelaskemail=qa_lang_html('question/q_notify_email');
				$labelonly=qa_lang_html('question/q_notify_label');
				$labelgotemail=qa_lang_html('question/q_notify_x_label');
				break;
				
			case 'A':
				$labelaskemail=qa_lang_html('question/a_notify_email');
				$labelonly=qa_lang_html('question/a_notify_label');
				$labelgotemail=qa_lang_html('question/a_notify_x_label');
				break;
				
			case 'C':
				$labelaskemail=qa_lang_html('question/c_notify_email');
				$labelonly=qa_lang_html('question/c_notify_label');
				$labelgotemail=qa_lang_html('question/c_notify_x_label');
				break;
		}
			
		if (empty($login_email)) {
			$fields['notify']['label']=
				'<SPAN ID="email_shown">'.$labelaskemail.'</SPAN>'.
				'<SPAN ID="email_hidden" STYLE="display:none;">'.$labelonly.'</SPAN>';
			
			$fields['notify']['tags'].='ID="notify" onclick="if (document.getElementById(\'notify\').checked) document.getElementById(\'email\').focus();"';
			$fields['notify']['tight']=true;
			
			$fields['email']=array(
				'id' => 'email_display',
				'tags' => 'NAME="email" ID="email"',
				'value' => qa_html($inemail),
				'note' => qa_lang_html('question/notify_email_note'),
				'error' => qa_html($errors_email),
			);
			
			qa_set_display_rules($qa_content, array(
				'email_display' => 'notify',
				'email_shown' => 'notify',
				'email_hidden' => '!notify',
			));
		
		} else {
			$fields['notify']['label']=str_replace('^', qa_html($login_email), $labelgotemail);
		}
	}

	
	function qa_load_theme_class($theme, $template, $content, $request)
/*
	Return the initialized class for $theme (or the default if it's gone), passing $template, $content and $request.
	Also applies any registered plugin layers.
*/
	{
		global $qa_root_url_relative, $qa_layers;
		
	//	First load the default class
		
		require_once QA_INCLUDE_DIR.'qa-theme-base.php';
		
		$classname='qa_html_theme_base';
		
	//	Then load the selected theme if valid, otherwise load the default theme
	
		if (!file_exists(QA_THEME_DIR.$theme.'/qa-styles.css'))
			$theme='Default';

		$themeroothtml=qa_html($qa_root_url_relative.'qa-theme/'.$theme.'/');
		
		if (file_exists(QA_THEME_DIR.$theme.'/qa-theme.php')) {
			require_once QA_THEME_DIR.$theme.'/qa-theme.php';
	
			if (class_exists('qa_html_theme'))
				$classname='qa_html_theme';
		}
		
	//	Then load any theme layers using some class-munging magic (substitute class names)
	
		$layerindex=0;
		
		foreach ($qa_layers as $layer) {
			$layerphp=trim(@file_get_contents($layer['directory'].$layer['include']));
			
			if (strlen($layerphp)) {
				$newclassname='qa_html_theme_layer_'.(++$layerindex);
				
				if (preg_match('/\s+class\s+qa_html_theme_layer\s+extends\s+qa_html_theme_base\s+/im', $layerphp)!=1)
					qa_fatal_error('Class for layer must be declared as "class qa_html_theme_layer extends qa_html_theme_base" in '.$layer['directory'].$layer['include']);
				
				$searchwordreplace=array(
					'qa_html_theme_layer' => $newclassname,
					'qa_html_theme_base' => $classname,
					'QA_HTML_THEME_LAYER_DIRECTORY' => "'".$layer['directory']."'",
					'QA_HTML_THEME_LAYER_URLTOROOT' => "'".$qa_root_url_relative.$layer['urltoroot']."'",
				);
				
				foreach ($searchwordreplace as $searchword => $replace)
					if (preg_match_all('/\W('.preg_quote($searchword, '/').')\W/im', $layerphp, $matches, PREG_PATTERN_ORDER|PREG_OFFSET_CAPTURE)) {
						$searchmatches=array_reverse($matches[1]); // don't use preg_replace due to complication of escaping replacement phrase
						
						foreach ($searchmatches as $searchmatch)
							$layerphp=substr_replace($layerphp, $replace, $searchmatch[1], strlen($searchmatch[0]));
					}
				
				// echo '<PRE STYLE="text-align:left;">'.htmlspecialchars($layerphp).'</PRE>'; // to debug munged code
				
				eval('?'.'>'.$layerphp);
				
				$classname=$newclassname;
			}
		}
		
	//	Finally, instantiate the object
			
		$themeclass=new $classname($template, $content, $themeroothtml, $request);
		
		return $themeclass;
	}
	
	
	function qa_load_editor($content, $format, &$editorname)
/*
	Return an instantiation of the appropriate editor module class, given $content in $format
	Pass the preferred module name in $editorname, on return it will contain the name of the module used.
*/
	{
		$maxeditor=qa_load_module('editor', $editorname); // take preferred one first
		
		if (isset($maxeditor) && method_exists($maxeditor, 'calc_quality')) {
			$maxquality=$maxeditor->calc_quality($content, $format);		
			if ($maxquality>=0.5)
				return $maxeditor;

		} else
			$maxquality=0;
		
		$modulenames=qa_list_modules('editor');
		foreach ($modulenames as $tryname) {
			$tryeditor=qa_load_module('editor', $tryname);
			
			if (method_exists($tryeditor, 'calc_quality')) {
				$tryquality=$tryeditor->calc_quality($content, $format);
				
				if ($tryquality>$maxquality) {
					$maxeditor=$tryeditor;
					$maxquality=$tryquality;
					$editorname=$tryname;
				}
			}
		}
				
		return $maxeditor;
	}
	
	
	function qa_load_viewer($content, $format)
/*
	Return an instantiation of the appropriate viewer module class, given $content in $format
*/
	{
		$maxviewer=null;
		$maxquality=0;
		
		$modulenames=qa_list_modules('viewer');
		
		foreach ($modulenames as $tryname) {
			$tryviewer=qa_load_module('viewer', $tryname);
			$tryquality=$tryviewer->calc_quality($content, $format);
			
			if ($tryquality>$maxquality) {
				$maxviewer=$tryviewer;
				$maxquality=$tryquality;
			}
		}
		
		return $maxviewer;
	}
	
	
	function qa_viewer_text($content, $format, $options=array())
/*
	Return the plain text rendering of $content in $format, passing $options to the appropriate module
*/
	{
		$viewer=qa_load_viewer($content, $format);
		return $viewer->get_text($content, $format, $options);
	}
	
	
	function qa_viewer_html($content, $format, $options=array())
/*
	Return the HTML rendering of $content in $format, passing $options to the appropriate module
*/
	{
		$viewer=qa_load_viewer($content, $format);
		return $viewer->get_html($content, $format, $options);
	}
	
	
	function qa_get_post_content($editorfield, $contentfield, &$ineditor, &$incontent, &$informat, &$intext)
/*
	Retrieve the POST from an editor module's HTML field named $contentfield, where the editor's name was in HTML field $editorfield
	Assigns the module's output to $incontent and $informat, editor's name in $ineditor, text rendering of content in $intext
*/
	{
		$ineditor=qa_post_text($editorfield);

		$editor=qa_load_module('editor', $ineditor);
		$readdata=$editor->read_post($contentfield);
		$incontent=$readdata['content'];
		$informat=$readdata['format'];

		$viewer=qa_load_viewer($incontent, $informat);
		$intext=$viewer->get_text($incontent, $informat, array());
	}
	
	
	function qa_get_avatar_blob_html($blobid, $width, $height, $size, $padding=false)
/*
	Return the <IMG...> HTML to display avatar $blobid whose stored size is $width and $height
	Constrain the image to $size (width AND height) and pad it to that size if $padding is true
*/
	{
		require_once QA_INCLUDE_DIR.'qa-util-image.php';
		
		if (strlen($blobid) && ($size>0)) {
			qa_image_constrain($width, $height, $size);
			
			$html='<IMG SRC="'.qa_path('image/'.$blobid, array('s' => $size)).
				'" WIDTH="'.$width.'" HEIGHT="'.$height.'" CLASS="qa-avatar-image"/>';
				
			if ($padding) {
				$padleft=floor(($size-$width)/2);
				$padright=$size-$width-$padleft;
				$padtop=floor(($size-$height)/2);
				$padbottom=$size-$height-$padtop;
				$html='<SPAN STYLE="display:inline-block; padding:'.$padtop.'px '.$padright.'px '.$padbottom.'px '.$padleft.'px;">'.$html.'</SPAN>';
			}
		
			return $html;

		} else
			return null;
	}
	
	
	function qa_get_gravatar_html($email, $size)
/*
	Return the <IMG...> HTML to display the Gravatar for $email, constrained to $size
*/
	{
		if ($size>0)
			return '<IMG SRC="http://www.gravatar.com/avatar/'.md5(strtolower(trim($email))).'?s='.(int)$size.
				'" WIDTH="'.(int)$size.'" HEIGHT="'.(int)$size.'" CLASS="qa-avatar-image"/>';
		else
			return null;
	}
	
	
	function qa_get_points_title_html($userpoints, $pointstitle)
/*
	Retrieve the appropriate user title from $pointstitle for a user with $userpoints points, or null if none
*/
	{
		foreach ($pointstitle as $points => $title)
			if ($userpoints>=$points)
				return $title;
				
		return null;
	}
	

/*
	Omit PHP closing tag to help avoid accidental output
*/