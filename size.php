<?php
function retSize($absLink) {
    $handle = curl_init($absLink);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);
    $content = curl_exec($handle);
    $total=getIndividualSize($absLink);
    $sources=regexApply($content)['found1'];
    $linkTags=regexApply($content)['found2'];
    $subs = array_merge($sources, $linkTags);
    $subs=array_values(array_unique($subs));
    $done = array();
    for($k=0;$k<sizeof($subs);$k++) {
        if(!in_array($subs[$k], $done)) {
            $subs[$k]=absoluteLink($subs[$k], parse_url($absLink))["abs"];
            array_push($done, $subs[$k]);
            $total+=getIndividualSize($subs[$k]);
        } 
    }
    return $total;
}
function getIndividualSize($link) {
    $handle = curl_init($link);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);
    $content = curl_exec($handle);
    return curl_getinfo($handle, CURLINFO_SIZE_DOWNLOAD);
}
function regexApply($content) {
    preg_match_all('/(?:src=)"([^"]*)"/m', $content, $found1);
    preg_match_all('/link.*\s*(?:href=)"([^"]*)"/m', $content, $found2);
    return array('found1'=>$found1[1], 'found2'=>$found2[1]);
}
?>