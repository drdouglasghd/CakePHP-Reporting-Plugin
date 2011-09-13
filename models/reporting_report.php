<?php
class ReportingReport extends ReportingAppModel {
	var $name = 'ReportingReport';
	var $useTable = 'reports';
	var $reportModels = array(
		'exoticsshop' => array(
			'Vehicle',
			'MaintenanceLog',
			'VehiclePart',
			'Supplier',
			'VehiclePlatform',
			'ShopInventoryAdjustment',
			'ServiceTask'
		)
	);
	var $validate = array(
		'name' => array(
			'notempty' => array(
				'rule' => array('notempty'),
				//'message' => 'Your custom message here',
				//'allowEmpty' => false,
				//'required' => false,
				//'last' => false, // Stop validation after this rule
				//'on' => 'create', // Limit validation to 'create' or 'update' operations
			),
		),
		'url_key' => array(
			'notempty' => array(
				'rule' => array('notempty'),
				//'message' => 'Your custom message here',
				//'allowEmpty' => false,
				//'required' => false,
				//'last' => false, // Stop validation after this rule
				//'on' => 'create', // Limit validation to 'create' or 'update' operations
			),
		),
	);

	function getReportModels(){
		if(isset($this->reportModels[Configure::read('App.id')])){
			return $this->reportModels[Configure::read('App.id')];
		} else {
			return array_combine(App::objects('model'),App::objects('model'));
		}
	}

	function beforeValidate(){
		$this->_urlKeyFromName();
		if(!isset($this->data['ReportingReport']['id']) && isset(AppModel::$activeUser)){
			$this->data['ReportingReport']['profile_id'] = AppModel::$activeUser['User']['active_profile'];
			$this->data['ReportingReport']['user_id'] = AppModel::$activeUser['User']['id'];
		}
	}

	function beforeFind($qd){
		if(isset(AppModel::$activeUser)){
			if(!$this->appAdmin){
				$qd['conditions'][] = array('ReportingReport.profile_id'=>AppModel::$activeUser['User']['active_profile']);
			}
		}	
		return $qd;
	}

	function _urlKeyFromName(){
    //if(!isset($this->data['ReportingReport']['id'])){
      if(!isset($this->data['ReportingReport']['url_key']) || !isset($this->data['ReportingReport']['id']) ||
          (isset($this->data['ReportingReport']['url_key']) && $this->data['ReportingReport']['url_key'] == '')){
        $this->data['ReportingReport']['url_key'] = strtolower(Inflector::slug($this->data['ReportingReport']['name'],'-'));
        $UrlKeyCheck = $this->findByUrlKey($this->data['ReportingReport']['url_key']);
        if(!empty($UrlKeyCheck)){
          $this->data['ReportingReport']['url_key'] .= '-'.rand(1111,9999);
        }
      }
    //}
	}
	
	function _configDataToArray($data){
		//pr('config2array');
		$this->data = $data;
		if(!isset($this->data['ReportingReport']['config'])) {
			//pr($this->data);
			App::import('Xml');
			//pr($this->data);
			$xml = new Xml($this->data['ReportingReport']['config_data']);
			//pr($xml->toArray());die();
			$configArr = Set::reverse($xml->toArray());
			if(isset($configArr['Config'])){ $this->data['ReportingReport']['config'] = $configArr['Config']; } else {
				//$this->Session->setFlash(__('The Report Config Data is Corrupt.', true));
				//$this->redirect(array('controller'=>'reports'));
				return false;
			}
			if(isset($this->data['ReportingReport']['config']['Conditions'])){
				$this->data['ReportingReport']['config']['conditions'] = $this->data['ReportingReport']['config']['Conditions'];
				unset($this->data['ReportingReport']['config']['Conditions']);
			}
			if(isset($this->data['ReportingReport']['config']['ReportFilters'])){
				//$this->data['ReportingReport']['config']['report_filters'] = $this->data['ReportingReport']['config']['ReportFilters'];
				if(isset($this->data['ReportingReport']['config']['ReportFilters']['field_name'])){
					$this->data['ReportingReport']['config']['report_filters'] = array();
					$this->data['ReportingReport']['config']['report_filters'][] = $this->data['ReportingReport']['config']['ReportFilters'];
				} else {
					$this->data['ReportingReport']['config']['report_filters'] = $this->data['ReportingReport']['config']['ReportFilters'];
				}
				unset($this->data['ReportingReport']['config']['ReportFilters']);
			}
			if(isset($this->data['ReportingReport']['config']['DefinedColumns'])){
				if(isset($this->data['ReportingReport']['config']['DefinedColumns']['label'])){
					$this->data['ReportingReport']['config']['defined_columns'] = array();
					$this->data['ReportingReport']['config']['defined_columns'][] = $this->data['ReportingReport']['config']['DefinedColumns'];
				} else {
					$this->data['ReportingReport']['config']['defined_columns'] = $this->data['ReportingReport']['config']['DefinedColumns'];
				}
				unset($this->data['ReportingReport']['config']['DefinedColumns']);
			}
			//pr($this->data);die();
			return $this->data;
		}
	}
	
