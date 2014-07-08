<?php
	
	require_once("Errors.php");
	
	if(!empty($_GET['format'])){
		
		set_error_handler(array('Errors', 'serviceWarningHandler'));
		
		require_once("Cache.php");
		require_once('CrimeXMLHandler.php');
		
		$responseFormat = strtolower($_GET['format']);
		
		if($responseFormat != 'xml' && $responseFormat != 'json'){
			Errors::sendError(501,'URL pattern not recognized');
			exit();
		}
		
		if(Cache::isRequestWithinAMinute()){
			echo Cache::getCache(null, $responseFormat);
			exit();
		}
		
		$crimeXMLHandler = new CrimeXMLHandler("CrimeXML.xml");
		
		//region names and totals
		$regionsArray =  $crimeXMLHandler->getXMLElements("//region/@name | //region/crimetype[@id='Victim-based crime']//total[@id='Total recorded crime - including fraud']");
		
		//Remove 'Region' from the end of the first regions which contain the word Region
		for($i = 0; $i < 9; $i++){
			$regionsArray[$i][0] = substr($regionsArray[$i][0], 0, -7);
		}
		
		//national data
		$nationals = $crimeXMLHandler->getXMLElements("//national/@name | //national/crimetype[@id='Victim-based crime']//total[@id='Total recorded crime - including fraud']");

		//country names and total data
		$countries = $crimeXMLHandler->getXMLElements("//country/@name | //country/crimetype[@id='Victim-based crime']//total[@id='Total recorded crime - including fraud']");
		
		if($responseFormat == 'xml'){
			
			require_once('DomResponseHandler.php');
			
			header('Content-type: text/xml');

			$dom = new DomResponseHandler();
			
			//create root node
			$root = $dom->createRoot("response");
			$crimes = $dom->createElement("crimes");
			$dom->addAttribute($crimes, "year", "6-2013");
			$dom->insertElement($root, $crimes);
			
			//rename value
			foreach($regionsArray as $regionData){
				$region = $dom->createElement("region");
				$dom->addAttribute($region, "id", $regionData[0]);
				$dom->addAttribute($region, "total", $regionData[1]);
				$dom->insertElement($crimes, $region);
			}
			
			foreach($nationals as $nationalData){
				$nation = $dom->createElement("national");
				$dom->addAttribute($nation, "id", $nationalData[0]);
				$dom->addAttribute($nation, "total", $nationalData[1]);
				$dom->insertElement($crimes, $nation);
			}
			
 			foreach($countries as $countryData){
				$countryName = strtolower($countryData[0]);
				$country = $dom->createElement($countryName);
				$dom->addAttribute($country, "total", $countryData[1]);
				$dom->insertElement($crimes, $country);
			} 
			
			$xmlResponse = $dom->getXMLResponseXML();
			
			file_put_contents("local_cache/$responseFormat/allCrimeData.txt", $xmlResponse);
			
			echo $xmlResponse;			
			
		}else if($responseFormat == 'json'){
			
			header('Content-type: application/json');

			$jsonResponse['response'] = array(
							'timestamp' => time(), 
							'crimes' => array(
								'year' => '6-2013'
								)
						);
			
			foreach($regionsArray as $key => $value){
				$jsonResponse['response']['crimes']['region'][$key]['id'] = $value[0];
				$jsonResponse['response']['crimes']['region'][$key]['total'] = $value[1];
			}
			
			foreach($nationals as $key => $value){
				$jsonResponse['response']['crimes']['national'][$key]['id'] = $value[0];
				$jsonResponse['response']['crimes']['national'][$key]['total'] = $value[1];
			}
			
			foreach($countries as $value){
				$countryName = strtolower($value[0]);
				$jsonResponse['response']['crimes'][$countryName]['total'] = $value[1];
			}
			
			$jsonResponse = json_encode($jsonResponse, JSON_PRETTY_PRINT);
			
			file_put_contents("local_cache/$responseFormat/allCrimeData.txt", $jsonResponse);
			
			echo $jsonResponse;
		}
		
	}else{
		//Error - missing URI parameters
		Errors::sendError(501,'URL pattern not recognized');
		exit();
	}
?>