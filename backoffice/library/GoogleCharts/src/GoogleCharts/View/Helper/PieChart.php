<?php

namespace GoogleCharts\View\Helper;

use GoogleCharts\GoogleChart;

class PieChart extends GoogleChart
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
				[\'Partner\', \'Bookings per Partner\'],';
        
        foreach ($data as $row) {
        	$js .= "['" . $row->getSectionTitle() . "', " . $row->getCount() . "],";
        }
        
        $js .= '
    			]);

				var options = {
					title: \'' . $title . '\'
				};

				var chart = new google.visualization.PieChart(document.getElementById(\'' . $id . '\'));
				chart.draw(data, options);
    		};';
        
        $this->getView()->inlineScript()->appendScript($js);
        return $this->renderHtml($id, $attributes);
    }
}