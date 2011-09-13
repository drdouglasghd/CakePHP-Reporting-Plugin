<?php 
class ReportingSubscriptionFulfillmentShell extends Shell{

	var $uses = array('Reporting.ReportingReport');
	var $tasks = array('Email');

	function main(){
		$this->out('Reporting Subscription Fulfillment');
	}
	
	function view(){
		//print_r($this->ReportingReport->read(null,1));
		$id = 1;
		
		$results = $this->_getReportResults();
		
		if(!empty($results)){
			print_r($results);
		}
		
	}

	function _getReportResults(){
		if(!isset($this->params['report_id'])){ $this->out('Need Report Id'); return false; }
		$id = $this->params['report_id'];
		if(is_numeric($id)){
			$this->data = $this->ReportingReport->read(null, $id);
		} else {
			$this->data = $this->ReportingReport->findByUrlKey($id);
		}
		$this->data = $this->ReportingReport->_configDataToArray($this->data);
		//pr($this->data);die();
		//$this->_processReportConfig();
		$this->ReportingReport->params = $this->params;
		$this->ReportingReport->data = $this->data;
		$this->ReportingReport->_processReportConfig();
		$this->ReportingReport->_reportParams('prep');

		if(!$this->ReportingReport->_reportParams('check')){
			$this->out('This report requires request params: ' . implode(', ',$this->ReportingReport->_reportParams()));
		} else {
			if(isset($this->ReportingReport->customQuery)){
				return $this->ReportingReport->ReportModel->query($this->ReportingReport->customQuery);
			} else {
				return $this->ReportingReport->ReportModel->find('all',$this->ReportingReport->options);
			}
		}
	}

	function email_report(){
		$results = $this->_getReportResults();
		if(!empty($results)){
			$this->Email->to = sprintf('%s <%s>','Geoff Douglas','drdouglasghd@gmail.com');
			$this->Email->template = 'report_email';
			$this->Email->subject = '[Intranet Reporting] '.$this->data['ReportingReport']['name'];
			$this->Email->set('sql_results',$results);
			$this->Email->set('report_data',$this->data);
			$this->Email->set('full_base',true);
			//$this->Email->delivery = 'debug';
			//pr($this->Email->send());
			if($this->Email->send()){
				pr($this->Email->htmlMessage);
				$this->out('Sent');
			}
		}
	}
	
	
} ?>