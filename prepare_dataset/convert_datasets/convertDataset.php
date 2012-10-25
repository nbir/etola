<?php

class ConvertDataset
{	
	private $documents=array();	/* List of documents ID => DOC*/
	private $classes=array();	/* List of classes and corresponding 
									document IDs */
	
	function __construct()
	{}
	
	public function convertType1($data_file, $class_file)
	{
		/* $data_file contains all documents in corpus. Each document 
		 * occupies only one line. The first word of each document is 
		 * the document ID.
		 * $class_file contains the classes and the documents in each. 
		 * Each class in represented in a single line. All document Ids 
		 * in that class is space seperated.
		 */
		 
		 /* Place $data_file and $class_file in the _convert folder.
		  * Output will be generated in the _dataset folder. Output 
		  * will be in the form - class name as file name, all 
		  * documents in class will be in the document.
		  */
		
		/* File pointer to data file */
		$fp=fopen("_convert/".$data_file, "r");
		
		/* Load corpus */
		while(!feof($fp))
		{
			$new_doc=trim(fgets($fp));
			
			/* Extract first word */
			$new_doc = explode (' ', $new_doc, 2);
			$key = $new_doc[0];
			if(isset($new_doc[1]))
			{
				$new_doc = $new_doc[1];
			
				/* Add new document */
				$this->documents[$key]=$new_doc;
			}
		}
		
		/* Verification print of all keys */
		foreach($this->documents as $key=>$doc)
		{
			print($key.",  ");
		}
		
		/* File pointer to class specification file */
		$fp=fopen("_convert/".$class_file, "r");
		
		/* Load document IDs in classes */
		$class_id=0;
		while(!feof($fp))
		{
			$new_doc=trim(fgets($fp));
			
			if($new_doc)
			{
				/* Extract all document IDs in class */
				$new_doc = explode (' ', $new_doc);
				
				/* Add document IDs to class */
				$class_id++;
				$this->classes[$class_id]=$new_doc;
			}
		}
		
		/* Write documents to seperate files */
		foreach($this->classes as $class_id=>$doc_list)
		{
			/* Create file with name as class ID */
			$fp=fopen("_dataset/".$class_id, "w");
			
			/* Write documents to file */
			foreach($doc_list as $doc_id)
			{
				if(isset($this->documents[$doc_id]))
				{
					fwrite($fp, $this->documents[$doc_id]."\n");
				}
			}
		}
	}
}
?>
