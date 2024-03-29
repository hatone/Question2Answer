<?php

/*
	Question2Answer 1.4.1 (c) 2011, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-image.php
	Version: 1.4.1
	Date: 2011-07-10 06:58:57 GMT
	Description: Outputs image for a specific blob at a specific size, caching as appropriate


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

//	Ensure no PHP errors are shown in the image data

	@ini_set('display_errors', 0);

	require_once QA_INCLUDE_DIR.'qa-db-cache.php';
	
	function qa_blob_image_db_fail_handler()
	{
		exit;
	}
	
	$blobid=@$qa_request_lc_parts[1];
	$size=(int)qa_get('s');
	$cachetype='i_'.$size;
	
	qa_base_db_connect('qa_blob_image_db_fail_handler');
	
	$content=qa_db_cache_get($cachetype, $blobid); // see if we've cached the scaled down version
	
	header('Cache-Control: max-age=2592000, public'); // allows browsers and proxies to cache images too
	
	if (isset($content)) {
		header('Content-Type: image/jpeg');
		echo $content;

	} else {
		require_once QA_INCLUDE_DIR.'qa-app-options.php';
		require_once QA_INCLUDE_DIR.'qa-db-blobs.php';
		require_once QA_INCLUDE_DIR.'qa-util-image.php';
		
		$blob=qa_db_blob_read($blobid);
		
		if (isset($blob)) {
			if ($size>0)
				$content=qa_image_constrain_data($blob['content'], $width, $height, $size);
			else
				$content=$blob['content'];
			
			if (isset($content)) {
				header('Content-Type: image/jpeg');
				echo $content;

				if (strlen($content) && ($size>0)) {
					$cachesizes=qa_get_options(array('avatar_profile_size', 'avatar_users_size', 'avatar_q_page_q_size', 'avatar_q_page_a_size', 'avatar_q_page_c_size', 'avatar_q_list_size'));
						// to prevent cache being filled with inappropriate sizes
						
					if (array_search($size, $cachesizes))
						qa_db_cache_set($cachetype, $blobid, $content);
				}
			}	
		}
	}

	qa_base_db_disconnect();


/*
	Omit PHP closing tag to help avoid accidental output
*/