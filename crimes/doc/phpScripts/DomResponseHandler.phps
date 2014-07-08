<?php
	class DomResponseHandler{
		
		/*
			This is a helper class which is used to create the responses for the REST request.
		*/
		
		private $dom;
		
		public function __construct(){
			$this->dom = new DOMDocument('1.0', 'utf-8');
			$this->dom->preserveWhiteSpace = false;
			$this->dom->formatOutput = true;
		}
		
		//Function to create the root element for the response.
		public function createRoot($rootName){
		
			$root = $this->dom->createElement($rootName);
			$this->dom->appendChild($root);
			
			$timestampElement = $this->dom->createAttribute("timestamp");
			$timestampElement->value = time();
			$root->appendChild($timestampElement);
			
			return $root;
		}
		
		//Retreive the XML within the DOM.
		public function getXMLResponseXML(){
			return $this->dom->saveXML();
		}
		
		//Create an element with the specified element name. 
		public function createElement($elementName){
			$element = $this->dom->createElement($elementName);
			$this->dom->appendChild($element);
			return $element;
		}
		
		//Add an attribute to an element.
		public function addAttribute($parentElement, $attributeName, $attributeValue){
			$parentElement->setAttribute($attributeName, $attributeValue);
		}
		
		//Append a child element to the parent element passed in through the parameter.
		public function insertElement($parentElem, $childEleme){
			$parentElem->appendChild($childEleme);
		}
	}
?>