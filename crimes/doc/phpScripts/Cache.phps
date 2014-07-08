<?php
	
	class Cache{
		
		/*
			This is a helper class which is used to check if a current request is within the cache time limit.
			It also has a function to retrieve the appropriate cached file based on the region name passed in as a parameter.
		*/
		
		//Function to check if the function call is within one minute of CrimeXML being editted.
		public static function isRequestWithinAMinute(){
			if(time() < filemtime("CrimeXML.xml")+60){
				return true;
			}else{
				return false;
			}
		}
		
		//Get the server side cache for the region name passed in.
		public static function getCache($regionName, $format){
			
			//If the region name passed in is empty, then $regionName is assigned the file name  of a file which contains the cache for the GET all request.
			$regionName = (!empty($regionName)) ? $regionName : "allCrimeData";

			$requestCacheFile = file_get_contents("local_cache/$format/$regionName.txt");
			
			//Echo out the cached response.
			switch($format){
				case 'xml':
					header('Content-type: application/xml');
					echo $requestCacheFile;
					break;
				case 'json':
					header('Content-type: application/json');
					echo $requestCacheFile;
					break;
			}
		}		

	}
?>