<?php
	
	require_once("Errors.php");
	
	if(!empty($_GET['format']) && !empty($_GET['region'])){
	
		set_error_handler(array('Errors', 'serviceWarningHandler'));
	
		require_once("Cache.php");
		require_once('CrimeXMLHandler.php');
		
		$crimeXMLHandler = new CrimeXMLHandler("CrimeXML.xml");
		
		$region = str_replace('_', " ", $_GET['region']);
		$regionName = ucwords($region);
		
		//If the region is not BTP or AF, then append ' Region' to the end of the region name given
		// so that the XPath query can match the region in CrimeXML.
		if($regionName != 'British Transport Police' &&  $regionName != 'Action Fraud'){
			$regionName .= ' Region';
		}

		$responseFormat = strtolower($_GET['format']);
		
		if($responseFormat != 'xml' && $responseFormat != 'json'){
			Errors::sendError(501,'URL pattern not recognized');
			exit();
		}
		
		//Check whether the request time is within one minute since CrimeXML has been edited.
		// If it is, return the cached response and exit.
		if(Cache::isRequestWithinAMinute()){
			echo Cache::getCache($regionName, $responseFormat);
			exit();
		}

		//Check if the region exists within CrimeXML.
		if(!$crimeXMLHandler->doesLocationExist($regionName)){
			Errors::sendError(404,'Not Found');
			exit();
		}
		
		//Get the region and its overall total.
		$regionData = $crimeXMLHandler->getXMLElements("//*[@name='$regionName']/@name | //*[@name='$regionName']/crimetype[@id='Victim-based crime']//total[@id='Total recorded crime - including fraud']");
		
		//Remove 'Region' from the end of the region name so that it is as the response requires.
		$crimeXMLHandler->removeRegionFromEnd($regionData);
		
		//retreive the areanames and totals within the region.
		$areaData = $crimeXMLHandler->getXMLElements("//*[@name='$regionName']//area/@name | //*[@name='$regionName']//area/crimetype[@id='Victim-based crime']//total[@id='Total recorded crime - including fraud']");
		
		if($responseFormat == 'xml'){
			
			require_once('DomResponseHandler.php');
			
			header('Content-type: text/xml');
			
			$dom = new DomResponseHandler();
			
			//Create the response XML structure.
			$root = $dom->createRoot("response");
			$crimes = $dom->createElement("crimes");
			$dom->addAttribute($crimes, "year", "6-2013");
			$dom->insertElement($root, $crimes);
			
			$region = $dom->createElement("region");
			$dom->addAttribute($region, "id", $regionData[0][0]);
			$dom->addAttribute($region, "total", $regionData[0][1]);
			$dom->insertElement($crimes, $region);

			foreach($areaData as $key => $value){
				$area = $dom->createElement("area");
				$dom->addAttribute($area, "id", $value[0]);
				$dom->addAttribute($area, "total", $value[1]);
				$dom->insertElement($region, $area);
			}
			
			$xmlResponse = $dom->getXMLResponseXML();
			
			//Cache the response XML since the Cached version has not been returned.
			file_put_contents("local_cache/$responseFormat/$regionName.txt", $xmlResponse);

			echo $xmlResponse;
			
		}else if($responseFormat == 'json'){
			
			header('Content-type: application/json');
			
			//Create the JSON response.
			$jsonResponse['response'] = array(
						'timestamp' => time(), 'crimes' => array(
						'year' => "6-2013", 'region' => array(
						'id' => $regionData[0][0],
						'total' => $regionData[0][1]
					)
				)
			);
			
			foreach($areaData as $key => $value){
				$jsonResponse['response']['crimes']['region']['area'][$key]['id'] = $value[0];
				$jsonResponse['response']['crimes']['region']['area'][$key]['total'] = $value[1];
			}
			
			$jsonResponse = json_encode($jsonResponse, JSON_PRETTY_PRINT);
			
			//Cache the JSON response.
			file_put_contents("local_cache/$responseFormat/$regionName.txt", $jsonResponse);
			
			echo $jsonResponse;
		}
		
	}else{
		//Error for missing URI strings
		Errors::sendError(501,'URL pattern not recognized');
		exit();
	}
?>