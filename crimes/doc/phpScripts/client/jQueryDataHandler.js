$(document).ready(function(){
	
	//An array of colours which will be used within the charts.
	var chartColours = new Array("#D15E5E", "#F2DE8F", "#CFF595", "#95EBF5", "#9995F5", "#D795F5");
	
	//Function activated upon a mouse click on one of the region buttons within the HTML.
	$('.button').click(function(event){
		
		var chosenRegion = $(this).attr('value');
		
		/*
			Local caching start
		*/
		
		//Get the current tile in seconds.
		var currentTime = parseInt(new Date().getTime()/1000);
		
		/*
			If the current time is less than the time the time of the local cache of the chosen region, 
			then acquire that cached data.
			
			Else make a new Ajax request to the server for the response data.
		*/
		if(currentTime < parseInt(localStorage.getItem(chosenRegion+'_time'))+60){
			var requestCache = JSON.parse(localStorage.getItem(chosenRegion));
			createChart(requestCache);
		}else{
			
			/*
				Check if the current time is more than the locally cached data for the chosen region.
				If it is then remove the local cache for the chosen region since it has expired.
			*/
			if(currentTime > parseInt(localStorage.getItem(chosenRegion+'_time'))){
				localStorage.removeItem(chosenRegion);
				localStorage.removeItem(chosenRegion+"_time");
			}

			$.ajax({
				url: "../Get.php",
				type: "GET",
				data: {region:chosenRegion, format:'json'},
				dataType: "json",
				success: createChart
			});
		}
		/*
			Local caching end.
		*/
		
		
		//Function to create the charts.
		function createChart(jsonResponse){
			
			//If there is no local cache for the requested region, then create the cache.
			if(!localStorage.getItem(chosenRegion)){
				localStorage.setItem(chosenRegion, JSON.stringify(jsonResponse));
				localStorage.setItem(chosenRegion+"_time", currentTime);
			}
			
			//Empty out the table containing the legends for the pie chart.
			$('#legends').empty();
			
			//Get the JSON area data from the response.
			var area = jsonResponse.response.crimes.region.area;
			
			var crimeLabels = [];
			var crimeData = [];
			var pieData = new Array();
			var pieLegends = "";
			
			//For each area, push the data into their relevant arrays.
			$.each(area, function (index, areaData) {
				
				crimeLabels.push(areaData['id']);
				crimeData.push(parseInt(areaData['total']));
				
				pieData.push({value:parseInt(areaData['total']), color:chartColours[index]});
				
				//Create a HTML table row for the pie chart legend, passing in the current area's name and total.
				pieLegends += '<tr style="background:'+chartColours[index]+'"><td>'+areaData['id']+'</td><td>'+areaData['total']+'</td></tr>';
			});
			
			//Insert the bar chart array data into an array to be used for creating the bar chart.
			var barChartData = {
				labels : crimeLabels,
				datasets : [
					{
						fillColor : chartColours,
						strokeColor : chartColours,
						data : crimeData
					}
				]
			}
			
			//Remove any place holder text or canvas elements from the current HTML page.
			$('p.placeholderText').remove();
			$('canvas').remove();
			
			/*
				Both the bar chart and the pie chart are created using Chart.js.
				Available from: http://www.chartjs.org/
			*/
			//Create the bar chart
			$('section#topSection').append('<canvas id="barChart" width="1080" height="400"></canvas>');
			var myLine = new Chart(document.getElementById("barChart").getContext("2d")).Bar(barChartData);
			
			//Create a string representing a HTML table row containing the current request's region name and total.
			var regionData = '<tr id="regionHeading"><td>'+jsonResponse.response.crimes.region['id']+'</td><td>'+jsonResponse.response.crimes.region['total']+'</td></tr>'
			
			var areaHeading = '<tr id="areaLegendHeading"><td>Areas</td><td>Totals</td></tr>';
			
			//Append the pie chart legends data into an HTML element within the page.
			$('#legends').append(regionData);
			$('#legends').append(areaHeading);
			$('#legends').append(pieLegends);
			
			//Create the pie chart.
			$('section#midSection').append('<canvas id="pieChart" width="540" height="397"></canvas>');
			var pieChart = new Chart(document.getElementById("pieChart").getContext("2d")).Pie(pieData);
		}

	});

});