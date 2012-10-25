<?php

class CountDatasetSize
{
	public function getDatasetSize($dir)
	{
		$this->loadFileNames($dir);
		
		foreach($this->file_names as $file_name)
		{		
			$count[$file_name]=$this->countHeadlinesIn($dir, $file_name);
		}
		
		arsort($count);
		//print_r($count);
		return($count);
	}
	
	/* Private variable to store the file names representing the 
	 * class labels. */
	private $file_names=array();
	
	private function loadFileNames($dir)
	{
		$dh=opendir("../_datasets/".$dir."/");
		
		while($new_file_name=readdir($dh))
		{
			if($new_file_name!="." && $new_file_name!="..")
			{
				array_push($this->file_names, $new_file_name);
			}
		}
	}
	
	private function countHeadlinesIn($dir, $file_name)
	{
		$count=0;
		$fp=fopen("../_datasets/".$dir."/".$file_name, "r");
		
		while(!feof($fp))
		{
			$new_headline=trim(fgets($fp));
			if($new_headline!=null)
			{
				$count++;
			}
		}
		fclose($fp);
		return $count;
	}
}
?>
