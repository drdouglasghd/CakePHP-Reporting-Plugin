<?php
class ReportingReportsController extends ReportingAppController {

	var $name = 'ReportingReports';

	function beforeFilter(){
		parent::beforeFilter();
		if(Configure::read('Reporting.use_app_user')){
			$this->ReportingReport->setAppUser($this->appUser);
		}
		//
		// If Report Filter Submitted, convert form data to named parameters, and redirect
		//
		if(isset($this->data['Filter'])) {
			
			// Parse Url using cakes router
			$parsedUrl = Router::parse($this->data['FilterUrl']['url']);
			
			// Loop the form data and set named parameters for url
			foreach($this->data['Filter'] as $model => $fields) foreach($fields as $field => $value){
				$modelField = $model.'.'.$field;
				$paramKey = $modelField.':'.$value;
				if($value){
					// Add value to named params
					$parsedUrl['named'][$modelField] = urlencode($value);
				} elseif(!$value) {
					// If Form value empty unset named parameter
					unset($parsedUrl['named'][$modelField]);
				}
			}
			// Convert parse url to string
			$parsedUrl['url'] = array();
			$redirect = Router::reverse($parsedUrl);
			//pr($redirect);die();
			$this->redirect($redirect);
		}
	}

	function index() {
		$this->ReportingReport->recursive = 0;
		$reports = $this->paginate();
		$this->set(compact('reports'));
	}

	function view($id = null) {
		if (!$id) {
			$this->Session->setFlash(__('Invalid report', true));
			$this->redirect(array('action' => 'index'));
		}
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

		$this->_doFind();
		
		$this->set('statement',$this->ReportingReport->statement);
		
		if($this->RequestHandler->isAjax())
			$this->render('ajax_view');
	}

	function _configDataToXml(){
		if(!empty($this->data)){
			App::Import('Helper', 'Xml');
			$Xml = new XmlHelper();
			$this->data['ReportingReport']['config_data'] = $Xml->serialize(array('config'=>$this->data['ReportingReport']['config']));
		}
	}

	function add() {
		$config = $this->data['ReportingReport']['config'];
		if(isset($this->data['ReportingReport']['action']) && $this->data['ReportingReport']['action'] == 'Save'){
			$this->_configDataToXml();
			$this->ReportingReport->create();
			if ($this->ReportingReport->save($this->data)) {
				$this->Session->setFlash(__('The report has been saved', true));
				$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(__('The report could not be saved. Please, try again.', true));
			}
		} else {
			$this->data['ReportingReport']['config']['connection_ready'] = $connectionReady = false;
			if(isset($config['data_source']))
				switch($config['data_source']){
					case 'model':
						if(isset($config['model_name'])){
							$this->data['ReportingReport']['config']['connection_ready'] = $connectionReady = true;
						} else {
							$this->set('reportModels',$this->ReportingReport->getReportModels());
						}
						break;
					case 'table':
						if(isset($config['database_id']) && isset($config['table_id'])){
							$this->data['ReportingReport']['config']['connection_ready'] = $connectionReady = true;
						} else {
							$this->_setDatabases();
							if(isset($config['database_id'])) $this->_setTables();
						}
						break;
					case 'custom_query':
						if(isset($config['database_id']) && isset($this->data['ReportingReport']['custom_command'])){
							$this->data['ReportingReport']['config']['connection_ready'] = $connectionReady = true;
						} else {
							$this->_setDatabases();
						}
						break;
					default:
					
				}
			if(isset($config['data_source']) && $connectionReady){
				$this->ReportingReport->data = $this->data;
				$this->ReportingReport->_processReportConfig();
				$this->data = $this->ReportingReport->data;
				$this->set('schema',$this->ReportingReport->ReportModel->_schema);
				$this->set('recordCount',$this->ReportingReport->ReportModel->find('count'));
				if(isset($this->ReportingReport->columns)) $this->set('reportColumns',$this->ReportingReport->columns['ReportingReport']);
				if(isset($this->ReportingReport->columns)) $this->set('organicColumns',$this->ReportingReport->columns['organic']);
			}
		}
	}

