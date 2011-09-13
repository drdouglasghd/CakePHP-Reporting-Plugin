<?php 
class ReportingAppController extends AppController {

	function beforeFilter(){
		parent::beforeFilter();
		Configure::load('reporting.reporting_core');
	}
	
} ?>