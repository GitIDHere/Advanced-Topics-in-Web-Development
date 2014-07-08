<?php
	
	require_once("Errors.php");
	
	if(!empty($_GET['format']) && !empty($_GET['region']) && !empty($_GET['total'])){
		
		//Set the error handler to handle any internal service errors (error code - 500)
		set_error_handler(array('Errors', 'serviceWarningHandler'));
		
		//Only accept integer numbers from the request data.
		if(is_float($_GET['total']) || !is_numeric($_GET['total'])){
			Errors::sendError(501,'URL pattern not recognized');
			exit();
		}
		
		$responseFormat = strtolower($_GET['format']);
		
		//Only accept the request formats that are supported.
		if($responseFormat != 'xml' && $responseFormat != 'json'){
			Errors::sendError(501,'URL pattern not recognized');
			exit();
		}
		
		require_once('CrimeXMLHandler.php');
		
		$crimeXMLHandler = new CrimeXMLHandler("CrimeXML.xml");		
		
		$newTotal = $_GET['total'];
		
		//Remove the underscore and uppercase the region name passed in.
		$region = str_replace("_", " ", $_GET['region']);
		$regionName = ucwords($region);
		
		//Append ' Region' to the region name if it is not BTP or AF.
		if($regionName != 'British Transport Police' &&  $regionName != 'Action Fraud'){
			$regionName .= ' Region';
		}
		
		//Check if the area exists within the CrimeXML
		if($crimeXMLHandler->doesLocationExist($regionName)){
			
			//Get the current total for the region passed in from the URL.
			$currentTotal = $crimeXMLHandler->searchXML("//*[@name='$regionName']/crimetype[1]/total[1]")->item(0)->nodeValue;
			
			//Update the requested region's total within CrimeXML.
			$crimeXMLHandler->searchXML("//*[@name='$regionName']/crimetype[1]/total[1]")->item(0)->nodeValue = $newTotal;
			
			$crimeXMLHandler->saveXML();
			
			//Format the region name to be the same format presented in the specification.
			$outputRegionName = str_replace(" ", "_", $regionName);
			$outputRegionName = strtolower($outputRegionName);
			
			if($responseFormat == 'xml'){
				
				//Create XML response.
				
				require_once('DomResponseHandler.php');
				
				header('Content-type: application/xml');
				
				$dom = new DomResponseHandler();
				
				//Create root and crime XML response elements
				$root = $dom->createRoot("response");
				$crimes = $dom->createElement("crimes");
				$dom->addAttribute($crimes, "year", "6-2013");
				$dom->insertElement($root, $crimes);
				
				//Create and append a region node to the crime element. The region node contains the necessary attributes for the requested response format.
				$region = $dom->createElement("region");
				$dom->addAttribute($region, "id", $outputRegionName);
				$dom->addAttribute($region, "previous", $currentTotal);
				$dom->addAttribute($region, "total", $newTotal);
				$dom->insertElement($crimes, $region);
				
				echo $dom->getXMLResponseXML();

			}else if($responseFormat == 'json'){
				
				//Create JSON response
				
				header('Content-type: application/json');
				
				
				//Create the JSON response array containing the region data to be displayed.
				$jsonResponse['response'] = array(
								'timestamp' => time(), 
								'crimes' => array(
									'year' => '6-2013', 
									'region' => array(
										'id' => $outputRegionName,
										'previous' => $currentTotal,
										'total' => $newTotal
						)
					)
				);
				
				echo json_encode($jsonResponse, JSON_PRETTY_PRINT);
			}
			
		}else{
			//Send error if the area does not exist within CrimeXML.
			Errors::sendError(404,'Not Found');
			exit();
		}
	}else{
		//Send error if there are missing URI parameters
		Errors::sendError(501,'URL pattern not recognized');
		exit();
	}
?>