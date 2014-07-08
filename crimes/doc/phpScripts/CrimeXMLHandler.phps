<?php
	
	class CrimeXMLHandler{
		
		/*********************************************************************************
			This is a helper class which is used to access and search through CrimeXML.xml
		**********************************************************************************/
		
		
		private $xmlFilePath;
		private $dom;
		
		public function __construct($xmlFilePath){
			$this->dom = new DomDocument("1.0", "UTF-8");
			$this->dom->loadXML($proxyFile);
			$this->xmlFilePath = $xmlFilePath;
		}
		
		/* 
		   Initiates an XPath query which searches CrimeXML and retrieves an element. 
		   The data from the element is formatted into an array and returned.
		*/
		public function getXMLElements($xPathQuery){
			$tempArray = array();
			$xpath = new DomXpath($this->dom);
			$data = $xpath->query($xPathQuery);
			for($i = 0; $i < $data->length; $i++){
				$tempArray[] = array($data->item($i)->nodeValue, $data->item($i+1)->nodeValue);
				$i++;
			}
			return $tempArray;
		}		

		public function removeRegionFromEnd(&$regions){
 			foreach($regions as $index => $regionData){
				$regions[$index][0] = substr($regions[$index][0], 0, -7);
			} 
		}
		
		/*
			This function takes in a string and returns an acronym of that string. 
			If the string passed in contains the word 'without', then 'o' is appended to the acronym of that word.
			This was implemented to handle the acroym creation for 'Crimes With Injury' and 'Crimes Without Injury', 
			since they both have the same acronym.
		*/
		public function getAcronym($words){
			$output = "";
			if(str_word_count($words, 0) == 1){
				$output = strtolower(substr($words, 0, 3));
			}else{
				$wordArray = explode(" ", $words);
				foreach ($wordArray as $word) {
					if($word == "without"){
						$output .= strtolower($word[0])."o";
					}else{
						$output .= strtolower($word[0]);
					}
				}
			}
			return $output;
		}
		
		/*	
			Fucntion checks if an element exists within CrimeXML with the same name as the parameter string passed in.
			This function checks for both regions and areas, which is why the parameter is $locationName.
		*/
		public function doesLocationExist($locationName){
			$xpath = new DomXpath($this->dom);
			$data = $xpath->query("//*[@name='$locationName']");
			if($data->length > 0){
				return true;
			}else{
				return false;
			}
		}
		
		//Executes an XPath query passed in within the parameter on CrimeXML.
		public function searchXML($xpathQuery){
			$xpath = new DomXpath($this->dom);
			return $xpath->query($xpathQuery);
		}
		
		//Save the XML
		public function saveXML(){
			$this->dom->save($this->xmlFilePath);
		}
		
	}
?>