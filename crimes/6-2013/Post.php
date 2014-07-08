<?php
	
	require_once("Errors.php");
	
	if(!empty($_GET['format']) && !empty($_GET['region']) && !empty($_GET['area']) && !empty($_GET['data'])){
		
		//Set an error handler for interal service errors (500)
		set_error_handler(array('Errors', 'serviceWarningHandler'));
	
		require_once('CrimeXMLHandler.php');
		$crimeXMLHandler = new CrimeXMLHandler("CrimeXML.xml");		
		
		$region = str_replace("_", " ", $_GET['region']);
		$regionName = ucwords($region);
		
		//If the region name is not BTP or AF, then append ' Region' to the end of region name.
		if($regionName != 'British Transport Police' &&  $regionName != 'Action Fraud'){
			$regionName .= ' Region';
		}
		
		$newAreaName = ucwords($_GET['area']);

		if(!$crimeXMLHandler->doesLocationExist($regionName)){
			Errors::sendError(404,'Not Found');
			exit();
		}
		
		$responseFormat = strtolower($_GET['format']);
		
		if($responseFormat != 'xml' && $responseFormat != 'json'){
			Errors::sendError(501,'URL pattern not recognized');
			exit();
		}
		
		$crimeTotalSum = 0;
		$newCrimeDataArr = array();
		$newData = explode("-", $_GET['data']);
		
		//Loop through the data that is passed in from the URI and further explode it and put those data into an array.
		foreach($newData as $key => $data){	
			$newCrimeDataArr[] = explode(":", $data);
			
			//Create a total sum of the crime data that has been passed in from the URI.
			$crimeTotalSum += $newCrimeDataArr[$key][1];
		}
		
		//Load the area template which will be used to insert data in and append to CrimeXML.
		$newAreaDom = new DomDocument("1.0","UTF-8");
		$newAreaDom->load("AreaTemplate.xml");
		
		//Insert the name of the area in the root tag as an attribute.
		$newAreaDom->documentElement->setAttribute("name", $newAreaName);
		
		//Get all the 'total' elements within the area template.
		$crimeTotalElems = $newAreaDom->getElementsByTagName("total");
		
		//This array will contain the crime names 
		$recordedCrimeTypes = array();
		
		//For every crime total, check if its name matches the acronym supplied by the user.
		foreach($crimeTotalElems as $total){
		
			$attribute = $total->getAttribute("id");
			
			foreach($newCrimeDataArr as $newData){
			
				$acronym = $crimeXMLHandler->getAcronym($attribute);
				
				if($acronym == $newData[0]){
					$total->nodeValue = $newData[1];
					$recordedCrimeTypes[] = $attribute;
				}
			}
			
		}

		
		//update 'Total recorded crime - including fraud' for the new area.
		$newAreaDom->getElementsByTagName("total")->item(0)->nodeValue += $crimeTotalSum;
		
		//Update region total in the main Crime XML
		$crimeXMLHandler->searchXML("//*[@name='$regionName']/crimetype[1]/total[1]")->item(0)->nodeValue += $crimeTotalSum;
		$newRegionTotal = $crimeXMLHandler->searchXML("//*[@name='$regionName']/crimetype[1]/total[1]")->item(0)->nodeValue;
		
		//update England total.
		$englandTotal = $crimeXMLHandler->searchXML("//*[@name='ENGLAND']/crimetype[1]//total[1]");
		$englandTotal->item(0)->nodeValue += $crimeTotalSum;
		$newEngTotal = $englandTotal->item(0)->nodeValue;
		
		//Update England and Wales totals.
		$engWalesTotal = $crimeXMLHandler->searchXML("//*[@name='ENGLAND AND WALES']/crimetype[1]//total[1]");
		$engWalesTotal->item(0)->nodeValue += $crimeTotalSum;
		$newEngWalTotal = $engWalesTotal->item(0)->nodeValue;

		$newArea = $newAreaDom->getElementsByTagName("area")->item(0);
		
		//Check if exists
		if($crimeXMLHandler->doesLocationExist($newAreaName)){
		
			$currentArea = $crimeXMLHandler->searchXML("//*[@name='$newAreaName']")->item(0);
			$parent = $currentArea->parentNode;
			$parent->removeChild($currentArea);

			$newArea = $parent->ownerDocument->importNode($newArea, true);
			$parent->appendChild($newArea);
			
		}else{
			$region = $crimeXMLHandler->searchXML("//*[@name='$regionName']")->item(0);
			$newArea = $region->ownerDocument->importNode($newArea, true);
			$region->appendChild($newArea);
		}
		
		$crimeXMLHandler->saveXML();
		
		if($responseFormat == 'xml'){
			
			require_once('DomResponseHandler.php');
			
			header('Content-type: text/xml');
			
			$dom = new DomResponseHandler();
			
			//create root node
			$root = $dom->createRoot("response");
			$crimes = $dom->createElement("crimes");
			$dom->addAttribute($crimes, "year", "6-2013");
			$dom->insertElement($root, $crimes);
			
			//Region Element
			$regionName = substr($regionName, 0, -7);
			$region = $dom->createElement("region");
			$dom->addAttribute($region, "id", $regionName);
			$dom->addAttribute($region, "total", $newRegionTotal);
			$dom->insertElement($crimes, $region);
			
			//Area element
			$area = $dom->createElement("area");
			$dom->addAttribute($area, "id", $newAreaName);
			$dom->addAttribute($area, "total", $crimeTotalSum);
			$dom->insertElement($region, $area);
			
			//Recorded Crimes elements //
			foreach($newCrimeDataArr as $index => $newData){
				$recordedCrimes = $dom->createElement("recorded");
				$dom->addAttribute($recordedCrimes, "id", $recordedCrimeTypes[$index]);
				$dom->addAttribute($recordedCrimes, "total", $newData[1]);
				$dom->insertElement($area, $recordedCrimes);
			}
			
			//Append England total
			$england = $dom->createElement("england");
			$dom->addAttribute($england, "total", $newEngTotal);
			$dom->insertElement($crimes, $england);
			
			//Append England and Wales total
			$engWales = $dom->createElement("england_wales");
			$dom->addAttribute($engWales, "total", $newEngWalTotal);
			$dom->insertElement($crimes, $engWales);

			echo $dom->getXMLResponseXML();
			
		}else if($responseFormat == 'json'){
			
			header('Content-type: application/json');
			
			$regionName = substr($regionName, 0, -7);
			
			$jsonResponse['response'] = array(
						'timestamp' => time(), 
						'crimes' => array(
							'year' => '6-2013',
							'region' => array(
								'id' => $regionName,
								'total' => $newRegionTotal,
								'area' => array(
									'id' => $newAreaName,
									'total' => $crimeTotalSum
								)
							)
						)
					);
			
			//Append Recorded Crimes
			foreach($recordedCrimeTypes as $key => $crimeName){
			     $jsonResponse['response']['crimes']['region']['area']['recorded'][$key]['id'] = $recordedCrimeTypes[$key];
				 $jsonResponse['response']['crimes']['region']['area']['recorded'][$key]['total'] = $newCrimeDataArr[$key][1];
			}
			
			//Append totals for England
			$jsonResponse["response"]['crimes']['england']['total'] = $newEngTotal;
			
			//Append total for england and wales
			$jsonResponse["response"]['crimes']['england_wales']['total'] = $newEngWalTotal;
			
			echo json_encode($jsonResponse, JSON_PRETTY_PRINT);
		}
		
	}else{
		//error
		Errors::sendError(501,'URL pattern not recognized');
		exit();
	}
?>