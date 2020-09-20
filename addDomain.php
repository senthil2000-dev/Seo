<?php
require_once("header.php");
require_once("includes/Stopwords.php");
require_once("settings.php");
require_once("includes/Image.php");
require_once("size.php");
error_reporting(E_ERROR);
set_time_limit(0);
$doc=new DomDocument();
$stat=0;
$number=0;
$opt = array('https'=>array('method'=>"GET", 'header'=>"User-Agent: webCrawler\n"));
$context = stream_context_create($opt);
function changeStartUrl($url) {
    global $doc, $context;
    $doc = new DomDocument("1.0");
    $html=file_get_contents($url, false, $context);
    @$doc->loadHTML($html);
}
function checkMobileResponsiveness($link, $parent) {
    global $doc, $context;
    $desktop=1;$mobile=0;
    $hostvar= parse_url($link)['host'];
    if(substr($hostvar, 0,4)=='www.') {
        $desktop=checkExists($link);
        $link=str_replace('www.', 'm.', $link);
        $mobile=checkExists($link);
    }
    else if(substr($hostvar, 0, 2)=='m.') {
        $mobile=checkExists($link);
        $link=str_replace('m.', 'www.', $link);
        $desktop=checkExists($link);
    }
    if(($mobile)&&($desktop)) {
        return 1;
    }
    else {
        $links=getTags('link');
        $stylesheet="";
        foreach($links as $link) {
            $href=getAttr($link, 'href');
            $res=absoluteLink($href, parse_url($parent))["abs"];
            if(getAttr($link, 'rel')=='stylesheet')  {
                $href=getAttr($link, 'href');
                $res=absoluteLink($href, parse_url($parent))["abs"];
                if($hostvar==parse_url($res)["host"]) {
                    $stylesheet=$res;
                }
            }
        }
        if($stylesheet) {
            $a=file_get_contents($stylesheet);
            $numMediaQueries=substr_count($a, '@media');
            if($numMediaQueries>=3)
                return 1;
        }
    }
    ini_set('user_agent', 'Mozilla/5.0 (iPhone; U; CPU iPhone OS 4_0 like Mac OS X; en-us) AppleWebKit/532.9 (KHTML, like Gecko) Version/4.0.5 Mobile/8A293 Safari/6531.22.7');
    $html1=file_get_contents($link, false, $context);
    ini_set('user_agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/85.0.4183.102 Safari/537.36');
    $html2=file_get_contents($link, false, $context);
    similar_text(substr($html1,0,2000),substr($html2,0,2000), $percentage);
    if(($percentage)&&$percentage<75) {
        return 1;
    }
    return 0;
}
function checkExists($link) {
    $head = @get_headers($link);
    if($head && strpos($head[0], '200')) { 
        $stat= 1; 
    } 
    else { 
        $stat= 0; 
    } 
    return $stat;
}
function getTags($tagName) {
        global $doc;
        return $doc->getElementsByTagName($tagName);
}
function getAttr($el, $attribute) {
    return $el->getAttribute($attribute);
}

$imagesParsed = array();
$videosParsed = array();


function getCurrentTitle() {
    $titles = getTags("title");
    if(sizeof($titles)==0 || is_null($titles->item(0))) {
        return false;
    }
    $titleString="";
    for($m=0;$m<sizeof($titles);$m++) {
        $titleElement = $titles->item($m)->nodeValue;
        $titleElement = str_replace("\n", " ", $titleElement);
        $titleString.=$titleElement;
        if($m!=sizeof($titles)-1) {
            $titleString.=" , ";
        }
    }
    return $titleString;
}
function getCurrentHeadings() {
    $headings=array();
    $arr=array("h1", "h2", "h3", "h4", "h5");
    foreach($arr as $element) {
        $elementNodes=getTags($element);
        foreach($elementNodes as $node) {
             $headings[]=$node->textContent;
        }
    }
    $headingStr=implode(' , ', $headings);
    return $headingStr;
}
function getCurrentMetaTags() {
    $data = getTags("meta");
    $metaData=array("description"=>"", "keywords"=>"");
    for($k=0; $k<sizeof($data); $k++) {
        $name=getAttr($data[$k], "name");
        switch($name) {
            case "description":
                $metaData[$name]=getAttr($data[$k], "content");
                break;
            case "keywords":
                $metaData[$name]=getAttr($data[$k], "content");
                break;
        }
    }
    return $metaData;
}
function process($absoluteUrl, $protocol, $parent) {
    global $number;
    changeStartUrl($absoluteUrl);
    $titleString=getCurrentTitle();
    if(!$titleString) {
        return false;
    }
    $metaData=getCurrentMetaTags();
    $headings=getCurrentHeadings();
    $resp=checkMobileResponsiveness($absoluteUrl, $parent);
    $size=retSize($absoluteUrl);
    if(insertIntoSites($absoluteUrl, $titleString, $metaData["description"], $metaData["keywords"], $headings, $protocol, $size, $resp)) {
        $number++;
    }
    getAllImages($absoluteUrl);
}

