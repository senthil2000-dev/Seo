<?php 
require_once("../settings.php");
$link=$_POST["link"];
$stmt=$conn->prepare("UPDATE pics SET clickthrough=clickthrough+1 WHERE picLink=:link");
$stmt->bindParam(':link', $link);
echo $stmt->execute();
?>