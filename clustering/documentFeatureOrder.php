<?php
/* This is the 


/* Include the file storing the FeatureExtractor class */
require_once("../feature_extractor/featureExtractor.php");

class DocumentFeatureOrder
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
		$this->extractFeaturesFromDoc();
		print("feature extract\n");
		$this->createListOfTerms();
		arsort($this->term_list);
		print("list of terms\n");
		
		$this->countFeaturesInDocuments();
		asort($this->features_in_document);
		print("document list\n");
		
		$this->assignDocumentsToClusters();
		print("assign doc\n");
		$this->buildDocumentClusters();
		print("build cluster\n");
		
		//print_r($this->document_set);
		//print_r($this->term_list);
		//print_r($this->features_in_document);
		//print_r($this->unclustered_documents);
		//print_r($this->clustered_documents);
		//print_r($this->clusters);
		
		return $this->clusters;
	}
	
	/*  */
	private $corpus=array();
	private $document_set=array();
	private $term_list=array();
	private $features_in_document=array();
	
	private $clustered_documents=array();
	private $unclustered_documents=array();
	
	private $clusters=array();
	
	function __construct()
	{}

	private function extractFeaturesFromDoc()
	{
		/* Extract features from each document by using an instance of 
		 * the FeatureExtractor class */
		$obj_fe=new FeatureExtractor();
		
		foreach($this->corpus as $doc_id=>$document)
		{
			$this->document_set[$doc_id]=$obj_fe->getFeatures($document);
			
			/* Initially all documents are unclustered. */
			array_push($this->unclustered_documents, $doc_id);
		}
	}
	
	private function createListOfTerms()
	{
		foreach($this->document_set as $document)
		{
			/* Add each term from each document in the corpus to the 
			 * term_list array. Also count the number of occurence of 
			 * each trem in the corpus. */
			foreach($document as $term)
			{
				if(!isset($this->term_list[$term]))
				{
					$this->term_list[$term]=1;
				}
				else
				{
					$this->term_list[$term]++;
				}
			}
		}
	}
	
	private function countFeaturesInDocuments()
	{
		/* Count the number of nouns in each document and store it in 
		 * nouns_in_document array. */
		
		foreach($this->document_set as $doc_id=>$document)
		{
			$this->features_in_document[$doc_id]=count($document);
		}
	}
	
	private function assignDocumentsToClusters()
	{		
		foreach($this->features_in_document as $doc_id=>$count)
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
					
					if($in_c < $in_u)
					{
						$this->formCluster($feature);						
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
		print("FORM CLUSTER\t");
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
		print("UC\t");
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
		print("C\t");
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
