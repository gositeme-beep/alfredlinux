<?php
/*
Copyright (c) 2015 Devendra Katariya (bylancer.com)
*/
require_once('includes.php');

// A list of permitted file extensions

$allowed = array('zip');

if(isset($_FILES['upl']) && $_FILES['upl']['error'] == 0){

	$extension = pathinfo($_FILES['upl']['name'], PATHINFO_EXTENSION);

	if(!in_array(strtolower($extension), $allowed)){
		echo '{"status":"error"}';
		exit;
	}

	if(check_allow()) {
        $safeName = basename($_FILES['upl']['name']);
        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $safeName);
        if (move_uploaded_file($_FILES['upl']['tmp_name'], 'uploads/' . $safeName)) {
            echo '{"status":"success"}';
            exit;
        }
    }
}

echo '{"status":"error"}';
exit;