<?php 
	//pr($sql_results);
	if(is_array($sql_results) && !empty($sql_results)){
		$trs = array();
		$tableData = array();
		//pr($sql_results);
		foreach($sql_results[0] as $model => $modelData){
		foreach($modelData as $fieldName => $value){
			if(!is_array($value))
				$tableData[] = Inflector::humanize($model).' '.Inflector::humanize($fieldName);
		}}
		//pr($tableData);
		$trs[] = $this->Html->tableHeaders($tableData); $tableData = array();
		
		foreach($sql_results as $row){
			$tableData = array();
			foreach($row as $model => $modelData){
			foreach($modelData as $fieldName => $value){
				if(!is_array($value))
					$tableData[] = $value;
			}}
			//pr($tableData);
			$trs[] = $this->Html->tableCells($tableData);
		}
		echo $this->Html->tag('table',implode($trs));
	}
?>
