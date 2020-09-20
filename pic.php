<?php
$length=7;
$arr=array();
$lengtharr=array();
$heightarr=array();
$pat="";
for($m=0;$m<$length;$m++) {
    array_push($arr, createSeparateIm());
}
$h = max($heightarr);
$w= array_sum($lengtharr);
$output = imagecreate($w,$h);
for($m=0;$m<$length;$m++) {
    if($m==0)
        $start=0;
    else  {
        $req=array_slice($lengtharr, 0, $m);
        $start=array_sum($req);
    }
    imagecopymerge($output, $arr[$m], $start, 0, 0, 0, $lengtharr[$m], $heightarr[$m], 100);
    setcookie("answer", $pat, 0, "/");
}
header( "Content-type: image/png" );
imagepng($output);
function createSeparateIm() {
    global $lengtharr, $heightarr, $pat;
    $alphanumArr = implode("",array_merge(range('A','Z'), range('a','z'), range(0,9)));
    $strlength = strlen($alphanumArr);
    $alphanum = $alphanumArr[rand(0, $strlength-1)];
    $pat.=$alphanum;
    $small = imagecreate( 20, 20 );
    $background = imagecolorallocate($small, 255,255,255);
    $color = imagecolorallocate($small, rand(0,255), rand(0,255), rand(0,255));
    imagestring($small, 5, 1, 0, $alphanum, $color);
    $angle=rand(-90, 90);
    $size=rand(1, 7);
    $large = imagecreate(25+5*$size, 37+7.5*$size);
    imagecopyresampled($large, $small, 0, 0, 0, 0, 25+5*$size, 37+7.5*$size,10,15);
    $large = imagerotate($large, $angle, $background);
    $imagewid = imagesx($large);
    $imageHeig = imagesy($large);
    array_push($lengtharr, $imagewid);
    array_push($heightarr, $imageHeig);
    return $large;
}
createSeparateIm();
?>