function getAllImages($absoluteUrl) {
    global $conn;
    global $imagesParsed;
    $images =getTags("img");
    foreach($images as $image) {
        $source=getAttr($image, "src");
        $alt=getAttr($image, "alt");
        $title=getAttr($image, "title");
        $source=absoluteLink($source, parse_url($absoluteUrl))["abs"];
        if(!in_array($source, $imagesParsed)) {
            $imagesParsed[] = $source;
            $imageToBeParsed=new Image($source, $conn);
            $imageToBeParsed->insert($source, $title, $alt, $absoluteUrl);
        }
    }
}

function insertIntoDomain($domain) {
    global $conn;
    $statement = $conn->prepare("INSERT INTO domains(domain)
                            VALUES(:domain)");
    $statement->bindParam(":domain", $domain);
    $statement->execute();
}
function start($resource) {
    $queue=array($resource);
    $done=array($resource);
    insertIntoDomain($resource);
    while(sizeof($queue)>0) {
        $resource=$queue[0];
        changeStartUrl($resource);
        $linkList = getTags("a");
        $numChild=0;
        $resource=rtrim($resource, "/\\");
        foreach($linkList as $link) {
            $anchor=getAttr($link, "href");
            $absolute = absoluteLink($anchor, parse_url($resource))["abs"];
            $protocol=absoluteLink($anchor, parse_url($resource))["protocol"];
            $absolute=rtrim($absolute, "/\\");
            if(insertRel($resource, $absolute, $anchor)) {
                $numChild++;
            }
            $validated=validateRelLink($anchor, $absolute, $done);
            if($validated) {
                array_push($done, $absolute);
                array_push($queue, $absolute);
                process($absolute, $protocol, $resource);
            }
            elseif($validated===null) {
                updateheadings($link->textContent, $resource);
            }
        }
        if($numChild!=0)
            insertOutlinks($numChild, $resource);
        elseif(!getOutlinks($resource)) {
            $numChild=0;
            $arr=getParents($resource);
            foreach($arr as $parent) {
                if(insertRel($resource, $parent)) {
                    $numChild++;
                }
            }
            insertOutlinks($numChild, $resource);
        }
        $a = array_shift($queue);
    }
}

function updateheadings($text, $url) {
    global $conn;
    $existing=getHeadingFromDatabase($url);
    if(strpos($existing, $text)!==false) {
        $existing.=" , $text";
    }
    $statement = $conn->prepare("UPDATE websites SET headings=:headings WHERE absolutelink=:link");
    $statement->bindParam(":headings", $existing);
    $statement->bindParam(":link", $url);
    return $statement->execute();
}

function getHeadingFromDatabase($url) {
    global $conn;
    $statement = $conn->prepare("SELECT headings FROM websites WHERE absolutelink = :link");
    $statement->bindParam(":link", $url);
    $statement->execute();
    return $statement->fetchColumn();
}
function getParents($child) {
    global $conn;
    $statement = $conn->prepare("SELECT parent FROM linkedges WHERE child = :child");
    $statement->bindParam(":child", $child);
    $statement->execute();
    $parents = $statement->fetchAll(PDO::FETCH_COLUMN);
    return $parents;
}

function insertOutlinks($num, $url) {
    global $conn;
    $statement = $conn->prepare("UPDATE websites SET outdegree=:num WHERE absolutelink=:abslink");
    $statement->bindParam(":num", $num);
    $statement->bindParam(":abslink", $url);
    $statement->execute();
}

function getOutlinks($url) {
    global $conn;
    $statement = $conn->prepare("SELECT outdegree FROM websites WHERE absolutelink=:abslink");
    $statement->bindParam(":abslink", $url);
    $statement->execute();
    return $statement->fetchColumn();
}

function insertIntoSites($url, $title, $description, $keywords, $headings, $protocol, $size, $resp) {
    global $conn;
    $proto=$protocol=="https"?1:0;
    $tit=formatText($title);
    $desp=formatText($description);
    $keywords=formatText($keywords);
    $headings=formatText($headings);
    $statement = $conn->prepare("SELECT * FROM websites WHERE absolutelink = :link");
    $statement->bindParam(":link", $url);
    $statement->execute();
    if($statement->rowCount() == 0) {
        $statement = $conn->prepare("INSERT INTO websites(absolutelink, title_head, dmeta, keymeta, headings, https, size, despMeta, pageTitle, responsiveness)
                            VALUES(:url, :title, :description, :keywords, :headings, :proto, :size, :desp, :tit, :resp)");
        $statement->bindParam(":url", $url);
        $statement->bindParam(":title", $tit);
        $statement->bindParam(":description", $desp);
        $statement->bindParam(":keywords", $keywords);
        $statement->bindParam(":headings", $headings);
        $statement->bindParam(":proto", $proto);
        $statement->bindParam(":size", $size);
        $statement->bindParam(":desp", $description);
        $statement->bindParam(":tit", $title);
        $statement->bindParam(":resp", $resp);
        $statement->execute();
        return true;
    }
    else 
        return false;
}

function formatText($param) {
    $param=strip_punctuation(removeHtml($param));
    $param=deleteStopwords($param);
    $param=stemSentence($param);
    return $param;
}

function absoluteLink($relLink, $domain) {
    if(dirname($domain["path"])=="\\") {
        $str="";
    }
    else      
        $str=dirname($domain["path"]);
    $protocol = $domain["scheme"];
    $domainName = $domain["host"];
    if(strpos($relLink, "//")===0) {
        $abs = "$protocol:$relLink";
    }
    else if(strpos($relLink, "/")===0) {
        $abs = "$protocol://$domainName$relLink";
    }
    else if(strpos($relLink, "./")===0) {
        $abs =  "$protocol://$domainName".$str.substr($relLink, 1);
    }
    else if(strpos($relLink, "../")===0) {
        $abs =  "$protocol://$domainName/$relLink";
    }
    else if(strpos($relLink, "http") !== 0  && strpos($relLink, "https") !== 0) {
        $abs =  "$protocol://$domainName/$relLink";
    }
    else {
        $abs = $relLink;
    }
    return array("protocol"=>$protocol, "abs"=>$abs);
}

function insertRel($url, $absolute, $anchor="none") {
    global $conn;
    $avoidables=["?", "#", "javascript:"];
    if(hasAvoidables($anchor, $avoidables))
        return false;
    if($url==$absolute) {
        return false;
    }
    $statement = $conn->prepare("SELECT * FROM linkedges WHERE parent = :parent AND child = :child");
    $statement->bindParam(":parent", $url);
    $statement->bindParam(":child", $absolute);
    $statement->execute();
    if($statement->rowCount() == 0) {
        $statement = $conn->prepare("INSERT INTO linkedges(parent, child)
                            VALUES(:parent, :child)");
        $statement->bindParam(":parent", $url);
        $statement->bindParam(":child", $absolute);
        $statement->execute();
        return true;
    }
    else 
        return false;
}

function validateRelLink($anchor, $absolute, $done) {
    $avoidables=["?", "javascript:"];
    $sublinks=['#'];
    if(hasAvoidables($anchor, $avoidables))
        return false;
    else if(hasAvoidables($anchor, $sublinks))
        return null;
    else if(in_array($absolute, $done))
        return false;
    else
        return true;
}
function hasAvoidables($str, $avoidables) {
    foreach($avoidables as $avoidable) {
        if(stripos($str, $avoidable)!==false) {
            return true;
        }
    }
}
if(isset($_GET["searchQuery"])) {
    global $number;
    $domain = $_GET["searchQuery"];
    $domain=rtrim($domain, "/\\");
    $protocol=parse_url($domain)["scheme"];
    process($domain, $protocol, $domain);
    start($domain);
    echo "<h3 style='text-align: center;'>$number urls have been crawled...</h3>";
}
?>
<script>
    document.querySelector("form").addEventListener("submit", function () {
        this.submit();
        document.body.innerHTML+="<div class='spiderCrawl'><img src='assets/images/spider.gif' title='spiderGif'></div>";
    });
</script>
