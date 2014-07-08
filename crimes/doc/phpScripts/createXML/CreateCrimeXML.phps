<?php
	require_once('ReadCSV.php');
	require_once('XMLHandler.php');
	
	$csvReader = new ReadCSV('CrimeCSV.csv');
    
	//Read the first three lines of the CSV, which contains data for the crime categories and victim based crime types, and other crime types.
	$crimeTypes = $csvReader->getNextBlock();
	$victimCrimes = $csvReader->getNextBlock();
	$otherCrimes = $csvReader->getNextBlock();
	
	//Creating this class object also creates the root element called 'crimes' for the CrimeXML.
	$createXML = new XMLHandler("crimes", $crimeTypes[0], $victimCrimes[0], $otherCrimes[0]);
	
	$root = $createXML->getRoot();
	
 	//Create and append a country node containing the crime data for England to the root element.
	$englandData = $csvReader->getNextBlock();
	$engElem = $createXML->createNode($root, "country", "name", $englandData[0][0]);
	//Remove the first element from the array, which is the country name - England.
	array_splice($englandData[0], 0, 1);
	$createXML->createCrimeCategories($engElem);
	$createXML->creatVictimCrimes($engElem, $englandData[0]);
	$createXML->createOtherCrimes($engElem, $englandData[0]);
	
	//Loop through 9 times reading the 9 region data from the CSV and creating their elements.
	for($i = 0; $i < 9; $i++){
		
		//Create a region
		$regionData = $csvReader->getNextBlock();
		$regionElem = $createXML->createNode($engElem, "region", "name", $regionData[0][0]);
		//Remove the first element from the array, which is the region name
		array_splice($regionData[0], 0, 1);
		$createXML->createCrimeCategories($regionElem);
		$createXML->creatVictimCrimes($regionElem, $regionData[0]);
		$createXML->createOtherCrimes($regionElem, $regionData[0]);
		
		//Get the CSV data block under the current region CSV data. 
		$areaData = $csvReader->getNextBlock();
		
		//Loop through the area data to create the area elements to be appended to the region.
		foreach($areaData as $key => $crimeData){
		
			//Create an area element
			$areaElem = $createXML->createNode($regionElem, "area", "name", $crimeData[0]);
			//Remove the first element from the array, which is the area name.
			array_splice($crimeData, 0, 1);
			$createXML->createCrimeCategories($areaElem);
			$createXML->creatVictimCrimes($areaElem, $crimeData);
			$createXML->createOtherCrimes($areaElem, $crimeData);
		}
		
	}
	
	//Read the next block in the CSV to get the Wales data and create another country element appending the Wales data to it.
	$walesData = $csvReader->getNextBlock();
	$walesElem = $createXML->createNode($root, "country", "name", $walesData[0][0]);
	//Remove the first element from the array, which is the country name - Wales.
	array_splice($walesData[0], 0, 1);
	$createXML->createCrimeCategories($walesElem);
	$createXML->creatVictimCrimes($walesElem, $walesData[0]);
	$createXML->createOtherCrimes($walesElem, $walesData[0]);	
	
 	//Read the next CSV block containing Wales region data and create region elements for those data.
	$walesRegionData = $csvReader->getNextBlock();
	foreach($walesRegionData as $key => $regionData){
		$walesRegionElem = $createXML->createNode($walesElem, "region", "name", $walesRegionData[$key][0]);
		//Remove the first element from the array, which is the region name.
		array_splice($regionData, 0, 1);
		$createXML->createCrimeCategories($walesRegionElem);
		$createXML->creatVictimCrimes($walesRegionElem, $regionData);
		$createXML->createOtherCrimes($walesRegionElem, $regionData);
	}

	
	//Read the next CSV block and create the British Transport Police element with its crime data.
	$btpData = $csvReader->getNextBlock();
	$btpEleme = $createXML->createNode($root, "national", "name", $btpData[0][0]);
	//Remove the first element from the array, which is British Transport Police.
	array_splice($btpData[0], 0, 1);
	$createXML->createCrimeCategories($btpEleme);
	$createXML->creatVictimCrimes($btpEleme, $btpData[0]);
	$createXML->createOtherCrimes($btpEleme, $btpData[0]);
	
	//Read the next CSV block and create the Action Fraud element with its crime data.
	$afData = $csvReader->getNextBlock();
	$afElem = $createXML->createNode($root, "national", "name", $afData[0][0]);
	//Remove the first element from the array, which is Action Fraud.
	array_splice($afData[0], 0, 1);
	$createXML->createCrimeCategories($afElem);
	$createXML->creatVictimCrimes($afElem, $afData[0]);
	$createXML->createOtherCrimes($afElem, $afData[0]);
	
	//Read the next CSV block and create the England and Wales element with its crime data.
	$engAndWalesData = $csvReader->getNextBlock();
	$engWalesElem = $createXML->createNode($root, "unitedkingdom", "name", $engAndWalesData[0][0]);
	//Remove the first element from the array, which is England and Wales.
	array_splice($engAndWalesData[0], 0, 1);
	$createXML->createCrimeCategories($engWalesElem);
	$createXML->creatVictimCrimes($engWalesElem, $engAndWalesData[0]);
	$createXML->createOtherCrimes($engWalesElem, $engAndWalesData[0]); 
	
	$createXML->saveXML("CrimeXML.xml");
?>