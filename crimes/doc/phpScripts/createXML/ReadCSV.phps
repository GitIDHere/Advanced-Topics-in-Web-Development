<?php

	class ReadCSV{
		
		/*
			This file is used as a helper script to read the CSV data blocks within the .csv file.
		*/
		
		private $csvFile;
		
		//Open and create a file object within the constructor.
		public function __construct($fileName){
			$this->csvFile = fopen($fileName, 'rt');
		}
	
		//Keeps reading the next block of data in the CSV until a blank line is encountered, and returns an array of data gathered until that point. 
		public function getNextBlock(){
			$tempArray = array();
			while(($row = fgetcsv($this->csvFile, 5000, ",")) !== false && $row[0] != null){
				$tempArray[] = $row;
			}
			return $tempArray;
		}
	}
?>