<?php
	
	require_once("Errors.php");
	
	if(!empty($_GET['format']) && !empty($_GET['region']) && !empty($_GET['total'])){

		set_error_handler(array('Errors', 'serviceWarningHandler'));
	
		if(is_float($_GET['total']) || !is_numeric($_GET['total'])){
			Errors::sendError(501,'URL pattern not recognized');
			exit();
		}
		
		$responseFormat = strtolower($_GET['format']);
		
		if($responseFormat != 'xml' && $responseFormat != 'json'){
			Errors::sendError(501,'URL pattern not recognized');
			exit();
		}
		
		require_once('CrimeXMLHandler.php');
		
		$crimeXMLHandler = new CrimeXMLHandler("CrimeXML.xml");		
		
		$newTotal = $_GET['total'];
		
		$region = str_replace("_", " ", $_GET['region']);
		$regionName = ucwords($region);

		if($regionName != 'British Transport Police' &&  $regionName != 'Action Fraud'){
			$regionName .= ' Region';
		}

		if($crimeXMLHandler->doesLocationExist($regionName)){
			
			$currentTotal = $crimeXMLHandler->searchXML("//*[@name='$regionName']/crimetype[1]/total[1]")->item(0)->nodeValue;
			
			$crimeXMLHandler->searchXML("//*[@name='$regionName']/crimetype[1]/total[1]")->item(0)->nodeValue = $newTotal;
			
			$crimeXMLHandler->saveXML();
			
			$outputRegionName = str_replace(" ", "_", $regionName);
			$outputRegionName = strtolower($outputRegionName);
			
			if($responseFormat == 'xml'){

				require_once('DomResponseHandler.php');
				
				header('Content-type: text/xml');
				
				$dom = new DomResponseHandler();
				
				//create root node
				$root = $dom->createRoot("response");
				$crimes = $dom->createElement("crimes");
				$dom->addAttribute($crimes, "year", "6-2013");
				$dom->insertElement($root, $crimes);
				
				//region
				$region = $dom->createElement("region");
				$dom->addAttribute($region, "id", $outputRegionName);
				$dom->addAttribute($region, "previous", $currentTotal);
				$dom->addAttribute($region, "total", $newTotal);
				$dom->insertElement($crimes, $region);
				
				echo $dom->getXMLResponseXML();

			}else if($responseFormat == 'json'){
			
				header('Content-type: application/json');

				$jsonResponse['response'] = array(
							'timestamp' => time(), 'crimes' => array(
							'year' => '6-2013', 'region' => array(
							'id' => $outputRegionName,
							'previous' => $currentTotal,
							'total' => $newTotal
						)
					)
				);
				
				echo json_encode($jsonResponse, JSON_PRETTY_PRINT);
			}
			
		}else{
			Errors::sendError(404,'Not Found');
			exit();
		}
	}else{
		//Error - missing URI parameters
		Errors::sendError(501,'URL pattern not recognized');
		exit();
	}
?>