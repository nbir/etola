#!/usr/bin/env php
<?php
// little helper function to print the results
require_once("pos_tagger.php");
$tagger = new PosTagger('lexicon.txt');


//$t="jump over the fence";
$t="china exports cooton to russia";

function printTag($tags) {
        foreach($tags as $t) {
                echo $t['token'] . "-" . $t['tag'] .  "\n";
        }
        echo "\n";
}

$tagger = new PosTagger('lexicon.txt');
$tags = $tagger->tag($t);
printTag($tags);

/*
require_once("../clustering/loadCorpus.php");
$obj_lc=new LoadCorpus();
$corpus=$obj_lc->getAllHeadlines("final");

$fp=fopen("output.".date("ymdHis"), "w");

foreach($corpus as $headline)
{
	$tags = $tagger->tag($headline);
	$string="";
	foreach($tags as $t)
	{
		$string.=strtolower($t['token']) . "/" . trim($t['tag']) .  " ";
	}
	fwrite($fp, $string."\n");
}
*/

?>
