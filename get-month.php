<?php
	require('../../../wp-blog-header.php');
	require 'jcalendar.php';
	$cal->display(urldecode($_REQUEST['c']));