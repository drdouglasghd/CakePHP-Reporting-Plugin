<?php
class ReportingReport extends ReportingAppModel {
	var $name = 'Report';
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
		//pr($this->data); die();
		if(isset($this->data['Report']['profile_id']) && $this->data['Report']['profile_id'] == false){
			$this->data['Report']['profile_id'] = parent::$activeUser['User']['active_profile'];
			$this->data['Report']['user_id'] = parent::$activeUser['User']['id'];
		}
	}

	function beforeFind($qd){
		if(isset(parent::$activeUser['User']['active_profile'])){
			if(!$this->appAdmin){
				$qd['conditions'][] = array('Report.profile_id'=>parent::$activeUser['User']['active_profile']);
			}
		}	
		return $qd;
	}

	function _urlKeyFromName(){
    //if(!isset($this->data['Report']['id'])){
      if(!isset($this->data['Report']['url_key']) || !isset($this->data['Report']['id']) ||
          (isset($this->data['Report']['url_key']) && $this->data['Report']['url_key'] == '')){
        $this->data['Report']['url_key'] = strtolower(Inflector::slug($this->data['Report']['name'],'-'));
        $UrlKeyCheck = $this->findByUrlKey($this->data['Report']['url_key']);
        if(!empty($UrlKeyCheck)){
          $this->data['Report']['url_key'] .= '-'.rand(1111,9999);
        }
      }
    //}
	}
	
	function _configDataToArray($data){
		//pr('config2array');
		$this->data = $data;
		if(!isset($this->data['Report']['config'])) {
			//pr($this->data);
			App::import('Xml');
			//pr($this->data);
			$xml = new Xml($this->data['Report']['config_data']);
			//pr($xml->toArray());die();
			$configArr = Set::reverse($xml->toArray());
			if(isset($configArr['Config'])){ $this->data['Report']['config'] = $configArr['Config']; } else {
				//$this->Session->setFlash(__('The Report Config Data is Corrupt.', true));
				//$this->redirect(array('controller'=>'reports'));
				return false;
			}
			if(isset($this->data['Report']['config']['Conditions'])){
				$this->data['Report']['config']['conditions'] = $this->data['Report']['config']['Conditions'];
				unset($this->data['Report']['config']['Conditions']);
			}
			if(isset($this->data['Report']['config']['ReportFilters'])){
				//$this->data['Report']['config']['report_filters'] = $this->data['Report']['config']['ReportFilters'];
				if(isset($this->data['Report']['config']['ReportFilters']['field_name'])){
					$this->data['Report']['config']['report_filters'] = array();
					$this->data['Report']['config']['report_filters'][] = $this->data['Report']['config']['ReportFilters'];
				} else {
					$this->data['Report']['config']['report_filters'] = $this->data['Report']['config']['ReportFilters'];
				}
				unset($this->data['Report']['config']['ReportFilters']);
			}
			if(isset($this->data['Report']['config']['DefinedColumns'])){
				if(isset($this->data['Report']['config']['DefinedColumns']['label'])){
					$this->data['Report']['config']['defined_columns'] = array();
					$this->data['Report']['config']['defined_columns'][] = $this->data['Report']['config']['DefinedColumns'];
				} else {
					$this->data['Report']['config']['defined_columns'] = $this->data['Report']['config']['DefinedColumns'];
				}
				unset($this->data['Report']['config']['DefinedColumns']);
			}
			//pr($this->data);die();
			return $this->data;
		}
	}
	
	function _processReportConfig(){
		if(isset($this->data['Report']['config']['model_name'])){
			$this->ReportModel = ClassRegistry::init($this->data['Report']['config']['model_name']);
			//$this->set('schema',$this->ReportModel->_schema);
			//$this->set('recordCount',$this->ReportModel->find('count'));
		} else {
			$this->setDataSource($this->data['Report']['config']['database_id']);
			if(isset($this->data['Report']['config']['table_id']) && $this->data['Report']['config']['table_id'] != ''){
			  $this->setSource($this->data['Report']['config']['table_id']);
			  //$this->set('schema',$this->Report->_schema);
			  //$this->set('recordCount',$this->Report->find('count'));
			}
			$this->ReportModel = $this;
		}
        if(isset($this->data['Report']['custom_command']) && $this->data['Report']['custom_command']){
        	return $this->ReportModel->query($this->data['Report']['custom_command']);
        } else {
          $this->options = array('limit'=>20);
          if(isset($this->data['Report']['config']['fieldList']) && $this->data['Report']['config']['fieldList']){
            $fieldList = explode(',',$this->data['Report']['config']['fieldList']);
            foreach($fieldList as $field) $this->options['fields'][] = trim($field);
            
			// Build Conditions  
			if(isset($this->data['Report']['config']['conditions']['field_id'])){
				$condss = $this->data['Report']['config']['conditions'];
				unset($this->data['Report']['config']['conditions']);
				$this->data['Report']['config']['conditions'][] = $condss;
			}

			//
			//Build Conditions from array
			//
			if(isset($this->data['Report']['config']['conditions']) && !empty($this->data['Report']['config']['conditions']))
			foreach($this->data['Report']['config']['conditions'] as $condKey => $cond){
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
					unset($this->data['Report']['config']['conditions'][$condKey]);
				}
			} // /conditions array loop
			
			//
			// Report Options
			//
			if(isset($this->data['Report']['config']['limit']) && $this->data['Report']['config']['limit']){
				$this->options['limit'] = $this->data['Report']['config']['limit'];
			}
			if(isset($this->data['Report']['config']['order']) && $this->data['Report']['config']['order']){
				$orderbys = explode(',',$this->data['Report']['config']['order']);
				foreach($orderbys as $ordby){$orderbylist[] = trim($ordby);}
				$this->options['order'] = $orderbylist;
				}
			if(isset($this->data['Report']['config']['group']) && $this->data['Report']['config']['group']){
				$groupbys = explode(',',$this->data['Report']['config']['group']);
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
				$this->columns['report'] = $reportColumns;
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
					'group' => null,
					'order' => null
				);
			$stmtOptions = $stmtOptions + $this->options;
			$this->statement = $dbo->expression($dbo->buildStatement($stmtOptions,$this->ReportModel));
		}
	}
	
	function _reportParams($callType = 'list'){
		if(isset($this->data['Report']['config']['params']) && !empty($this->data['Report']['config']['params'])){
			$params = explode(',',$this->data['Report']['config']['params']);
			foreach($params as $param) $paramList[] = trim($param);
			switch ($callType) {
				case 'list':
					return $paramList;
					break;
				case 'prep':
					$this->data['Report']['config']['params'] = $paramList;
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