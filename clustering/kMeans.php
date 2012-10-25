<?php
/* This is the 


/* Include the file storing the FeatureExtractor class */
require_once("../feature_extractor/featureExtractor.php");

class kMeans
{
	function cluster($corpus, $k)
	{
		$this->k=$k;
		
		$this->assignDocumentIds($corpus);
		$this->formInitialClusters();
		$this->extractFeaturesFromDoc();
		$this->createListOfTerms();
		//asort($this->term_list);
		$this->createTermDocumentMatrix();
		
		/* BEGIN K-MEANS */
		$iterations=0;
		do
		{
			$iterations++;
			print($iterations.", ");
			
			$this->calculateClusterCentroids();
			$this->calculateDocumentClusterSimilarities();
			$any_change=$this->assignDocumentsToClusters();
			
			if($iterations==100)	// Max allowed iterations
			{
				break;
			}
		} while($any_change);
		/* END K-MEANS */
		
		$this->buildDocumentClusters();
		
		//print_r($this->corpus);
		//print_r($this->document_set);
		//print_r($this->term_list);
		//print_r($this->term_document_matrix);
		//print_r($this->cluster_centroids);
		//print_r($this->document_similarities);
		//print_r($this->document_in_cluster);
		//print_r($this->clusters);
		
		return $this->clusters;
	}
	
	/*  */
	private $k=0;
	private $corpus=array();
	private $document_set=array();
	private $term_list=array();
	private $term_document_matrix=array();
	private $cluster_centroids=array();
	private $document_similarities=array();
	private $document_in_cluster=array();
	private $clusters=array();
	
	function __construct()
	{}
	
	private function assignDocumentIds($corpus)
	{
		/* Load all documents in input corpus into the document_set 
		 * array assigning an unique id to each. */
		//$id=0;
		
		foreach($corpus as $doc_id=>$document)
		{
			//$id++;
			$this->corpus[$doc_id]=$document;
			
			$this->document_in_cluster[$doc_id]=0;
		}
	}

	private function formInitialClusters()
	{
		/* Also assignes cluster ids to all documents in clusters with 
		 * more than one document. */
		$total_no_of_documents=count($this->corpus);
		$random_max=floor($total_no_of_documents/$this->k);
		
		$counter=0;
		$counter_next=rand(1, $random_max);
		$k=0;
		foreach($this->corpus as $doc_id=>$document)
		{
			$counter++;

			if($counter==$counter_next)
			{
				$k++;
				$this->document_in_cluster[$doc_id]=$k;
			 
				$counter_next+=rand(1, $random_max);
			}

			if($k==$this->k)
			{
			 break;
			}
		}
	}

