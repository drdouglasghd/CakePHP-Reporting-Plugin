<?php
	// Expects Report Id
	$reportLink = $this->Html->link('Loading Report...',array(
		'controller'=>'reporting_reports','action'=>'view',$reportId,'plugin'=>'reporting'),
		array('class'=>'report-loader-link','id'=>'report_render_link_'.$reportId,'rel'=>$reportId));
	echo $this->Html->div('report-loader',$reportLink);
?>
