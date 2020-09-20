<?php
require_once("porter.php");
$stopwords=array("ourselves", "hers", "between", "yourself", "but", "again",
 "there", "about", "once", "during", "out", "very", "having", 
 "with", "they", "own", "an", "be", "some", "for", "do", "its", 
 "yours", "such", "into", "of", "most", "itself", "other", "off", 
 "is", "s", "am", "or", "who", "as", "from", "him", "each", "the", 
 "themselves", "until", "below", "are", "we", "these", "your", "his", 
 "through", "don", "nor", "me", "were", "her", "more", "himself", "this", 
 "down", "should", "our", "their", "while", "above", "both", "up", "to", 
 "ours", "had", "she", "all", "no", "when", "at", "any", "before", "them", 
 "same", "and", "been", "have", "in", "will", "on", "does", "yourselves", 
 "then", "that", "because", "what", "over", "why", "so", "can", "did", "not", 
 "now", "under", "he", "you", "herself", "has", "just", "where", "too", 
 "only", "myself", "which", "those", "i", "after", "few", "whom", "t", 
 "being", "if", "theirs", "my", "against", "a", "by", "doing", "it", "how", 
 "further", "was", "here", "than");
 function deleteStopwords($sentence) {
     global $stopwords;
    $ret= preg_replace('/\b('.implode('|',$stopwords).')\b/','',$sentence);  
    $ret=preg_replace('!\s+!', ' ', $ret);
    return $ret;
    
 }
 function deleteStopwordsFromArray($arr) {
   $sent=implode(" ", $arr);
   deleteStopwords($sent);
   $sent=explode(" ",$sent);
 }
 function removeHtml($sentence) {
    return strip_tags($sentence);
 }
 
 function stemWord($word) {
    $stem = PorterStemmer::Stem($word);   
    return $stem;
 }
 function stemSentence($sentence) {
    $stems=array();
    $words=explode(" ",$sentence);
    foreach($words as $word) {
        $stems[]=PorterStemmer::Stem($word);
    }
    return implode(" ", $stems);
 }
 function strip_punctuation($string) {
    $string = strtolower($string);
    $string = preg_replace('/[\d\p{P}]+/u', ' ', $string);
    return $string;
}

 ?>