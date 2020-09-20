<?php
class Image {
    private $url, $conn;
    public function __construct($url, $conn) {
        $this->url=$url;
        $this->conn=$conn;
    }
    public function process() {
        $parameters=parse_url($this->url);
        if(isset($parameters["query"]))
            $this->url=str_replace("?".$parameters["query"], "", $this->url);
        $ext = pathinfo($this->url, PATHINFO_EXTENSION);
        if(!$ext) {
            $ext="png";
        }
        if(stripos($this->url, "data")===0) {
            $pos = strpos($this->url, 'base64,');
            $img= base64_decode(substr($this->url, $pos + 7));
        }
        else {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
            curl_setopt($ch, CURLOPT_HEADER, 0);
            $img = curl_exec($ch);
            curl_close($ch);
        }
        $path="Tesseract-OCR/tesseract.exe";
        $path=realpath($path);
        file_put_contents('image.'.$ext, $img);
        $imgPath=realpath("image.".$ext);
        clearstatcache();
        exec($path.' "'.$imgPath.'" output');
        $myfile = fopen("output.txt", "r") or die("Unable to open file!");
        $ocr="";
        if(filesize("output.txt"))
            $ocr=fread($myfile, filesize("output.txt"));
        fclose($myfile);
        return array("blobdata"=>$img, "ocr"=>$ocr);
    }
    public function insert($source, $title, $alt, $absoluteUrl) {
        $res=$this->process();
        $blobData=$res["blobdata"];
        $ocr=$res["ocr"];
        $statement = $this->conn->prepare("INSERT INTO pics(parentUrl, picLink, hover, tit, blobdata, ocr)
                                VALUES(:parentUrl, :imageLink, :hover, :tit, :blobdata, :ocr)");
        $statement->bindParam(":parentUrl", $absoluteUrl);
        $statement->bindParam(":imageLink", $source);
        $statement->bindParam(":hover", $alt);
        $statement->bindParam(":tit", $title);
        $statement->bindParam(":blobdata", $blobData);
        $statement->bindParam(":ocr", $ocr);
        $statement->execute();
    }

    public static function showImage($sols) {
            $sourceOfImage=$sols["blobdata"];
            $direct=$sols["parentUrl"];
            $hover=$sols["hover"];
            $tit=$sols["tit"];
            $link=$sols['picLink'];
            $path=parse_url($link)["path"];
            $last=basename($path);
            $ext = pathinfo($path, PATHINFO_EXTENSION);
            if(!$ext) {
                $ext="png";
            }
            if($tit==$hover) {
                $toOut='';
            }
            else {
                $toOut="<span>$hover</span>";
            }
            $last=str_replace(".cms", ".png", $last);
            $sourceOfImage="data:image/$ext;base64,".base64_encode($sourceOfImage); 
            $action="moveTo(\"$link\")";
                return "<div class='searchSol'>
                        <img src='$sourceOfImage'>
                        <div class='side'>
                            <a class='direct' href='$direct'>
                                $toOut
                                <h4>$tit</h4>
                            </a>
                            <div class='desp'>
                                <a onclick='$action' target='__blank'>LOAD IMAGE<a>
                                <a href='$sourceOfImage' download='$last'>DOWNLOAD IMAGE</a>
                            </div>
                        </div>
                    </div>";
    }
}
?>