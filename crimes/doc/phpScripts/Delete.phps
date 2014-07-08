<?php
	
	require_once("Errors.php");
	
	if(!empty($_GET['format']) && !empty($_GET['area'])){
		
		//Set the error handler to handle any 500 internal errors which might arrise.
		set_error_handler(array('Errors', 'serviceWarningHandler'));
		
		$responseFormat = strtolower($_GET['format']);
		
		//Only continue if the requested response format is supported.
		if($responseFormat != 'xml' && $responseFormat != 'json'){
			Errors::sendError(501,'URL pattern not recognized');
			exit();
		}
		
		require_once('CrimeXMLHandler.php');
		
		$crimeXMLHandler = new CrimeXMLHandler("CrimeXML.xml");				
		
		$areaName = ucwords($_GET['area']);
		
		//Only continue if the specefied area from the request exists within CrimeXML.
		if($crimeXMLHandler->doesLocationExist($areaName)){
			
			//Get all the elements contained within the area.
			$areaElement = $crimeXMLHandler->searchXML("//*[@name='$areaName']")->item(0);
			
			//Get the overall total for the area.
			$areaTotalCrime = $crimeXMLHandler->searchXML("//*[@name='$areaName']//total[@id='Total recorded crime - including fraud']")->item(0)->nodeValue;
			
			//Get all the total nodes within $areaElement.
			$areaTotals = $areaElement->getElementsByTagName('total');
			
			//This array will contain the data which is used for the response output.
			$outputCrimeData = array();
			
			
			/*
				Loop through all the total elements to populate $outputCrimeData array with the crime name and thier totals.
				This is only done for crimes that have totals above 0, since crimes with 0 totals are considered empty.
				$outputCrimeData holds the data that I will need to populate the response with.
			*/
			$counter = 0;
			foreach($areaTotals as $total){
			
				$attribute = $total->getAttribute('id');
				
				//An if statement is implemented so that only total node data are used for the population of $outputCrimeData.
				if($attribute != 'Total recorded crime - including fraud' && $attribute != 'Total recorded crime - excluding fraud'){
					
					if($total->nodeValue > 0){
						$outputCrimeData[$counter][] = $attribute;
						$outputCrimeData[$counter][] = $total->nodeValue;
						$counter++;
					}					
					
				}
			}
			
			//update england total and store the new England total within $newEngTotal.
			$englandTotal = $crimeXMLHandler->searchXML("//*[@name='ENGLAND']/crimetype[1]//total[1]");
			$englandTotal->item(0)->nodeValue -= $areaTotalCrime;
			$newEngTotal = $englandTotal->item(0)->nodeValue;
			
			//update england and wales total and store the new Wales total within $newEngWalTotal.
			$engWalesTotal = $crimeXMLHandler->searchXML("//*[@name='ENGLAND AND WALES']/crimetype[1]//total[1]");
			$engWalesTotal->item(0)->nodeValue -= $areaTotalCrime;
			$newEngWalTotal = $engWalesTotal->item(0)->nodeValue;
			
			//Delete the requested area from the CrimeXML.
			$parent = $areaElement->parentNode;
			$parent->removeChild($areaElement);
			
			$crimeXMLHandler->saveXML();
			
			if($responseFormat == 'xml'){
				
				//Create the XML response
				
				require_once('DomResponseHandler.php');
				
				header('Content-type: application/xml');
				
				$dom = new DomResponseHandler();
				
				//Create the response root element and also the crimes element.
				$root = $dom->createRoot("response");
				$crimes = $dom->createElement("crimes");
				$dom->addAttribute($crimes, "year", "6-2013");
				$dom->insertElement($root, $crimes);
				
				//Create and append an area element within the crimes element.
				$area = $dom->createElement("area");
				$dom->addAttribute($area, "id", $areaName);
				$dom->addAttribute($area, "deleted", $areaTotalCrime);
				$dom->insertElement($crimes, $area);
				
				//create the Deleted nodes with the relevant attributes with data and append them to the area element.
				foreach($outputCrimeData as $key => $crime){
					$deleted = $dom->createElement("deleted");
					$dom->addAttribute($deleted, "id", $crime[0]);
					$dom->addAttribute($deleted, "total", $crime[1]);
					$dom->insertElement($area, $deleted);
				}
				
				//Create the england node.
				$england = $dom->createElement("england");
				$dom->addAttribute($england, "total", $newEngTotal);
				$dom->insertElement($crimes, $england);
				
				//Create the england_wales node.
				$engWales = $dom->createElement("england_wales");
				$dom->addAttribute($engWales, "total", $newEngWalTotal);
				$dom->insertElement($crimes, $engWales);
				
				echo $dom->getXMLResponseXML();
				
			}else if($responseFormat == 'json'){
				
				//Create the JSON response
				
				header('Content-type: application/json');
				
				//Create the JSON response array containing the response and the crimes array.
				$jsonResponse['response'] = array(
								"timestamp" => time(), 
								"crimes" => array(
									"year" => "6-2013"
							)
						);
				
				//Append an area array to crimes withint the JSON response.
				$jsonResponse['response']['crimes']['area']['id'] = $areaName;
				$jsonResponse['response']['crimes']['area']['deleted'][0] = $areaTotalCrime;
				
				/*
					Since the area element containes an attribute called 'deleted', JSON did not allow me to append another array with the key of 'deleted'.
					I therefore appended the crime data at the next available position within the area array.
				*/
				$counter = 1;
				foreach($outputCrimeData as $key => $crime){
					$jsonResponse['response']['crimes']['area']['deleted'][$counter]['id'] = $crime[0];
					$jsonResponse['response']['crimes']['area']['deleted'][$counter]['total'] = $crime[1];
					$counter++;
				}
				
				//Append totals for England
				$jsonResponse["response"]['crimes']['england']['total'] = $newEngTotal;
				
				//Append total for england and wales
				$jsonResponse["response"]['crimes']['england_wales']['total'] = $newEngWalTotal;				
				
				echo json_encode($jsonResponse, JSON_PRETTY_PRINT);
			}
	
		}else{
			//error it does not exists
			Errors::sendError(404,'Not Found');
			exit();
		}
	}else{
		//This error is sent if the URI is missing parameters.
		Errors::sendError(501,'URL pattern not recognized');
		exit();
	}

?>