	function _processReportConfig(){
		if(isset($this->data['ReportingReport']['config']['model_name'])){
			$this->ReportModel = ClassRegistry::init($this->data['ReportingReport']['config']['model_name']);
			//$this->set('schema',$this->ReportModel->_schema);
			//$this->set('recordCount',$this->ReportModel->find('count'));
		} else {
			$this->setDataSource($this->data['ReportingReport']['config']['database_id']);
			if(isset($this->data['ReportingReport']['config']['table_id']) && $this->data['ReportingReport']['config']['table_id'] != ''){
			  $this->setSource($this->data['ReportingReport']['config']['table_id']);
			  //$this->set('schema',$this->Report->_schema);
			  //$this->set('recordCount',$this->Report->find('count'));
			}
			$this->ReportModel = $this;
		}
        if(isset($this->data['ReportingReport']['custom_command']) && $this->data['ReportingReport']['custom_command']){
			$this->ReportModel->customQuery = $this->ReportModel->statement = $this->data['ReportingReport']['custom_command'];
			$results = $this->ReportModel->query($this->ReportModel->customQuery);
			if(isset($results[0])){
				foreach($results[0] as $model => $fields) {
					foreach($fields as $field => $fieldvalue){
						$reportColumns[$model.'.'.$field] = $model.'.'.$field;
					}
				}
				$this->columns['ReportingReport'] = $reportColumns;
				$this->columns['organic'] = $reportColumns;	
			}
			//return $this->ReportModel->query($this->data['ReportingReport']['custom_command']);
        } else {
          $this->options = array('limit'=>20);
          if(isset($this->data['ReportingReport']['config']['fieldList']) && $this->data['ReportingReport']['config']['fieldList']){
            $fieldList = explode(',',$this->data['ReportingReport']['config']['fieldList']);
            foreach($fieldList as $field) $this->options['fields'][] = trim($field);
            
			// Build Conditions  
			if(isset($this->data['ReportingReport']['config']['conditions']['field_id'])){
				$condss = $this->data['ReportingReport']['config']['conditions'];
				unset($this->data['ReportingReport']['config']['conditions']);
				$this->data['ReportingReport']['config']['conditions'][] = $condss;
			}

			//
			//Build Conditions from array
			//
			if(isset($this->data['ReportingReport']['config']['conditions']) && !empty($this->data['ReportingReport']['config']['conditions']))
			foreach($this->data['ReportingReport']['config']['conditions'] as $condKey => $cond){
				if($cond['field_id'] == 'custom_where_block'){
					// replace param tokens in where
					preg_match_all('/{{param:.*?}}/',$cond['where_block'],$matches);
					foreach($matches as $matchlist) foreach($matchlist as $match){
					
						$matchParam = preg_replace(array('/{{param:/','/}}/'),array('',''),$match);
						
						if(isset($this->params['named'][$matchParam]) || isset($this->params['url'][$matchParam])){
							if(isset($this->params['named'][$matchParam])) $param = $this->params['named'][$matchParam];
							if(isset($this->params['url'][$matchParam])) $param = $this->params['url'][$matchParam];
							$cond['where_block'] = preg_replace('/'.$match.'/',$param,$cond['where_block']);
						}
					}
					$this->options['conditions'][] = $cond['where_block'];

				} elseif($cond['value'] && $cond['field_id']) {
					$this->options['conditions'][] = array(
						$cond['operator']?$cond['field_id'].' '.$cond['operator']:$cond['field_id'] => $cond['value']
					);
				} elseif(!$cond['field_id']){
					unset($this->data['ReportingReport']['config']['conditions'][$condKey]);
				}
			} // /conditions array loop
			
			//
			// Report Options
			//
			if(isset($this->data['ReportingReport']['config']['limit']) && $this->data['ReportingReport']['config']['limit']){
				$this->options['limit'] = $this->data['ReportingReport']['config']['limit'];
			}
			if(isset($this->data['ReportingReport']['config']['order']) && $this->data['ReportingReport']['config']['order']){
				$orderbys = explode(',',$this->data['ReportingReport']['config']['order']);
				foreach($orderbys as $ordby){$orderbylist[] = trim($ordby);}
				$this->options['order'] = $orderbylist;
				}
			if(isset($this->data['ReportingReport']['config']['group']) && $this->data['ReportingReport']['config']['group']){
				$groupbys = explode(',',$this->data['ReportingReport']['config']['group']);
				foreach($groupbys as $grpby){$groupbylist[] = trim($grpby);}
				$this->options['group'] = $groupbylist;
			}
			
			//
			// Retrieve First Record and Collect Models and Field Names
			//
			$this->{$this->ReportModel->name} = ClassRegistry::init($this->ReportModel->name);
			$row = $this->{$this->ReportModel->name}->find('first');
			if(!empty($row)){
				foreach($row as $model => $fields) {
					foreach($fields as $field => $fieldvalue){
						if($this->ReportModel->name == $model || 
							($this->ReportModel->name != $model 
								&& $this->ReportModel->{$model}->useDbConfig == $this->ReportModel->useDbConfig))
								$organicColumns[$model.'.'.$field] = $model.'.'.$field;
						$reportColumns[$model.'.'.$field] = $model.'.'.$field;
					}
				}
				//$this->set(compact('reportColumns','organicColumns'));
				$this->columns['ReportingReport'] = $reportColumns;
				$this->columns['organic'] = $organicColumns;	
			}
			//
			// Add conditions from filter
			//
			if(!empty($this->params['named'])){
				foreach($this->params['named'] as $modelField => $value){
					if(isset($organicColumns[$modelField])){
						$this->options['conditions'][] = array(urldecode($modelField).' '.urldecode($value));
					}
				}
			}

		  }
			$dbo = $this->ReportModel->getDataSource();
			$stmtOptions = array(
					'table' => $dbo->fullTableName($this->ReportModel),
					'alias' => $this->ReportModel->name,
					'offset' => null,
					'joins' => array(),
					'conditions' => array(),
					'fields' => array(),
					'group' => '',
					'order' => ''
				);
			$stmtOptions = array_merge($stmtOptions,$this->options);
			$this->statement = $dbo->buildStatement($stmtOptions,$this->ReportModel);
		} // Not Custom
	}
	
	function _reportParams($callType = 'list'){
		if(isset($this->data['ReportingReport']['config']['params']) && !empty($this->data['ReportingReport']['config']['params'])){
			$params = explode(',',$this->data['ReportingReport']['config']['params']);
			foreach($params as $param) $paramList[] = trim($param);
			switch ($callType) {
				case 'list':
					return $paramList;
					break;
				case 'prep':
					$this->data['ReportingReport']['config']['params'] = $paramList;
					return true;
					break;
				case 'check':
					$missingParams = 0;
					foreach($paramList as $param){
						if(!isset($this->params['named'][$param]) && !isset($this->params['url'][$param])) $missingParams++;
					}
					if($missingParams > 0){ return false; } else { return true; }
					break;
			}
		} else {
			return true;
		}
	}

	
}
?>