	function _setDatabases(){
		$connections = get_class_vars('DATABASE_CONFIG');
		//$connList = array_keys($connections);
		foreach($connections as $conn => $config){
		  $databases[$conn] = $conn . ' - ' . $config['database'];
		}
		$this->set(compact('databases'));
	}

	function _setTables(){
		if(isset($this->data['ReportingReport']['config']['database_id']) && $this->data['ReportingReport']['config']['database_id']) {
			$db = ConnectionManager::getDataSource($this->data['ReportingReport']['config']['database_id']);
			$tables = $db->listSources();
			$tables = array_combine(array_values($tables),array_values($tables));
		}
		$this->set(compact('tables'));
	}

	function _doFind(){
		//
		// Check Params and Run Report
		//
		if(!$this->ReportingReport->_reportParams('check')){
			$this->Session->setFlash('This report requires request params: ' . implode(', ',$this->ReportingReport->_reportParams()));
		} else {			
			if(isset($this->ReportingReport->data['ReportingReport']['config']['use_paginator']) 
				&& $this->ReportingReport->data['ReportingReport']['config']['use_paginator']){
				$this->{$this->ReportingReport->ReportModel->name} = ClassRegistry::init($this->ReportingReport->ReportModel->name);
				$this->paginate[$this->ReportingReport->ReportModel->name] = $this->ReportingReport->options;
				$this->set('sql_results',$this->paginate($this->ReportingReport->ReportModel->name));
			} else {
				if(isset($this->ReportingReport->ReportModel->customQuery)){
					$this->set('sql_results',$this->ReportingReport->ReportModel->query($this->ReportingReport->ReportModel->customQuery));
				} else {
					$this->set('sql_results',$this->ReportingReport->ReportModel->find('all',$this->ReportingReport->options));
				}
			}
		}
		$this->ReportingReport->_reportParams('prep');	
	}

	function edit($id = null) {
		if (!$id && empty($this->data)) {
			$this->Session->setFlash(__('Invalid report', true));
			$this->redirect(array('action' => 'index'));
		}
		if(isset($this->data['ReportingReport']['action']) && $this->data['ReportingReport']['action'] == 'Save'){
			$this->_configDataToXml();
			if ($this->ReportingReport->save($this->data)) {
				$this->Session->setFlash(__('The report has been saved', true));
				$this->redirect(array('action' => 'view',$this->data['ReportingReport']['id']));
			} else {
				$this->Session->setFlash(__('The report could not be saved. Please, try again.', true));
			}
		} else {
			if(empty($this->data)){
				$this->data = $this->ReportingReport->read(null, $id);
				$this->data = $this->ReportingReport->_configDataToArray($this->data);
			}
			
			//$this->_processReportConfig();

			$this->ReportingReport->params = $this->params;
			$this->ReportingReport->data = $this->data;
			$this->ReportingReport->_processReportConfig();
			$this->data = $this->ReportingReport->data;
			
			if(isset($this->ReportingReport->columns)) $this->set('reportColumns',$this->ReportingReport->columns['ReportingReport']);
			if(isset($this->ReportingReport->columns)) $this->set('organicColumns',$this->ReportingReport->columns['organic']);

			$this->set('reportModels',$this->ReportingReport->getReportModels());
			$this->set('schema',$this->ReportingReport->ReportModel->_schema);
			$this->set('recordCount',$this->ReportingReport->ReportModel->find('count'));
			
			if((isset($this->data['ReportingReport']['action']) 
				&& $this->data['ReportingReport']['action'] == 'Test')){
					$this->set('statement',$this->ReportingReport->ReportModel->statement);
					$this->_doFind();
			}
	
			$this->_setDatabases();
			$this->_setTables();
		}
	}

	function delete($id = null) {
		if (!$id) {
			$this->Session->setFlash(__('Invalid id for report', true));
			$this->redirect(array('action'=>'index'));
		}
		if ($this->ReportingReport->delete($id)) {
			$this->Session->setFlash(__('Report deleted', true));
			$this->redirect(array('action'=>'index'));
		}
		$this->Session->setFlash(__('Report was not deleted', true));
		$this->redirect(array('action' => 'index'));
	}
}
?>