<?php 
	//pr($sql_results);
	if(is_array($sql_results) && $this->data['ReportingReport']['config']['renderAs'] == 'defined_columns'
		&& isset($this->data['ReportingReport']['config']['defined_columns']) && !empty($this->data['ReportingReport']['config']['defined_columns'])){
		
		//pr($this->data['ReportingReport']['config']['defined_columns']);
		// Search for field tokens in the template.
		//preg_match_all('/{{field:.*?}}/',$this->data['ReportingReport']['config']['htmlRowBlock'],$matches);
		
		//$this->data['ReportingReport']['config']['defined_columns']
		unset($this->data['ReportingReport']['config']['defined_columns']['@']);
		// Build Header from Labels
		foreach($this->data['ReportingReport']['config']['defined_columns'] as $column){
			$ths[] = $this->Html->tag('th',$column['label']);
		}
		$rows[] = $this->Html->tag('tr',implode($ths)); $ths = array();

		foreach($sql_results as $row){
			// Load up template for the row
			//$rowContent = $this->data['ReportingReport']['config']['htmlRowBlock'];
			
			foreach($this->data['ReportingReport']['config']['defined_columns'] as $column){
				// Build each row
				preg_match_all('/{{field:.*?}}/',$column['column_content'],$matches);
				foreach($matches as $matchlist) foreach($matchlist as $match){
					
					//Extract Model.Field key from token
					preg_match('/{{field:(?P<model>.+)\.(?P<field>.+)}}/',$match,$fieldKey);
					$modelName = $fieldKey['model'];
					$fieldName = $fieldKey['field'];

					//Replace token with field value
					if(array_key_exists($fieldName,$row[$modelName])){
						$column['column_content'] = preg_replace('/'.$match.'/',$row[$modelName][$fieldName],$column['column_content']);
					} else {
						$column['column_content'] = preg_replace('/'.$match.'/','No Data: '.$modelName.'.'.$fieldName,$column['column_content']);
					}
				}
				$tds[] = $this->Html->tag('td',$column['column_content']);
			} 
			//Load content into rows array
			
			$rows[] = $this->Html->tag('tr',implode($tds)); $tds = array();

		}
		
		//output the html to browser
		echo $this->Html->tag('table',implode("\n",$rows)); $rows = array();
	}
?>
