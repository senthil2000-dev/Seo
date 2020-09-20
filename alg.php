<?php
require_once("settings.php");
require_once("includes/Stopwords.php");
require_once './vendor/autoload.php';
use Phpml\Classification\NaiveBayes;
use Phpml\FeatureExtraction\TokenCountVectorizer;
use Phpml\Tokenization\WhitespaceTokenizer;
use Phpml\Tokenization\WordTokenizer;
use Phpml\FeatureExtraction\TfIdfTransformer;
$stmt=$conn->prepare("SELECT * FROM train INNER JOIN websites ON train.link = websites.absolutelink");
$stmt->execute();
$traindata=array();
$labels=array();
while($data=$stmt->fetch(PDO::FETCH_ASSOC)) {
    $query=$data["query"];
    $tit=$data["title_head"];
    $headings=$data["headings"];
    $labels[]=$data["classify"];
    $sent=deleteStopwords($query." ".$tit." ".$headings);
    $traindata[]=stemSentence($sent);
}
$tokenize = new WordTokenizer();
$configured = new TokenCountVectorizer($tokenize);

$configured->fit($traindata);
$resultant = $traindata;
$configured->transform($resultant);
$modifier = new TfIdfTransformer($resultant);
$modifier->transform($resultant);
$model = new NaiveBayes();
$model->train($resultant, $labels);
?>