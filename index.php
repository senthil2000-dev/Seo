<?php
require_once("header.php");
require_once("settings.php");
require_once("includes/Stopwords.php");
require_once("includes/Short.php");
require_once("alg.php");
error_reporting(E_ERROR);
$domainfound=0;
if(isset($_GET['id'])){
    $id  = $_GET['id'];
    $short=new Short($conn);
    $url = $short->dest($id);
    header("Location: $url");
}
if(isset($_COOKIE["urlInto"]) && isset($_GET["searchQuery"])&& $_COOKIE["query"]===$_GET["searchQuery"]) {
    $link= $_COOKIE["urlInto"];
    $now=time();
    $dwell= $now-$_COOKIE["time"];
    $query=$_GET["searchQuery"];
    setcookie("urlInto", "", time() - (36000), "/");
    setcookie("time", time(), time() - (36000), "/");
    setcookie("query", time(), time() - (36000), "/");
    if($dwell<30)
        $classify=0;
    elseif($dwell<60)
        $classify=1;
    elseif($dwell<100)
        $classify=2;
    elseif($dwell<150)
        $classify=3;
    elseif($dwell>310)
        $classify=4;
    $statement = $conn->prepare("INSERT INTO train(query, link, dwelltime, classify)
        VALUES(:query, :link, :dwelltime, :classify)");
    $statement->bindParam(":query", $query);
    $statement->bindParam(":link", $link);
    $statement->bindParam(":dwelltime", $dwell);
    $statement->bindParam(":classify", $classify);
    $statement->execute();
}
function getResults($no, $length, $phrase) {
    global $configured, $modifier, $model;
    global $conn, $domainfound;
    $start=($no - 1)*$length;
    $searchArr=makeArr($phrase);
    $domainresults=array();
    if($no==1) {
        $stmt=$conn->prepare("SELECT * FROM domains INNER JOIN websites on domains.domain=websites.absolutelink WHERE INSTR(domain, :phrase)>0 OR INSTR(title_head, :phrase)>0 
        OR INSTR(keymeta, :phrase)>0 OR INSTR(dmeta, :phrase)>0 OR INSTR(headings, :phrase)>0");
        $stmt->bindParam(":phrase", $phrase);
        $stmt->execute();
        $domainfound=$stmt->rowCount();
        
        while($ans=$stmt->fetch(PDO::FETCH_ASSOC)) {
            $domainresults[]=$ans;
        }
    }
    $stmt="SELECT * FROM (";
    $stmt.=getSql($searchArr, "'".implode("','", array_column($domainresults, "absolutelink"))."'", $start, $length);
    $perform=$conn->prepare($stmt);
    for($p=0;$p<sizeof($searchArr);$p++) {
        for($q=1;$q<=5;$q++) {
            $k=($p*5+$q);
            $stringVal=$searchArr[$p];
            $perform->bindValue($p*5+$q, $stringVal);
        }  
    }
    $perform->execute();
    $total=$perform->rowCount();
    $html = "<div class='allSols'>";
    $arr=array();
    $testSet=array();
    $output=array();
        while($sols=$perform->fetch(PDO::FETCH_ASSOC)) {
            $query=$_GET["searchQuery"];
            $pageTitle = $sols["pageTitle"];
            $headings=$sols["headings"];
            $testSet[]="$query $pageTitle $headings";
            $arr[]=$sols;
        }
            if($total>0) {
                $configured->transform($testSet);
                $modifier->transform($testSet);
                $result = $model->predict($testSet);
                // var_dump($result);
                $uniq=array_count_values($result);
                ksort($uniq);
                foreach($uniq as $k=>$v) {
                    $valArr=array_keys($result, $k);
                    $curr=array_values(array_intersect_key($arr, array_flip($valArr)));
                    $output=array_merge($curr, $output);
                } 
            }
            $output=array_merge($domainresults, $output);
            if(sizeof($output)>$length) {
                var_dump($output);
                $output=array_slice($output, 0, $length);
            }
            foreach($output as $sols) {
                $id = $sols["id"];
                $abs = $sols["absolutelink"];
                $pageTitle = $sols["pageTitle"];
                $desp = $sols["despmeta"];
                $query=$_GET["searchQuery"];
                $action="moveTo(\"$abs\", \"$query\")";
                $html.="<div class='searchSol'>
                            <a class='direct' onclick='$action' id='$id'>
                                <span>$abs</span>
                                <h4 class='header'>$pageTitle</h3>
                            </a>
                            <div class='desp'>$desp</div>
                            <span onclick='shortenOut(\"$abs\", this)' class='short'>Get shortened url</span>
                        </div>";
            }
        $html.="</div>";
        return $html;
}

function getSql($sent, $inArr, $start, $length) {
    global $domainfound;
    $limit=$length-$domainfound;
    $statement="";
    for($m=1;$m<=sizeof($sent);$m++) {
        $statement.="SELECT *, 'id$m' Relevance FROM websites WHERE (INSTR(title_head, ?)>0 OR INSTR(absolutelink, ?)>0 
        OR INSTR(keymeta, ?)>0 OR INSTR(dmeta, ?)>0 OR INSTR(headings, ?)>0) AND (absolutelink NOT IN ($inArr))";
        if($m!=sizeof($sent)) {
            $statement.=" UNION ";
        }
        else {
            $statement.=") AS temp GROUP by id ORDER BY Relevance, https DESC, clickthrough DESC, pageRank DESC, responsiveness DESC, size ASC LIMIT $start, $limit";
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
function moveTo (url,query) {
    var xhttp=new XMLHttpRequest();
    xhttp.open("POST", "includes/cookieAjax.php", true);
	xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhttp.send("urlAjax="+url+"&query="+query);
    xhttp.onreadystatechange = function() {
		if (this.readyState == 4 && this.status == 200) {
			console.log(this.responseText);
            window.location.href=url;
        }
    }
}
if (window.performance && window.performance.navigation.type === window.performance.navigation.TYPE_BACK_FORWARD) {
    location.reload();
}
function shortenOut(url, toChange) {
	$.post("includes/process.php", {link: url}).done(function(data) {
		out = location.origin+location.pathname+ '?id=' + data;
		toChange.innerHTML='<b>Shortened url: </b><a href="' + out + '" target="_blank">myweb/' + data + '</a>';
	});
}
</script>