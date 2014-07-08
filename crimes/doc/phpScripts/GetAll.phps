<?php
	
	require_once("Errors.php");
	
	if(!empty($_GET['format'])){
		
		//Set the error handler to handle any internal service errors.
		set_error_handler(array('Errors', 'serviceWarningHandler'));
		
		require_once("Cache.php");
		require_once('CrimeXMLHandler.php');
		
		$responseFormat = strtolower($_GET['format']);
		
		//Check if the required format is supported.
		if($responseFormat != 'xml' && $responseFormat != 'json'){
			Errors::sendError(501,'URL pattern not recognized');
			exit();
		}
		
		/*
			Check if this current request is within one minute of CrimeXML being edited.
			If it is then return a cached version of this request.
		*/ 
		if(Cache::isRequestWithinAMinute()){
			echo Cache::getCache(null, $responseFormat);
			exit();
		}
		
		$crimeXMLHandler = new CrimeXMLHandler("CrimeXML.xml");
		
		//Retrieve all the region names and their totals from CrimeXML.
		$regionsArray =  $crimeXMLHandler->getXMLElements("//region/@name | //region/crimetype[@id='Victim-based crime']//total[@id='Total recorded crime - including fraud']");
		
		//Remove the word 'Region' from the region names.
		for($i = 0; $i < 9; $i++){
			$regionsArray[$i][0] = substr($regionsArray[$i][0], 0, -7);
		}
		
		//retrieve BTP and AF names and thier totals from CrimeXML.
		$nationals = $crimeXMLHandler->getXMLElements("//national/@name | //national/crimetype[@id='Victim-based crime']//total[@id='Total recorded crime - including fraud']");

		//retrieve england and wales names and their totals from CrimeXML.
		$countries = $crimeXMLHandler->getXMLElements("//country/@name | //country/crimetype[@id='Victim-based crime']//total[@id='Total recorded crime - including fraud']");
		
		if($responseFormat == 'xml'){
			
			//Create XML response.
			
			require_once('DomResponseHandler.php');
			
			header('Content-type: application/xml');

			$dom = new DomResponseHandler();
			
			//create root and crime elements
			$root = $dom->createRoot("response");
			$crimes = $dom->createElement("crimes");
			$dom->addAttribute($crimes, "year", "6-2013");
			$dom->insertElement($root, $crimes);
			
			//Loop through each region data and create the region elements, appending them to the crimes element at the end. 
			foreach($regionsArray as $regionData){
				$region = $dom->createElement("region");
				$dom->addAttribute($region, "id", $regionData[0]);
				$dom->addAttribute($region, "total", $regionData[1]);
				$dom->insertElement($crimes, $region);
			}
			
			//Loop through each national data and create the national elements, appending them to the crimes element at the end. 
			foreach($nationals as $nationalData){
				$nation = $dom->createElement("national");
				$dom->addAttribute($nation, "id", $nationalData[0]);
				$dom->addAttribute($nation, "total", $nationalData[1]);
				$dom->insertElement($crimes, $nation);
			}
			
			//Loop through each country data and create the country elements, appending them to the crimes element at the end. 
 			foreach($countries as $countryData){
				$countryName = strtolower($countryData[0]);
				$country = $dom->createElement($countryName);
				$dom->addAttribute($country, "total", $countryData[1]);
				$dom->insertElement($crimes, $country);
			} 
			
			$xmlResponse = $dom->getXMLResponseXML();
			
			//Cache the current request server side.
			file_put_contents("local_cache/$responseFormat/allCrimeData.txt", $xmlResponse);
			
			echo $xmlResponse;			
			
		}else if($responseFormat == 'json'){
			
			//Create JSON response.
			
			header('Content-type: application/json');

			$jsonResponse['response'] = array(
							'timestamp' => time(), 
							'crimes' => array(
								'year' => '6-2013'
								)
						);
			
			//Loop through each region data and append each data to a region array within jsonResponse.
			foreach($regionsArray as $key => $value){
				$jsonResponse['response']['crimes']['region'][$key]['id'] = $value[0];
				$jsonResponse['response']['crimes']['region'][$key]['total'] = $value[1];
			}
			
			//Loop through each national data and append each data to a national array within jsonResponse.
			foreach($nationals as $key => $value){
				$jsonResponse['response']['crimes']['national'][$key]['id'] = $value[0];
				$jsonResponse['response']['crimes']['national'][$key]['total'] = $value[1];
			}
			
			//Loop through each country data and append each data to a country array within jsonResponse.
			foreach($countries as $value){
				$countryName = strtolower($value[0]);
				$jsonResponse['response']['crimes'][$countryName]['total'] = $value[1];
			}
			
			$jsonResponse = json_encode($jsonResponse, JSON_PRETTY_PRINT);
			
			file_put_contents("local_cache/$responseFormat/allCrimeData.txt", $jsonResponse);
			
			echo $jsonResponse;
		}
		
	}else{
		//Send an error if the URI has missing data.
		Errors::sendError(501,'URL pattern not recognized');
		exit();
	}
?>