<?php

namespace GoogleCharts\View\Helper;

use GoogleCharts\GoogleChart;

class ColumnChart extends GoogleChart
{

	/**
	 * (non-PHPdoc)
	 * @see \GoogleCharts\GoogleChart::__invoke()
	 */
    public function __invoke($id, array $attributes = array(), $title, $data) {
    	parent::__invoke($id, $attributes, $title, $data);
        
        $js = '
        	google.load("visualization", "1", {packages:["corechart"]});
			google.setOnLoadCallback(drawChart);
			function drawChart() {
				var data = google.visualization.arrayToDataTable([
		          	[\'Year\', \'Sales\', \'Expenses\'],
		          	[\'2004\',  1000,      400],
		          	[\'2005\',  1170,      460],
		          	[\'2006\',  660,       1120],
		          	[\'2007\',  1030,      540]
		        ]);

				var options = {
					title: \'' . $title . '\',
					hAxis: {title: \'Year\', titleTextStyle: {color: \'red\'}}
				};

				var chart = new google.visualization.ColumnChart(document.getElementById(\'' . $id . '\'));
				chart.draw(data, options);
    		};';
        
        $this->getView()->inlineScript()->appendScript($js);
        return $this->renderHtml($id, $attributes);
    }
}