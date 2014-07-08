<?php
	
	class Errors{
		
		/*
			This is the error class which is used by other scripts that need to output any errors.
			This class is static so no class object is required for this class.
		*/
		
		private static $dom;
		private static $initialized = false;
		
		//This function is used to initialize variables for the error response. If it has already been initialized, then it just returns.
		private static function initializeVariables(){
			if (self::$initialized){
				return;
			}
			
			self::$dom = new DomDocument("1.0", "UTF-8");
			self::$dom->preserveWhiteSpace = false;
			self::$dom->formatOutput = true;
			self::$initialized = true;
		}
		
		//This function is used to out put errors based on the error code and description passed in from the paramters.
		public static function sendError($code, $desc){
		
			self::initializeVariables();
			
			header($_SERVER['SERVER_PROTOCOL'].$code.' '.$desc, true, $code);
			
			header('Content-type: application/xml');
			
			//Create the error XML.
			$root = self::$dom->createElement("response");
			$root->setAttribute("timestamp", time());
			self::$dom->appendChild($root);

			$errorElement = self::$dom->createElement("error");
			$errorElement->setAttribute("code", $code);
			$errorElement->setAttribute("desc", $desc);
			self::$dom->documentElement->appendChild($errorElement);
			
			echo self::$dom->saveXML();
		}
		
		//This function is passed as a parameter into set_error_handler within the REST files to handle service errors.
		public static function serviceWarningHandler() {
			self::initializeVariables();
			
			header($_SERVER['SERVER_PROTOCOL'].'500 Service Error', true, 500);
			
			header('Content-type: application/xml');
			
			//Create the error XML.
			$root = self::$dom->createElement("response");
			$root->setAttribute("timestamp", time());
			self::$dom->appendChild($root);

			$errorElement = self::$dom->createElement("error");
			$errorElement->setAttribute("code", 500);
			$errorElement->setAttribute("desc", "Service Error");
			self::$dom->documentElement->appendChild($errorElement);
			
			echo self::$dom->saveXML();
			exit();
		}	
		
	}
?>