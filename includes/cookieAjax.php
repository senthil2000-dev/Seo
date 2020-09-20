<?php 
require_once("../settings.php");
$link=$_POST["urlAjax"];
$stmt=$conn->prepare("UPDATE websites SET clickthrough=clickthrough+1 WHERE absolutelink=:link");
$stmt->bindParam(':link', $link);
$stmt->execute();
setcookie("urlInto", $_POST["urlAjax"], time() + (36000), "/");
setcookie("time", time(), time() + (36000), "/");
setcookie("query", $_POST["query"], time() + (36000), "/");
echo $_POST["urlAjax"];
?>