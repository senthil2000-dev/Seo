<?php  
	require_once('../settings.php');
	require_once('Short.php');
	$link= $_POST['link']; 
	$short=new Short($conn);
	echo $short->getShortened($link);
?>