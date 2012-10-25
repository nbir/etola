<?php

/* Directory name containing the folders containing news posts. */
define("DIR_NEWS_POSTS", "DirNewsFiles");

class PrepareDataset
{
	public function buildDataset()
	{
		/* This function collects news headlines from the news posts 
		 * stored in folders representing their labels all inside the 
		 * folder specified by constant DIR_NEWS_POSTS. */
		$this->loadFileNames();
		
		foreach($this->folder_names as $folder_name)
		{		
			$this->collectHeadlinesFrom($folder_name);
		}
	}
	
	/* Private variable to store the folder names representing the 
	 * class labels. */
	private $folder_names=array();
	
	private function loadFileNames()
	{
		/* Loads all folder names in the folder defined by constant 
		 * DIR_NEWS_POSTS to the array file_names */
		$dh=opendir(constant("DIR_NEWS_POSTS"));
		
		while($new_file_name=readdir($dh))
		{
			if($new_file_name!="." && $new_file_name!="..")
			{
				if(is_dir(constant("DIR_NEWS_POSTS")."/".$new_file_name))
				{
					array_push($this->folder_names, $new_file_name);
				}
			}
		}
	}
	
	private function collectHeadlinesFrom($folder_name)
	{
		/* This function extracts news headlines from all the news posts
		 * in the folder names folder_name. It stores them in a file 
		 * named same as the folder name in the _datasets folder. */
		$headlines=array();
		$dh=opendir(constant("DIR_NEWS_POSTS")."/".$folder_name);
		
		while($file_name=readdir($dh))
		{
			if($file_name!="." && $file_name!="..")
			{
				$fp=fopen(constant("DIR_NEWS_POSTS")."/".$folder_name."/".$file_name, "r");

				while(true)
				{
					$new_headline=trim(fgets($fp));
					if($new_headline!=null)
					{
						//$new_headline=strtolower($new_headline);
						array_push($headlines, $new_headline);
						break;
					}
				}

				fclose($fp);
			}
		}
		
		$this->writeToFile($folder_name, $headlines);
	}
	
	private function writeToFile($file_name, $headlines)
	{
		/* Writes all headlines in headlines array into the file 
		 * specified by variable file_name. */

		$dir_name="../_datasets/".date("md")."/";
		if(!file_exists($dir_name))
		{
			mkdir($dir_name);
		}
		
		$fp=fopen($dir_name.$file_name, "w");
		
		foreach($headlines as $headline)
		{
			fwrite($fp, $headline."\n");
		}
	}
}
?>
