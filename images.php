<link rel="stylesheet" type="text/css" href="assets/css/styles2.css">
<?php
require_once("header.php");
require_once("settings.php");
require_once("includes/Image.php");
require_once("header.php");
require_once("settings.php");
require_once("includes/Stopwords.php");
function getResults($no, $length, $phrase) {
    global $conn;
    $begin=($no - 1)*$length;
    $stmt="SELECT * FROM (";
    $searchArr=makeArr($phrase);
    $searchArr=array_slice($searchArr, 0, 100);
    $stmt.=getSql($searchArr, $begin, $length);
    $perform=$conn->prepare($stmt);
    for($p=0;$p<sizeof($searchArr);$p++) {
        for($q=1;$q<=5;$q++) {
            $k=($p*5+$q);
            $stringVal=$searchArr[$p];
            $perform->bindValue($k, $stringVal);
        }  
    }
    $perform->execute();
    $append = "<div class='allSols'>";
        while($sols = $perform->fetch(PDO::FETCH_ASSOC)) {
            $append.=Image::showImage($sols);
        }
        $append.="</div>";
        return $append;
}

function getSql($sent, $begin, $length) {
    $statement="";
    for($m=1;$m<=sizeof($sent);$m++) {
        $statement.="SELECT *, 'id$m' Relevance FROM pics WHERE INSTR(parentUrl, ?)>0 OR INSTR(picLink, ?)>0
                    OR INSTR(hover, ?)>0 OR INSTR(tit, ?)>0 OR INSTR(ocr, ?)>0";
        if($m!=sizeof($sent)) {
            $statement.=" UNION ";
        }
        else {
            $statement.=") AS temp GROUP by id ORDER BY Relevance,clickthrough DESC LIMIT $begin, $length";
        }
    }
    return $statement;
} 

function makeArr($phrase) {
    $newPhrase=strip_punctuation(removeHtml($phrase));
    $newPhrase=deleteStopwords($newPhrase);
    $newPhrase=stemSentence($newPhrase);
    $stemNewWords=getRequired($newPhrase);
    $searchArr=array($phrase);
    $searchArr=array_merge($searchArr, $stemNewWords);
    $searchArr=array_unique($searchArr);
    $searchArr=array_filter($searchArr, "test");
    $searchArr=array_values($searchArr);
    return $searchArr;
}

function test($var) {
    if($var==="")
        return false;
    else
        return true;
}

if(isset($_GET["searchQuery"]) && $_GET["searchQuery"]!="") {
    $query=$_GET["searchQuery"];
    echo getResults($present, $maxRes, $query);
}
else {
    exit("<div class='novalue'>Search the world wide web</div>");
}

function getOrderedPossibilities($array) {
    $res = [[]];
    foreach ($array as $key => $val) {
        foreach ($res as $possibility)
            $res[] =  $possibility + [$key => $val];
    }
    return array_values(array_filter($res));
}

function sortByNumOfWords($a, $b) {
    $aLength=sizeof($a);
    $bLength=sizeof($b);
    return -($aLength - $bLength);
}

function getRequired($sent) {
    $arr = getOrderedPossibilities(explode(" ", $sent));
    usort($arr, 'sortByNumOfWords');
    $res=array();
    foreach($arr as $val) 
        $res[] = implode(" ", $val);
    return $res;
}
?>
<script>
    function moveTo (link) {
    var xhttp=new XMLHttpRequest();
    xhttp.open("POST", "includes/imageAjax.php", true);
	xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhttp.send("link="+link);
    xhttp.onreadystatechange = function() {
		if (this.readyState == 4 && this.status == 200) {
			console.log(this.responseText);
            }
        }
        window.open(link);
    }
    
</script>