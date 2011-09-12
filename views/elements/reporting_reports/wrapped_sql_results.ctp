<?php 
	//pr($sql_results);
	if(is_array($sql_results)){
		
		// Search for field tokens in the template.
		preg_match_all('/{{field:.*?}}/',$this->data['ReportingReport']['config']['htmlRowBlock'],$matches);
		
		foreach($sql_results as $row){
			// Load up template for the row
			$rowContent = $this->data['ReportingReport']['config']['htmlRowBlock'];
			foreach($matches as $matchlist) foreach($matchlist as $match){
				
				//Extract Model.Field key from token
				preg_match('/{{field:(?P<model>.+)\.(?P<field>.+)}}/',$match,$fieldKey);
				$modelName = $fieldKey['model'];
				$fieldName = $fieldKey['field'];

				//Replace token with field value
				if(array_key_exists($fieldName,$row[$modelName])){
					$rowContent = preg_replace('/'.$match.'/',$row[$modelName][$fieldName],$rowContent);
				} else {
					$rowContent = preg_replace('/'.$match.'/','//Unknown Field|'.$modelName.'.'.$fieldName.'//',$rowContent);
				}
				
			}
			
			//Load content into rows array
			if(isset($this->data['ReportingReport']['config']['wrapDataRowIn']) && $this->data['ReportingReport']['config']['wrapDataRowIn'] != ''){
				$rows[] = $this->Html->tag($this->data['ReportingReport']['config']['wrapDataRowIn'],$rowContent);
			} else {
				$rows[] = $rowContent;
			}
		}
		
		//output the html to browser
		echo $this->Html->tag($this->data['ReportingReport']['config']['wrapDataSetIn'],implode("\n",$rows));
	}
?>
