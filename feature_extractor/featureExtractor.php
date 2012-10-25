<?php
/* This is the 


/* Include the file storing the PorterStemmer class */
require_once("porter_stemming.php");

/* File containing stopwords to be removed from the document, in JSON format */
define("FILE_STOPWORDS", "../feature_extractor/stopwords.default.json");

class FeatureExtractor
{
	public function getFeatures($docment)
	{	
		$docment=$this->changeCase($docment);
		$docment=$this->removeSpecialChar($docment);
		$docment=$this->tokenize($docment);
		
		/* Remove Stopwords & Stemming for cicling-2003, hep-ex and 
		 * KnCr corpus */
		//$docment=$this->removeStopWords($docment);
		//$docment=$this->applyPorterStemming($docment);
		$docment=$this->removeEmptyWords($docment);
		
		return $docment;
	}
	
	/* Private variable to store stopwords */
	private $stopwords=array();
	
	function __construct()
	{
		/* Loads stopwords from the file specifed in the header */
		$this->stopwords=json_decode(file_get_contents(constant("FILE_STOPWORDS")), true);
	}

	private function changeCase($docment)
	{
		/* Change all text in document to lower case */
		return strtolower($docment);
	}
	
	function removeSpecialChar($docment)
	{
		/* Remove any non alphabetic character from the document */
		return preg_replace("/[^a-zA-Z\s]/", "", $docment);
	}

	function tokenize($docment)
	{
		/* Tokenizes the document by splitting at spaces. The document 
		 * is then stored as an array of features. */
		//return preg_split("/[\s]+/", $docment, null, PREG_SPLIT_NO_EMPTY);
		return preg_split("/[\s]+/", $docment);
	}

	function removeStopWords($docment)
	{
		/* Checks for occurence of stopwords in the document array and 
		 * removes them */
		foreach($this->stopwords as $word)
		{
			foreach($docment as $docment_key=>$docment_word)
			{
				if($docment_word == $word)
					unset($docment[$docment_key]);
			}
		}
		
		return array_values($docment);
	}

	function applyPorterStemming($docment)
	{
		/* Applies Porter stemming algorithm to every word in the 
		 * document */
		foreach($docment as $index=>$word)
		{
			$docment[$index] = PorterStemmer::Stem($word);
		}

		return $docment;
	}
	function removeEmptyWords($docment)
	{
		/* Removes any empty words. */
		foreach($docment as $index=>$word)
		{
			if(strlen($word)==0)
			{
				unset($docment[$index]);
			}
		}
		
		return $docment;
	}
}
?>
