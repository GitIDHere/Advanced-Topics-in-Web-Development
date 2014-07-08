<?php

	class XMLHandler{
		
		/*
			This class is a helper class which is used to create the CrimeXML.xml file.
		*/
		
		private $dom;
		private $crimeCategories;
		private $victimCrimeTypes;
		private $otherCrimeTypes;
		
		private $counter;
		
		//The constructor instantiates a DomDocument object and creates a root element for the XML which is to be created.
		//The constructor also stores the data for the Victim-based and Other crime types passed in as parameters which was read from the CSV file.
		public function __construct($rootElementName, $crimeTypes, $victimCrimes, $otherCrimes){
			$this->crimeCategories = $crimeTypes;
			$this->victimCrimeTypes = $victimCrimes;
			$this->otherCrimeTypes = $otherCrimes;
		
			$this->dom = new DomDocument('1.0', 'UTF-8');
			$this->dom->preserveWhiteSpace = false;
			$this->dom->formatOutput = true;
			
			$root = $this->dom->createElement("crimes");
			$this->dom->appendChild($root);
		}
		
		public function saveXML($filePath){
			$this->dom->save($filePath);
		}
		
		public function getRoot(){
			return $this->dom->documentElement;
		}
		
		//Creates a new XML node with one attribute and appends that node to the parent XML element passed within the parameter.
		public function createNode($parentElement, $nodeName, $attName, $attValue){
			$node = $this->dom->createElement($nodeName);
			$node->setAttribute($attName, $attValue);
			$parentElement->appendChild($node);
			return $node;
		}
		
		//Loops through each crime categories and appends the crime categories within the XML element passed in the paramter..
		public function createCrimeCategories($parentElement){
			foreach($this->crimeCategories as $categories){
				$crimetypeElement = $this->dom->createElement("crimetype");
				$crimetypeElement->setAttribute("id", $categories);
				$parentElement->appendChild($crimetypeElement);
			}
		}
		
		/*	
			Loops through each Victim-based crime categories and appends those categories to the XML element passed in from the parameter,
			as well as populating those categories with the data passed in from the parameter.
		*/
		public function creatVictimCrimes($parentElement, $crimeData){
			foreach($this->victimCrimeTypes as $key => $victimCrimes){
				$crime = $this->dom->createElement("total");
				$crime->setAttribute("id", $victimCrimes);
				$crime->nodeValue = $crimeData[$key];
				$vicCrimeElem = $parentElement->getElementsByTagName('crimetype')->item(0);
				$vicCrimeElem->appendChild($crime);
			}			
		}
		
		/*	
			Loops through each Other crime categories and appends those categories to the XML element passed in from the parameter,
			as well as populating those categories with the data passed in from the parameter.
		*/
		public function createOtherCrimes($parentElement, $crimeData){
			$offset = 18;
			foreach($this->otherCrimeTypes as $key => $otherCrimes){
				$crime = $this->dom->createElement("total");
				$crime->setAttribute("id", $otherCrimes);
				$crime->nodeValue = $crimeData[$offset];
				$otherCrimeElem = $parentElement->getElementsByTagName('crimetype')->item(1);
				$otherCrimeElem->appendChild($crime);
				$offset++;
			}	
		}
	}
?>