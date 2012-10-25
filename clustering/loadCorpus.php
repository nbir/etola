<?php

class LoadCorpus
{
	/* Arrays to store input and output document id lists */
	private $input_doc_id_list=array();
	private $output_doc_id_list=array();
	
	private $corpus_name="";
	
	
	public function getAllHeadlines($dir)
	{
		$this->corpus_name=$dir;
		/* Returns all news headlines from dir folder in unclustered 
		 * form. */
		
		$corpus=$this->getClusteredHeadlines($dir);
		$corpus_temp=array();
		
		/* Assign a unique id to each document, starting with 1 */
		$id=0;
		
		foreach($corpus as $topic=>$headlines)
		{
			$this->input_doc_id_list[$topic]=array();
			
			foreach($headlines as $headline)
			{
				//array_push($corpus_temp, $headline);
				$id++;
				$corpus_temp[$id]=$headline; /* Assign ID */
				
				/* Store input document ids */
				array_push($this->input_doc_id_list[$topic], $id);
			}
		}
		
		//print_r($this->input_doc_id_list);
		//print($corpus_temp[100]."\n");
		
		return $corpus_temp;
	}
	
	public function getClusteredHeadlines($dir)
	{
		$this->corpus_name=$dir;
		/* Returns all news headlines from dir folder in clustered 
		 * form. */
		
		$this->loadFileNames($dir);
		
		foreach($this->file_names as $file_name)
		{
			$corpus[$file_name] = $this->getHeadlinesFrom($dir, $file_name);
		}
		
		return($corpus);
	}
	
	/* Private variable to store the file names representing the 
	 * class labels. */
	private $file_names=array();
	
	private function loadFileNames($dir)
	{
		/* Loads the file names contained in the directory specified in
		 * dir. */
		$dh=opendir("../_datasets/".$dir."/");
		
		while($new_file_name=readdir($dh))
		{
			if($new_file_name!="." && $new_file_name!="..")
			{
				array_push($this->file_names, $new_file_name);
			}
		}
	}
	
	private function getHeadlinesFrom($dir, $file_name)
	{
		/* Loads the headlines from file file_name in directory dir. */
		
		$headlines=array();
		$fp=fopen("../_datasets/".$dir."/".$file_name, "r");
		
		while(!feof($fp))
		{
			$new_headline=trim(fgets($fp));
			if($new_headline!=null)
			{
				array_push($headlines, $new_headline);
			}
		}
		fclose($fp);
		return $headlines;
	}
	
	/*
	public function writeOutputToFile($clusters)
	{
		if($clusters)
		{
			$fp=fopen("output.".date("ymdHis"), "w");
			
			foreach($clusters as $feature=>$cluster)
			{
				fwrite($fp, $feature."\n");
				foreach($cluster as $headline)
				{
					fwrite($fp, $headline."\n");
				}
				fwrite($fp, "\n\n");
			}
		}
	}
	*/
	
	public function writeDocIdListToFile($clusters, $file_id)
	{
		if($clusters)
		{
			$this->loadOutputDocId($clusters);
			
			$fp=fopen("../_results/result.".$this->corpus_name.".".$file_id, "w");
			//$fp=fopen("result.".$file_id, "w");
			
			$arr_string_in="\$classes".$this->formArrayString($this->input_doc_id_list);
			$arr_string_out="\$clusters".$this->formArrayString($this->output_doc_id_list);
			
			fwrite($fp, $arr_string_in."\n".$arr_string_out);
		}
	}
	
	private function loadOutputDocId($clusters)
	{
		$k=0; /* For cluster id */
		
		foreach($clusters as $feature=>$cluster)
		{				
			$temp_arr_cluster=array();
			
			foreach($cluster as $doc_id=>$headline)
			{
				/* Store output document ids */
				array_push($temp_arr_cluster, $doc_id);
			}
			
			$k++;
			$this->output_doc_id_list[$k]=$temp_arr_cluster;
		}		
	}
	
	private function formArrayString($arr)
	{
		$arr_string="=array(";
		
		foreach($arr as $feature=>$cluster)
		{
			$arr_string.="\"".$feature."\"=>array(";
			
			foreach($cluster as $doc_id)
			{
				$arr_string.=$doc_id.",";
			}
			$arr_string=substr($arr_string, 0, -1);
			$arr_string.="),";
		}
		$arr_string=substr($arr_string, 0, -1);
		$arr_string.=");";
		
		return $arr_string;
	}
}

?>
