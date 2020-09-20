<?php
require_once("settings.php");
require_once("header.php");
$stmt=$conn->prepare("SELECT count(*) FROM websites");
$stmt->execute();
$num=$stmt->fetchColumn();
$frac=1/$num;
$stmt=$conn->prepare("UPDATE websites SET pageRank=:rankVal");
$stmt->bindParam(":rankVal", $frac);
$stmt->execute();
for($m=0;$m<2; $m++) {
    $stmt=$conn->prepare("UPDATE websites s SET s.pageRank = (SELECT SUM(pageRank/outdegree) FROM websites p INNER JOIN linkedges le ON le.parent = p.absolutelink WHERE child = s.absolutelink)");
    $stmt->execute();
}
echo "<h3 style='text-align: center;'>Indexing has been completed....</h3>";
?>