	private function extractFeaturesFromDoc()
	{
		/* Extract features from each document by using an instance of 
		 * the FeatureExtractor class */
		$obj_fe=new FeatureExtractor();
		
		foreach($this->corpus as $doc_id=>$document)
		{
			$this->document_set[$doc_id]=$obj_fe->getFeatures($document);
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
					//$this->term_list[$term]["freq"]=1;
					//$this->term_list[$term]["idf"]=1;
				}
				else
				{
					//$this->term_list[$term]["freq"]++;
					$this->term_list[$term]++;
				}
			}
		}
	}
	
	private function createTermDocumentMatrix()
	{
		/* Creates a Term-Document Matrix by storing the IDF value of 
		 * each term in the intersection of Document ID and Term. */
		foreach($this->document_set as $doc_id=>$document)
		{
			foreach($document as $term)
			{
				$this->term_document_matrix[$doc_id][$term]=1;
			}
		}
	}
	
	private function calculateClusterCentroids()
	{
		/* Calculating new cluster centroids by taking the average 
		 * value of the IDF values of all features belonging to the 
		 * documents in the cluster. */
		for($k=1;$k<=$this->k;$k++)
		{
			$this->cluster_centroids[$k]=null;
			
			$doc_ids_in_cluster=$this->getDocumentIdsInCluster($k);
			$no_of_documents=count($doc_ids_in_cluster);
			foreach($doc_ids_in_cluster as $doc_id)
			{
				foreach($this->term_document_matrix[$doc_id] as $term=>$value)
				{
					if(!isset($this->cluster_centroids[$k][$term]))
					{
						$this->cluster_centroids[$k][$term]=$value;
					}
					else
					{
						$this->cluster_centroids[$k][$term]+=$value;
					}
				}
			}
			foreach($this->cluster_centroids[$k] as $term=>$value)
			{
				$this->cluster_centroids[$k][$term]=$value/$no_of_documents;
			}
		}
	}
	
	private function getDocumentIdsInCluster($cluster_no)
	{
		/* Returns an array of Document IDs belonging to cluster number 
		 * cluster_no. */
		$doc_ids_in_cluster=array();
		
		foreach($this->document_in_cluster as $doc_id=>$k)
		{
			if($cluster_no==$k)
			{
				array_push($doc_ids_in_cluster, $doc_id);
			}
		}
		
		return $doc_ids_in_cluster;
	}
	
	private function calculateDocumentClusterSimilarities()
	{
		/* Calculates the similarity of each document in the corpus 
		 * with all k cluster centroids. */
		 foreach($this->term_document_matrix as $doc_id=>$document)
		 {
			 for($k=1;$k<=$this->k;$k++)
			 {
				 $this->document_similarities[$doc_id][$k]=$this->findSimilarity($document, $this->cluster_centroids[$k]);
			 }
		 }
	}
	
	private function findSimilarity($document1, $document2)
	{
		/* Find the similarity of the two documents by multiplying the 
		 * IDF of all common terms */
		$similarity=0;
		
		foreach($document1 as $term=>$value)
		{
			if(isset($document2[$term]))
			{
				$similarity+=$value*$document2[$term];
			}
		}
		$similarity/=($this->findEuclidianNorm($document1)*$this->findEuclidianNorm($document2));
		
		return $similarity;
	}
	
	private function findEuclidianNorm($vector)
	{
		/* Find the Euclidian Norm of a vector, given as the square 
		 * root of the sum of squared values of all elements in the 
		 * vector. */
		$square_sum=0;
		
		foreach($vector as $element=>$value)
		{
			$square_sum+=$value*$value;
		}
		
		return sqrt($square_sum);
	}
	
	private function assignDocumentsToClusters()
	{
		/* Assigns documents to a cluster having the highest similarity 
		 * value. Also checks if there is any change in the clusters */
		$flag_change=false;
		
		foreach($this->document_similarities as $doc_id=>$similarities)
		{
			arsort($similarities);
			
			foreach($similarities as $k=>$value)
			{
				$old_k=$this->document_in_cluster[$doc_id];
					
				if(!$flag_change && $old_k!=$k)
				{
					$flag_change=true;
				}
				$this->document_in_cluster[$doc_id]=$k;
				
				break;
			}
		}
		
		/* Returns TRUE is there is any change in a cluster, FALSE 
		 * otherwise */
		return $flag_change;
	}
	
	private function buildDocumentClusters()
	{
		/* Seperates the documents as per the built clusters and assign 
		 * the most common feature in the cluster as its descriptor. */
		
		for($k=1;$k<=$this->k;$k++)
		{
			$feature_list=array();
			$doc_ids_in_cluster=$this->getDocumentIdsInCluster($k);
			$temp_cluster=array();
			
			foreach($doc_ids_in_cluster as $doc_id)
			{
				/* Add document to cluster. */
				//array_push($temp_cluster, $this->corpus[$doc_id]);
				$temp_cluster[$doc_id]=$this->corpus[$doc_id];
				
				/* Create feature list. */
				foreach($this->term_document_matrix[$doc_id] as $term=>$value)
				{
					//print($term.", ");
					if(!isset($feature_list[$term]))
					{
						$feature_list[$term]=1;
					}
					else
					{
						$feature_list[$term]++;
					}
				}
			}
			
			//print_r($feature_list);
			
			/* Find most frequent feature. */
			arsort($feature_list);
			foreach($feature_list as $feature=>$count)
			{
				$cluster_descriptor=$feature;
				break;
			}
			//print($cluster_descriptor."\n");
			
			/* Assign cluster to final list of clusters. */
			$this->clusters[$cluster_descriptor]=$temp_cluster;
		}
	}
}
?>
