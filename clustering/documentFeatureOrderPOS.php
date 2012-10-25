<?php
/* This is the 


/* Include the file storing the Parts of Speech tagger class */
require_once("../pos/pos_tagger.php");

require_once("../feature_extractor/porter_stemming.php");

class DocumentFeatureOrderPOS
{
	function cluster($corpus)
	{
		/* Load all documents in input corpus into the document_set 
		 * array assigning an unique id to each */
		//$id=0;
		foreach($corpus as $document)
		{
			$document=preg_replace("/pct/i", "%", $document);
			$document=preg_replace("/mln/i", "million", $document);
			
			//$id++;
			//$this->corpus[$id]=$document;
		}
		
		$this->corpus=$corpus;
		
		/* PREPROCESSING */
		$this->findPOSofheadlines();
		$this->applyPreprocessingToCorpus();
		$this->createListOfTerms();
		arsort($this->term_list);
		
		$this->countNounsInDocuments();
		asort($this->nouns_in_document);
		//$this->formDocumentPresenceMatrix();
		
		$this->selectNoun();
		$this->buildDocumentClusters();
		
		//print_r($this->document_set);
		//print_r($this->term_list);
		//print_r($this->nouns_in_document);
		//print_r($this->unclustered_documents);
		//print_r($this->clustered_documents);
		//print_r($this->clusters);
		
		/*
		foreach($this->document_presence_matrix as $doc_id=>$matrix)
		{
			if(count($matrix)==3)
				print_r($matrix);
		}
		*/
		/*
		print(count($this->term_list)."\n");
		foreach($this->term_list as $term=>$freq)
			print($term." , ");
		print("\n");
		
		foreach($this->document_presence_matrix as $doc_id=>$matrix)
		{
			foreach($this->term_list as $term=>$freq)
			{
				$flag_found=false;
				foreach($matrix as $f=>$ff)
				{
					if($f==$term)
					{
						print(" X ");
						$flag_found=true;
						break;
					}
				}
				
				if(!$flag_found)
				{
					print(" ");
				}
				
				print(",");
			}
			print("\n");
		}
		*/
		/*
		foreach($this->clusters as $feature=>$docs)
		{
			print($feature."\t".count($docs)."\n");
			
			if(count($docs)==1)
			{
				foreach($this->document_set as $doc_id=>$document)
				{
					if($this->isPresentIn($feature, $doc_id))
					{
						foreach($document as $term)
							print($term." ");
						print("\n");
						break;
					}
				}
			}
			
		}
		*/
		
		return $this->clusters;
	}
	
	/*  */
	private $corpus=array();
	private $document_set=array();
	private $term_list=array();
	private $nouns_in_document=array();
	
	private $clustered_documents=array();
	private $unclustered_documents=array();
	
	private $clusters=array();
	
	function __construct()
	{}

	private function findPOSofheadlines()
	{
		/* Seperates each headline at spaces and tags their parts of 
		 * speech. */
		$obj_pos = new PosTagger("../pos/lexicon.txt");
		
		foreach($this->corpus as $doc_id=>$document)
		{
			$tags=$obj_pos->tag($document);
			
			$trimmed_document=array();
			foreach($tags as $id=>$tag)
			{
				//$tags[$id]["token"]=applyPreprocessing($tags[$id]["token"]);
				$tags[$id]["tag"]=trim($tags[$id]["tag"]);
				
				/* Remove non Nouns. */
				if($this->checkPOS($tags[$id]["tag"]))
				{
					array_push($trimmed_document, $tags[$id]["token"]);
				}
			}
			
			$this->document_set[$doc_id]=$trimmed_document;
			
			/* Initially all documents are unclustered. */
			array_push($this->unclustered_documents, $doc_id);
		}
	}
	
	private function checkPOS($tag)
	{
		/* Checks if the tag is as required in the experiment. */
		if($tag=="NN" || $tag=="NNS" || $tag=="NNP" || $tag=="NNPS" || $tag=="VBG" || $tag=="JJ")
		//if($tag=="NN" || $tag=="NNS" || $tag=="NNP" || $tag=="NNPS")
		{
			return true;
		}
		return false;
	}
	
	private function applyPreprocessingToCorpus()
	{
		foreach($this->document_set as $doc_id=>$document)
		{
			foreach($document as $term_id=>$term)
			{
				$this->document_set[$doc_id][$term_id]=$this->applyPreprocessing($this->document_set[$doc_id][$term_id]);
				
				if($this->document_set[$doc_id][$term_id]=="")
				{
					unset($this->document_set[$doc_id][$term_id]);
				}
			}
		}
	}
	
