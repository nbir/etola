<?php

class Evaluation
{
	private $file_id="";			/* File being evaluated */
	
	private $doc_count=0;			/* Total number of documents */
	
	private $list_class=array();	/* List of classes and clusters */
	private $list_cluster=array();
	private $count_class=array();	/* Number of documents in each class and cluster */
	private $count_cluster=array();
	private $count_class_cluster=array();	/* Number of documents of each class in each cluster */
	private $assigned_class_cluster=array();	/* List of clusters and corresponding class. Class with the maximum common documents in assigned to the cluster. */
	private $assigned_cluster_class=array();
	
	public $purity=0;				/* Purity measure of the clustering */
	public $entropy=0;				/* Entropy measure of the clustering */
	public $fmeasure=0;				/* F measure of the clustering */
	
	function __construct($file_id)
	{
		$this->file_id=$file_id;
		
		/* Read the result fole for the corresponding algorithm. */
		require_once("../_results/result.".$file_id);
		
		foreach($classes as $class_id=>$class)
		{
			/* Add cluster name to list */
			array_push($this->list_class, $class_id);
			
			/* Store cluster size in array */
			$this->count_class[$class_id]=count($class);
			
			/* Increase total number of documents */
			$this->doc_count+=count($class);
		}
		
		foreach($clusters as $cluster_id=>$cluster)
		{
			/* Add cluster name to list */
			array_push($this->list_cluster, $cluster_id);
			
			/* Store cluster size in array */
			$this->count_cluster[$cluster_id]=count($cluster);
		}
		
		/* Store the number of documents of each class in each cluster. Intersection. */
		foreach($classes as $class_id=>$class)
		{
			foreach($clusters as $cluster_id=>$cluster)
			{
				$this->count_class_cluster[$class_id][$cluster_id]=count(array_intersect($class, $cluster));
			}
			
			/* Sort clusters according to the number of common documents. */
			arsort($this->count_class_cluster[$class_id]);
			
			/* Assign class with the maximum common documents to the cluster. */
			foreach($this->count_class_cluster[$class_id] as $id=>$count)
			{
				$this->assigned_class_cluster[$class_id]=-1;
				
				if($count!=0)
				{
					$this->assigned_class_cluster[$class_id]=$id;
					$this->assigned_cluster_class[$id]=$class_id;
				}
				
				break;
			}
		}
		
		//print_r($this->count_class_cluster);
		//print_r($this->assigned_class_cluster);
		//print($this->doc_count);
	}
	
	public function evaluate()
	{
		$this->calcPurity();
		$this->calcEntropy();
		$this->calcFMeasure();
		
		//$this->printResults();
		$this->printResultsLine();
	}
	
	private function printResults()
	{
		print("-----\n".$this->file_id."\n-----\n");
		print("PURITY:\t\t".round($this->purity,2)."\n");
		print("ENTROPY:\t".round($this->entropy,2)."\n");
		print("F-MEASURE:\t".round($this->fmeasure,2)."\n");
	}
	
	private function printResultsLine()
	{
		print($this->file_id."\t");
		
		if(strlen($this->file_id) < 16 )
		{
			print("\t");
			
			if(strlen($this->file_id) < 8 )
			{
				print("\t");
			}
		}
		print(round($this->purity,2)."\t".round($this->entropy,2)."\t".round($this->fmeasure,2)."\n");
	}
	
	private function calcPurity()
	{
		foreach($this->assigned_class_cluster as $class_id=>$cluster_id)
		{
			if($cluster_id != -1)		// For classes with no cluster assigned
			{
				$this->purity += $this->count_class_cluster[$class_id][$cluster_id];
			}
		}
		
		$this->purity /= $this->doc_count;	/* Calculate weighted average */
	}
	
	private function calcEntropy()
	{
		/* Claculate entropy for each cluster */
		$entropy_cluster=array();
		foreach($this->list_cluster as $cluster_id)
		{
			$entropy_cluster[$cluster_id]=0;
			
			foreach($this->list_class as $class_id)
			{
				$p_i_j = $this->count_class_cluster[$class_id][$cluster_id] / $this->count_cluster[$cluster_id];
				
				if($p_i_j != 0)		/* Since log(0) would give and error. */
				{
					$entropy_cluster[$cluster_id] += ($p_i_j*log($p_i_j));
				}
			}
			$entropy_cluster[$cluster_id] *= (-1);	/* Since negative sum */
		}
		
		/* Claculate entropy of the clustering */
		foreach($entropy_cluster as $cluster_id=>$entropy_value)
		{
			$this->entropy += (($this->count_cluster[$cluster_id] * $entropy_value) / $this->doc_count);	/* Calculate weighted average directly */
			//$this->entropy += ($this->count_cluster[$cluster_id] * $entropy_value);
		}
		//'$this->entropy /= $this->doc_count;	/* Calculate weighted average */
	}
	
	private function calcFMeasure()
	{
		foreach($this->list_class as $class_id)
		{
			$f_measures=array();
			
			foreach($this->list_cluster as $cluster_id)
			{	/* Calculate F measure for all clusters corresponding to each class. */
			
				$recall=$this->count_class_cluster[$class_id][$cluster_id] / $this->count_class[$class_id];
				$precision=$this->count_class_cluster[$class_id][$cluster_id] / $this->count_cluster[$cluster_id];
				
				if(($recall+$precision) != 0)
				{
					array_push($f_measures, ((2*$recall*$precision) / ($recall+$precision)));
				}
			}
			
			/* Add f measure each class */
			if(count($f_measures) != 0)
			{
				$this->fmeasure += (($this->count_class[$class_id] / $this->doc_count) * max($f_measures));
			}
		}
	}
}
?>
