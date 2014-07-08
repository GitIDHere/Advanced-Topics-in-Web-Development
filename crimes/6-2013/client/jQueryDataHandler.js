$(document).ready(function(){
	
	var chartColours = new Array("#D15E5E", "#F2DE8F", "#CFF595", "#95EBF5", "#9995F5", "#D795F5");
	
	$('.button').click(function(event){
		
		var chosenRegion = $(this).attr('value');
		
		var currentTime = parseInt(new Date().getTime()/1000);
		
		if(currentTime < parseInt(localStorage.getItem(chosenRegion+'_time'))+60){
			var requestCache = JSON.parse(localStorage.getItem(chosenRegion));
			createChart(requestCache);
		}else{
			
			if(currentTime > parseInt(localStorage.getItem(chosenRegion+'_time'))){
				localStorage.removeItem(chosenRegion);
				localStorage.removeItem(chosenRegion+"_time");
			}
			
			$.ajax({
				url: "../Get.php",
				type: "get",
				data: {region:chosenRegion, format:'json'},
				dataType: "json",
				success: createChart
			});
		}

		function createChart(jsonResponse){
			
			//set the cache
			if(!localStorage.getItem(chosenRegion)){
				localStorage.setItem(chosenRegion, JSON.stringify(jsonResponse));
				localStorage.setItem(chosenRegion+"_time", currentTime);
			}

			$('#legends').empty();
			
			var area = jsonResponse.response.crimes.region.area;
			
			var crimeLabels = [];
			var crimeData = [];
			var pieData = new Array();
			var pieLegends = "";
			
			$.each(area, function (index, areaData) {
			
				crimeLabels.push(areaData['id']);
				crimeData.push(parseInt(areaData['total']));
				
				pieData.push({value:parseInt(areaData['total']), color:chartColours[index]});
				
				pieLegends += '<tr style="background:'+chartColours[index]+'"><td>'+areaData['id']+'</td><td>'+areaData['total']+'</td></tr>';
			});
			
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
			
			$('p.placeholderText').remove();
			$('canvas').remove();
			
			/*
				Both the bar chart and the pie chart are created using Chart.js.
				Available from: http://www.chartjs.org/
			*/
			
			$('section#topSection').append('<canvas id="barChart" width="1080" height="400"></canvas>');
			var myLine = new Chart(document.getElementById("barChart").getContext("2d")).Bar(barChartData);
			
			//Pie chart legends
			var regionData = '<tr id="regionHeading"><td>'+jsonResponse.response.crimes.region['id']+'</td><td>'+jsonResponse.response.crimes.region['total']+'</td></tr>'
			var areaHeadings = '<tr id="areaLegendHeading"><td>Areas</td><td>Totals</td></tr>';
			$('#legends').append(regionData);
			$('#legends').append(areaHeadings);
			$('#legends').append(pieLegends);
			
			
			$('section#midSection').append('<canvas id="pieChart" width="540" height="397"></canvas>');
			var pieChart = new Chart(document.getElementById("pieChart").getContext("2d")).Pie(pieData);
		}

	});

});