	private function applyPreprocessing($string)
	{
		/* Converts to lower case, removes non alphanumeric characters 
		 * and applies stemming. */
		
		$string=strtolower($string);
		//$string=preg_replace("/pct/", "%", $string);
		$string=preg_replace("/[^a-zA-Z\s]/", "", $string);
		$string=PorterStemmer::Stem($string);
		
		return $string;
	}
	
	private function createListOfTerms()
	{
		foreach($this->document_set as $document)
		{
			/* Add each term from each document in the corpus to the 
			 * term_list array. Also count the number of occurence of 
			 * each trem in the corpus. */
			foreach($document as $feature)
			{
				if(!isset($this->term_list[$feature]))
				{
					$this->term_list[$feature]=1;
				}
				else
				{
					$this->term_list[$feature]++;
				}
			}
		}
	}
	
	private function countNounsInDocuments()
	{
		/* Count the number of nouns in each document and store it in 
		 * nouns_in_document array. */
		
		foreach($this->document_set as $doc_id=>$document)
		{
			$this->nouns_in_document[$doc_id]=count($document);
		}
	}
	
	private function selectNoun()
	{
		foreach($this->nouns_in_document as $doc_id=>$count)
		{
			$arr_term_count=array();
			foreach($this->document_set[$doc_id] as $feature)
			{
				$arr_term_count[$feature]=$this->findOccurenceInUnclustered($feature);
			}
			arsort($arr_term_count);
			
			/* Form a cluster using the feature present most number of 
			 * times in unclustered documents. */
			foreach($arr_term_count as $feature=>$freq)
			{
				if(!isset($this->clusters[$feature]))
				{
					$in_c=$this->findOccurenceInClustered($feature);
					$in_u=$this->findOccurenceInUnclustered($feature);
					//print($ic.", ".$iu."\n");
					if($in_c < $in_u)
					{
						$this->formCluster($feature);
						
						/*
						if($feature=="week")
						{
							print_r($this->document_set[$doc_id]);
						}
						*/
						
						break;
					}
				}
			}
			
			if(is_null($this->unclustered_documents))
			{
				break;
			}
		}
	}
	
	private function formCluster($feature)
	{
		/* Forms a cluster by gathering all documents containing the 
		 * specified feature. */
		
		$this->clusters[$feature]=array();
		
		foreach($this->unclustered_documents as $doc_id)
		{
			if($this->isPresentIn($feature, $doc_id))
			{
				/* Assign cluster. */
				array_push($this->clusters[$feature], $doc_id);
				
				/* Delete from Unclustered document list. */
				foreach($this->unclustered_documents as $key=>$value)
				{
					if($value==$doc_id)
					{
						unset($this->unclustered_documents[$key]);
						break;
					}
				}
				
				/* Add to Clustered document list */
				array_push($this->clustered_documents, $doc_id);
			}
		}
	}
	
	private function findOccurenceInUnclustered($feature)
	{
		/* Returns the number of unclustered documents the feature is
		 * present in. */
		 
		$count=0;
		
		foreach($this->unclustered_documents as $doc_id)
		{
			if($this->isPresentIn($feature, $doc_id))
			{
				$count++;
			}
		}
		
		return $count;
	}
	
	private function findOccurenceInClustered($feature)
	{
		/* Returns the number of clustered documents the feature is
		 * present in. */
		
		$count=0;
		
		foreach($this->clustered_documents as $doc_id)
		{
			if($this->isPresentIn($feature, $doc_id))
			{
				$count++;
			}
		}
		
		return $count;
	}
	
	private function isPresentIn($feature, $doc_id)
	{
		/* Checks if the feature is present in the document specified 
		 * by doc_id. */
		
		foreach($this->document_set[$doc_id] as $term)
		{
			if($term==$feature)
			{
				return true;
			}
		}
		
		return false;
	}

	private function buildDocumentClusters()
	{
		/* Seperates the documents as per the built clusters. */
		
		foreach($this->clusters as $cluster_no=>$cluster)
		{
			$temp_cluster=array();
			
			foreach($cluster as $doc_id)
			{
				//array_push($temp_cluster, $this->corpus[$doc_id]);
				$temp_cluster[$doc_id]=$this->corpus[$doc_id];
			}
			
			$this->clusters[$cluster_no] = $temp_cluster;
		}
	}
}
?>
