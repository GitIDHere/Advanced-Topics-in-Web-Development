<?php
	
	require_once("UWEProxy.php");
	
	class CrimeXMLHandler{
		
		private $xmlFilePath;
		private $dom;
		
		public function __construct($xmlFilePath){
			$this->dom = new DomDocument("1.0", "UTF-8");
			
			//Get the file content from the UWE servers.
			$proxyFile = UWEProxy::getFileContent($xmlFilePath);
			$this->dom->loadXML($proxyFile);
			
			$this->xmlFilePath = $xmlFilePath;
		}
		
		/* 
		   Initiates an XPath query which searches CrimeXML and retrieves an element data. 
		   The data is formatted into an array and returned.
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
		   A string is passed into this function which creates an acronym representation of the string passed in.
		   If a word within the string passed into this function contains the word 'without', then 'o' is appended
		   to the acronym of that word. This was implemented to handle the acroym creation for 'Crimes With Injury' 
		   and 'Crimes Without Injury', since they both have the same acronym.
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
		
		public function saveXML(){
			$this->dom->save($this->xmlFilePath);
		}
		
	}
?>