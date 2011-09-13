<?php
	//pr($this->data);
	$reportDataInputs = array();

	pr($this->data['ReportingReport']);
	
	if(isset($this->data['ReportingReport']['config']))
	foreach($this->data['ReportingReport']['config'] as $key => $value){
		if(!$value) unset($this->data['ReportingReport']['config'][$key]);
		if($value && !is_array($value)) 
			echo $this->Form->hidden('ReportingReport.config.'.$key,array('value'=>$value,'id'=>'ReportConfigDefault'.$key));
	}

	if(isset($this->data['ReportingReport']['config']))
		extract($this->data['ReportingReport']['config']);

	//pr($this->data['ReportingReport']);
	
	
	//echo isset($data_source)?$data_source:'';
	
	if(!isset($data_source)){
		echo $this->Form->input('ReportingReport.config.data_source',array(
				'empty'=>'(Choose Data Source)',
				'type'=>'select',
				'onchange'=>"loadingText(); if($(this).val()) $(this).closest('form').submit()",
				'options'=>array(
					'model'=>'Defined Data Model',
					'table'=>'Database Table',
					'custom_query'=>'Custom SQL Query'
				)));
	} else {
		$defaultname = array();
		switch($data_source){
			case 'model':
				if(!isset($model_name)){
					echo $this->Form->input('ReportingReport.config.model_name',array(
						   'type'=>'select','options'=>$reportModels,'empty'=>'(Choose Data Model)',
						   'onchange'=>"loadingText(); if($(this).val()) $(this).closest('form').submit()"));
				} else {
					$defaultname[] = Inflector::humanize($this->data['ReportingReport']['config']['model_name']);
				}
				break;
			case 'table':
				if(!isset($database_id)){
					echo $this->Form->input('ReportingReport.config.database_id',array(
						   'type'=>'select','options'=>$databases,'empty'=>'(Choose Database)',
						   'onchange'=>"loadingText(); if($(this).val()) $(this).closest('form').submit()"));
				} elseif(!isset($table_id)){
					echo $this->Form->input('ReportingReport.config.table_id',array(
					   'type'=>'select','options'=>$tables,'empty'=>'(Choose Table)',
					   'onchange'=>"loadingText(); if($(this).val()) $(this).closest('form').submit()"));
				} else {
					$defaultname[] = Inflector::humanize($this->data['ReportingReport']['config']['database_id']);
					$defaultname[] = Inflector::humanize($this->data['ReportingReport']['config']['table_id']);
				}
				break;
			case 'custom_query':
				echo $this->Form->input('custom_command',array('class'=>'custom_command_input'));
				if(!isset($database_id)){
					echo $this->Form->input('ReportingReport.config.database_id',array(
						   'type'=>'select','options'=>$databases,'empty'=>'(Choose Database)',
						   'onchange'=>"loadingText(); if($(this).val()) $(this).closest('form').submit()"));
				} elseif(!isset($this->data['ReportingReport']['custom_command'])) {
					echo $this->Form->submit('Load Report Columns',array('name'=>'data[Report][action]','value'=>'load'));
				}
				$defaultname[] = Inflector::humanize('custom query');
				break;
		}
	}

	//if(!empty($reportDataInputs))
		//echo $this->Html->tag('fieldset',$this->Html->tag('legend','Report Connection and Data').$this->Html->div('collapsible-fieldset',implode($reportDataInputs)),array('class'=>'report_connection'));

		 
    if(isset($connection_ready) && $connection_ready) {

		 $fields = array_combine(array_keys($schema),array_keys($schema));

		// Get Name 
		echo $this->Form->input('name',array('default'=>implode(' ',$defaultname)));
		echo $this->Form->input('url_key',array('default'=>Inflector::slug(implode(' ',$defaultname))));
		 
		// Get Fields
		if($data_source != 'custom_query')
		echo $this->Form->input('ReportingReport.config.fieldList',array('default'=>'*',
			'label'=>'Fields to Select','div'=>'required','after'=>'Use comma separated list. (SQL fragments are ok)'));

		// Track param dependecies 
		echo $this->Form->input('ReportingReport.config.params',array(
			'after'=>'List all named params report is dependent on. Use comma separated list.'));


		if(!isset($reportColumns)){ 
			echo $this->Form->submit('Load Report Columns',array('name'=>'data[Report][action]','value'=>'load'));
		}

		// Backwards compatability for original conditions structure, and XML parsing
		if(isset($this->data['ReportingReport']['config']['conditions']['field_id'])){
			$condss = $this->data['ReportingReport']['config']['conditions'];
			unset($this->data['ReportingReport']['config']['conditions']);
			$this->data['ReportingReport']['config']['conditions'][] = $condss;
		}

		// Backwards compatability for XML parse for one element arrays.
		if(isset($this->data['ReportingReport']['config']['report_filters']['field_name'])){
			$condss = $this->data['ReportingReport']['config']['report_filters'];
			unset($this->data['ReportingReport']['config']['report_filters']);
			$this->data['ReportingReport']['config']['report_filters'][] = $condss;
		}
	
		if($data_source != 'custom_query'){
			// Get Order List
			$reportOptions = $this->Form->input('ReportingReport.config.order',array(
				'after'=>'Use comma separated list. Use space for direction. (i.e. created DESC, username ASC)(SQL Fragments OK)'));

			// Get Group List
			$reportOptions .= $this->Form->input('ReportingReport.config.group',array(
				'after'=>'Use comma separated list. (SQL Fragments OK)'));

			$reportOptions .= $this->Form->input('ReportingReport.config.limit',array(
				'after'=>'Use simple interger. Defaults to 20.'));

			$reportOptions .= $this->Form->input('ReportingReport.config.use_paginator',array(
				'after'=>' (Use automatic paginator)','type'=>'checkbox'));
			
			echo $this->Html->tag('fieldset',$this->Html->tag('legend','Report Options').$this->Html->div('collapsible-fieldset',$reportOptions),array('class'=>'report_options'));

		}
		// Submit buttons
		if(isset($reportColumns)){
			// Conditions builder
			$divs = '';
			
			if($data_source != 'custom_query'){
				$conditionColumns = $organicColumns;
				array_unshift($conditionColumns,array('custom_where_block'=>'Where Block'));

				// Pre load the conditions array with a template key, to force at least one loop for template creation
				$this->data['ReportingReport']['config']['conditions']['@'] = array();
				
				if(isset($this->data['ReportingReport']['config']['conditions']) && !empty($this->data['ReportingReport']['config']['conditions'])){
					$cc = 0; //$cc used to reset condition index
					foreach($this->data['ReportingReport']['config']['conditions'] as $cid => $cond){
						
						// Reset index if not template
						if($cid != '@' && !empty($cond)) $cid == $cc;
						
						// Build condition form
						$content = $this->Form->input('ReportingReport.config.conditions.'.$cid.'.field_id',array(
							'class'=>'field_id-handle conditional-field',
							'type'=>'select','options'=>$conditionColumns,'empty'=>'(Choose Field or Where Block)',
							'after'=>'Leave Blank to Not Filter.'
							));
						$content .= $this->Form->input('ReportingReport.config.conditions.'.$cid.'.where_block',array(
							'div'=>'where_input','class'=>'conditional-field','after'=>'omit WHERE, it is assumed'));
						$content .= $this->Form->input('ReportingReport.config.conditions.'.$cid.'.operator',array(
							'div'=>'dependent-field_id','class'=>'conditional-field report_config_condition_operator','after'=>' = (default), LIKE, IN, NOT, <>, etc...'));
						$content .= $this->Form->input('ReportingReport.config.conditions.'.$cid.'.value',array(
							'div'=>'dependent-field_id report_config_condition_value_div','class'=>'conditional-field report_config_condition_value','after'=>'For LIKE, use % wildcards. For IN use (1,2,nn) syntax.'));
						$content .= $this->Html->link('Remove Condition',array('#remove-condition'),array('class'=>'condition-remove-handle'));

						if($cid == '@' && empty($cond)){
							$conditionInputDiv = $this->Html->tag('div',$content,array(
								'class'=>'report-condition','id'=>'newestCondition','style'=>'border:1px #ddd solid; padding-left:1em;'));
						} else {
							// Append report conditions into conditions div
							$divs .= $this->Html->tag('div',$content,array('class'=>'report-condition','id'=>'report-condition_'.$cid,'style'=>'border:1px #ddd solid; padding-left:1em;'));
						}
						$content = ''; // clear content variable
						$cc++; // Increment condition array index
					}
				}
				
				$divs .= $this->Html->link('Add Condition',array('#add-condition'),array('class'=>'add_condition_handle'));
						
				// Wrap Conditions in Fieldset
				echo $this->Html->tag('fieldset',$this->Html->tag('legend','Report Conditions').$this->Html->div('collapsible-fieldset',$divs),array('class'=>'report_conditions'));

			
				//
				// Create Report Filters
				//
				$filters = isset($this->data['ReportingReport']['config']['report_filters'])?$this->data['ReportingReport']['config']['report_filters']:array();
				$filters['@'] = array();
				$ff = 0; $divs = '';
				foreach($filters as $fid => $filter){
					if($fid != '@' && !empty($filter)) $fid == $ff;
					$content = $this->Form->input('ReportingReport.config.report_filters.'.$fid.'.field_name',array('options'=>$organicColumns,
						'type'=>'select','label'=>'Field for Filter','after'=>'Field for dynamic report filtering.','class'=>'report-filter-field',
						'value'=>isset($filters[$fid]['field_name'])?$filters[$fid]['field_name']:'',
						'default'=>isset($filters[$fid]['field_name'])?$filters[$fid]['field_name']:''
					));
					$content .= $this->Html->link('Remove Filter',array('#remove-filter'),array('class'=>'filter-remove-handle'));
					$ff++;
					if($fid == '@' && empty($filter)){
						$filterInputDiv = $this->Html->tag('div',$content,array(
							'class'=>'report-filter','id'=>'newestFilter','style'=>'border:1px #ddd solid; padding-left:1em;'));
					} else {
						// Append report conditions into conditions div
						$divs .= $this->Html->tag('div',$content,array('class'=>'report-filter','id'=>'report-filter_'.$fid,'style'=>'border:1px #ddd solid; padding-left:1em;'));
					}				
				}
				$divs .= $this->Html->link('Add Filter',array('#add-filter'),array('class'=>'add_filter_handle'));
				echo $this->Html->tag('fieldset',$this->Html->tag('legend','Report Filters').$this->Html->div('collapsible-fieldset',$divs),array('class'=>'report_filters'));

			} // Not Custom Query
			
			// Report Fields
			$reportCols = $this->Form->input('ReportingReport.config.renderAs',array(
				'options'=>array(
					'auto_table'=>'Fully-Auto Table Format',
					'wrapped_html'=>'Wrapped Html Blocks',
					'defined_columns'=>'Defined Columns'
				),
				'default'=>'auto_table',
				'class'=>'renderAsHandle'
			));
			
			// Pre load the defined columns array with a template key, to force at least one loop for template creation
			$this->data['ReportingReport']['config']['defined_columns']['@'] = array();
			$definedColumnDivs = '';
			if(isset($this->data['ReportingReport']['config']['defined_columns']) && !empty($this->data['ReportingReport']['config']['defined_columns'])){
				$cc = 0; //$cc used to reset condition index
				foreach($this->data['ReportingReport']['config']['defined_columns'] as $cid => $column){
					
					// Reset index if not template
					if($cid != '@' && !empty($column)) $cid == $cc;
					
					// Build condition form
					$content = $this->Form->input('ReportingReport.config.defined_columns.'.$cid.'.label',array(
						'div'=>'render-dependent renderAs_defined_columns',
						'class'=>'report-column column-label'
						));
					$content .= $this->Form->input('ReportingReport.config.defined_columns.'.$cid.'.column_content',array(
						'div'=>'render-dependent renderAs_defined_columns','type'=>'textarea',
						'class' => 'plain-text report-column column-content','spellcheck'=>'false',
						'after'=>'Html Template Block - Use {{field:nnnnn}} tokens to insert row values'
						));
					$content .= $this->Form->hidden('ReportingReport.config.defined_columns.'.$cid.'.column_seq',array('rel'=>'report-column-seq','value'=>$cid+1));
					$links[] = $this->Html->link('Complete',array('#complete-column'),array('class'=>'column-complete-handle'));
					$links[] = $this->Html->link('Remove',array('#remove-column'),array('class'=>'column-remove-handle'));
					$links[] = $this->Form->input('FieldSearch.reportColumns',array(
						'empty'=>'(Choose Field to Insert Token)','options'=>$reportColumns,
						'type'=>'select','div'=>false,'label'=>false,'class'=>'column-token-handle'));
					$content .= $this->Html->div('render-dependent renderAs_defined_columns',implode(' - ',$links)); $links = array();
					
					if($cid == '@' && empty($column)){
						$columnsInputDiv = $this->Html->tag('div',$content,array(
							'class'=>'report-column render-dependent renderAs_defined_columns','id'=>'newestColumn','style'=>'border:1px #ddd solid; padding-left:1em;'));
					} else {
						// Append report conditions into conditions div
						$definedColumnDivs .= $this->Html->tag('div',$content,array(
							'class'=>'report-column render-dependent renderAs_defined_columns','id'=>'report-column_'.$cid,'style'=>'border:1px #ddd solid; padding-left:1em;'
						));
					}
					$content = ''; // clear content variable
					$cc++; // Increment condition array index
				} // foreach column
				$definedColumnDivs = $this->Html->div('report-column-list',$definedColumnDivs);
			}
			$definedColumnDivs .= $this->Form->input('FieldSearch.reportColumns',array(
						'empty'=>'(Choose Field to Field to Create Field From)','options'=>$reportColumns,
						'type'=>'select','div'=>false,'label'=>false,'class'=>'add-column-dropdown render-dependent renderAs_defined_columns'));
			$definedColumnDivs .= $this->Html->link('Add Column',array('#add-column'),array(
				'class'=>'add_column_handle render-dependent renderAs_defined_columns'));
			
			$wrappedForm = $this->Form->input('ReportingReport.config.wrapDataSetIn',array('label'=>'Data Set Wrapper Tag',
				'after'=>'<strong>Html Tag Name</strong> to Wrap the entire set in (EXAMPLE: use ul for <code>'.htmlentities('<ul></ul>').'</code>)',
				'div'=>'render-dependent renderAs_wrapped_html'
			));
			
			$wrappedForm .= $this->Form->input('ReportingReport.config.wrapDataRowIn',array('label'=>'Row Wrapper Tag',
				'after'=>'<strong>Html Tag Name</strong> to Wrap each row in (EXAMPLE: use li for <code>'.htmlentities('<li></li>').'</code>)','div'=>'render-dependent renderAs_wrapped_html'
			));
			
			$wrappedForm .= $this->Form->input('ReportingReport.config.htmlRowBlock',array('type'=>'textarea','class'=>'accept-field-tokens plain-text','spellcheck'=>'false',
				'label'=>'Row Template (Html Block)',
				'after'=>'Html Template Block - Use {{field:nnnnn}} tokens to insert row values','div'=>'render-dependent renderAs_wrapped_html'
			));
			
			$wrappedForm .= $this->Form->input('FieldSearch.reportColumns',array(
				'type'=>'text','label'=>'Insert Field Template Tokens',
				'after'=>'Search and Insert field Token into Html Block',
				'div'=>'render-dependent renderAs_wrapped_html'
			));
			
			echo $this->Html->tag('fieldset',$this->Html->tag('legend','Report Rows & Columns').$this->Html->div('collapsible-fieldset',$reportCols.$definedColumnDivs.$wrappedForm),array('class'=>'report_columns'));

			// Create Before and After Render Blocks 
			$settingsForm = $this->Form->input('ReportingReport.config.beforeReportBlock',array(
					'type'=>'textarea','label'=>'Before Report Code Block','after'=>'Anything that needs to be before the report. This is the first thing that gets loaded, even before the name.'
			));
			$settingsForm .= $this->Form->input('ReportingReport.config.afterReportBlock',array(
					'type'=>'textarea','label'=>'After Report Code Block','after'=>'This is the last thing that gets loaded.'
			));
			echo $this->Html->tag('fieldset',$this->Html->tag('legend','Report Render Blocks').$this->Html->div('collapsible-fieldset',$settingsForm),array('class'=>'report_settings'));
			
		
		} // if/ReportColumns

		echo $this->Form->submit('Test',array('name'=>'data[ReportingReport][action]'));
		echo $this->Form->submit('Save',array('name'=>'data[ReportingReport][action]'));
	 
    } // if/schema

?>
<style type="text/css">
	.plain-text{font-family:'Courier New', Courier, monospace;}
	.collapsible-fieldset{ padding:0; margin:0;}
</style>
<script type="text/javascript">

	var conditionCount = <?php echo (isset($conditions))?count($conditions):0; ?>;
	<?php if(isset($conditionInputDiv)){ ?>
	var conditionInputs = {
		and:<?php echo isset($conditionInputDiv)?json_encode($conditionInputDiv):''; ?>
	}
	<?php } else { ?>
	var conditionInputs = null;
	<?php } ?>
	var filterCount = <?php echo isset($filters)?count($filters):0; ?>;
	<?php if(isset($filterInputDiv)){ ?>
	var filterInputs = <?php echo isset($filterInputDiv)?json_encode($filterInputDiv):''; ?>;
	<?php } else { ?>
	var filterInputs = null;
	<?php } ?>

	var columnCount = <?php echo count($this->data['ReportingReport']['config']['defined_columns']); ?>;
	var columnInputs = <?php echo isset($columnsInputDiv)?json_encode($columnsInputDiv):''; ?>;

	
	// Bind Legends to hide fieldset contents 
	$('fieldset fieldset legend').live('click',function(){
		var a = $(this).siblings('div.collapsible-fieldset');
		if(a.length)
			if(a.is(':visible')){
				window.location.hash = $(this).parent().attr('class') + '-';
				a.slideUp();
			} else {
				window.location.hash = $(this).parent().attr('class');
				a.slideDown();
			}
	});

	$('.condition-remove-handle').live('click',function(event){
		$(this).closest('div.report-condition').remove();
		event.preventDefault();
	});
	$('.filter-remove-handle').live('click',function(event){
		$(this).closest('div.report-filter').remove();
		event.preventDefault();
	});
	$('.column-remove-handle').live('click',function(event){
		$(this).closest('div.report-column').remove();
		event.preventDefault();
	});
	$('.column-complete-handle').live('click',function(event){
		var columnLabel = '';
		columnLabel = $(this).closest('div.report-column').find('input.column-label').val();
		if(columnLabel == ''){
			//$(this).closest('div.report-column').find('input.column-label').addClass('validation-error');
			$(this).closest('div.report-column').find('input.column-label').qtip({
				position:{at:'left center',my:'right center'},
				content:{text:'The column needs at least a Label.'},
				show:false,
				hide:false
			}).qtip('show').focus();
			return false;
		} else {
			$(this).closest('div.report-column').find('input.column-label').qtip('hide');
		}
		if($(this).closest('div.report-column').find('a.completed-column').length == 0){
			$('<a/>',{class:'completed-column column-edit-handle',html:columnLabel,href:'#edit-column'})
				.prependTo($(this).closest('div.report-column'));
		} else {
			$(this).closest('div.report-column').find('a.completed-column').show().html(columnLabel);
		}
		$(this).closest('div.report-column').find('.render-dependent').hide();
		event.preventDefault();
	});
	$('.column-edit-handle').live('click',function(event){
		$(this).closest('div.report-column').find('.render-dependent').show();
		$(this).closest('div.report-column').find('a.completed-column').hide();
		event.preventDefault();
	});
	
	$('.column-token-handle').live('change',function(event){
		if($(this).val() != ''){
			var token = '{{field:' + $(this).val() + '}}';
			$(this).closest('.report-column').find('.column-content').val($(this).closest('.report-column').find('.column-content').val() + token);
			$(this).val('');
		}
	});
	
	$('.renderAsHandle').change(function(){
		$('.render-dependent').hide();
		$('.render-dependent input, .render-dependent select').attr('disabled',true);
		$('.renderAs_' + $(this).val()).show();
		$('.renderAs_' + $(this).val()).find('input, select').attr('disabled',false);
	});
	$('.renderAsHandle').trigger('change');
	
	if($('.field_id-handle').val() == ""){
		$('.field_id-handle').closest('div').siblings('div.dependent-field_id').hide();
	}
	$('.field_id-handle').live('change',function(){
		if($(this).val() == 'custom_where_block'){
			$(this).closest('div').siblings('div.where_input').show();
			$(this).closest('div').siblings('div.dependent-field_id').hide();
		} else if($(this).val() != 'custom_where_block') {
			$(this).closest('div').siblings('div.dependent-field_id').show();
			$(this).closest('div').siblings('div.where_input').hide();
		} else {$(this).closest('div').siblings('div.dependent-field_id').hide();}
	});
	$('.add_condition_handle').click(function(event){
		conditionCount++;
		$('.add_condition_handle').before(conditionInputs.and);
		$('#newestCondition > div > .conditional-field, #newestCondition > div > label').each(function(){
			if($(this).attr('name')) $(this).attr('name',$(this).attr('name').replace('@',conditionCount));
			if($(this).attr('id')) $(this).attr('id',$(this).attr('id').replace('@',conditionCount));
			if($(this).attr('for')) $(this).attr('for',$(this).attr('for').replace('@',conditionCount));
		});
		$('#newestCondition').attr('id','report-condition_' + conditionCount);
		$('.field_id-handle').trigger('change');
		event.preventDefault();
	});
	$('.add_filter_handle').click(function(event){
		filterCount++;
		$('.add_filter_handle').before(filterInputs);
		$('#newestFilter > div > .report-filter-field, #newestFilter > div > label').each(function(){
			if($(this).attr('name')) $(this).attr('name',$(this).attr('name').replace('@',filterCount));
			if($(this).attr('id')) $(this).attr('id',$(this).attr('id').replace('@',filterCount));
			if($(this).attr('for')) $(this).attr('for',$(this).attr('for').replace('@',filterCount));
		});
		$('#newestFilter').attr('id','report-filter_' + filterCount);
		event.preventDefault();
	});
	$('.add_column_handle').click(function(event){
		columnCount++;

		if($('.report-column-list').length == 0){
			$('<div/>',{class:'report-column-list'}).insertBefore($('.add_column_handle'));
		} 
		$('.report-column-list').append(columnInputs);

		$('#newestColumn input, #newestColumn label, #newestColumn .report-column').each(function(){
			if($(this).attr('name')) $(this).attr('name',$(this).attr('name').replace('@',columnCount));
			if($(this).attr('id')) $(this).attr('id',$(this).attr('id').replace('@',columnCount));
			if($(this).attr('for')) $(this).attr('for',$(this).attr('for').replace('@',columnCount));
		});
		if($('.add-column-dropdown').val()){
			var label = $('.add-column-dropdown option[value="' + $('.add-column-dropdown').val() + '"]').html();
			var token = '{{field:' + $('.add-column-dropdown').val() + '}}';
			$('#newestColumn .column-content').val(token);
			$('#newestColumn .column-label').val(label);
			$(this).val('');
			$('.column-complete-handle').trigger('click');
		}
		$('#newestColumn').find('[rel="report-column-seq"]').val(columnCount - 1);
		$('#newestColumn').attr('id','report-column_' + columnCount);
		event.preventDefault();
	});
	$('.add-column-dropdown').change(function(event){
		$('.add_column_handle').trigger('click');
	});
	
	$('.report-column-list').sortable({
		axis:'y',
		update:function(event,ui){
			var c = $(this);
			//var a = $(this).sortable("serialize");
			//alert(a);
			var i = 1;
			$(this).children('.report-column').each(function(){
				$(this).find('[rel="report-column-seq"]').val(i++);
			});
		}
	});
	
	jQuery(document).ready(function(){
		$('.field_id-handle').trigger('change');
		$('.column-complete-handle').trigger('click');
		if(window.location.hash) 
			//appAlert(window.location.hash.replace('#',''));
			$('.' + window.location.hash.replace('#','')).children('legend').trigger('click');
	});
  
  // Autocomplete Field List
	$(function() {
		var availableFields = <?php echo json_encode(array_values($reportColumns)); ?>;
		var organicFields = <?php echo json_encode(array_values($organicColumns)); ?>;
		function split( val ) {
			return val.split( /,\s*/ );
		}
		function extractLast( term ) {
			return split( term ).pop();
		}
		
		$('#ReportingReportConfigHtmlRowBlock')
			.bind( "keydown", function( event ) {
				if ( event.keyCode === $.ui.keyCode.TAB) {
					event.preventDefault();
				}
			});
		
		$('#FieldSearchReportColumns')
			.bind( "keydown", function( event ) {
				if ( event.keyCode === $.ui.keyCode.ENTER) {
					event.preventDefault();
				}
			})
			.autocomplete({
				source:availableFields,
				select: function(event,ui){
					var token = '{{field:' + ui.item.value + '}}';
					//appAlert('Selected: ' + token);
					var accepts = $(this).closest('form').find('.accept-field-tokens:visible');
					accepts.val(accepts.val() + token);
					//$(this).blur().val('empty').focus();
				},
				close: function(){
					$(this).val('');
				}
			});
		
		$('.report_config_condition_operator').autocomplete({
			source:["LIKE","IN","NOT","<>","=","NOT LIKE","NOT IN"],
			select:function(event, ui){
				if(ui.item.value == 'LIKE' || ui.item.value == 'NOT LIKE')
					$(this).parent().siblings('.report_config_condition_value_div').find('.report_config_condition_value').val('%INSERT VALUE HERE%');
				if(ui.item.value == 'IN' || ui.item.value == 'NOT IN')
					$(this).parent().siblings('.report_config_condition_value_div').find('.report_config_condition_value').val('(INSERT LIST HERE)');
				
			}
			});
		$( "#ReportingReportConfigFieldList" )
			// don't navigate away from the field on tab when selecting an item
			.bind( "keydown", function( event ) {
				if ( event.keyCode === $.ui.keyCode.TAB &&
						$( this ).data( "autocomplete" ).menu.active ) {
					event.preventDefault();
				}
			})
			.autocomplete({
				minLength: 0,
				source: function( request, response ) {
					// delegate back to autocomplete, but extract the last term
					response( $.ui.autocomplete.filter(
						availableFields, extractLast( request.term ) ) );
				},
				focus: function() {
					// prevent value inserted on focus
					return false;
				},
				select: function( event, ui ) {
					var terms = split( this.value );
					// remove the current input
					terms.pop();
					// add the selected item
					terms.push( ui.item.value );
					// add placeholder to get the comma-and-space at the end
					terms.push( "" );
					this.value = terms.join( ", " );
					return false;
				}
			});
						
			$( "#ReportingReportConfigOrder, #ReportingReportConfigGroup" )
			// don't navigate away from the field on tab when selecting an item
			.bind( "keydown", function( event ) {
				if ( event.keyCode === $.ui.keyCode.TAB &&
						$( this ).data( "autocomplete" ).menu.active ) {
					event.preventDefault();
				}
			})
			.autocomplete({
				minLength: 0,
				source: function( request, response ) {
					// delegate back to autocomplete, but extract the last term
					response( $.ui.autocomplete.filter(
						organicFields, extractLast( request.term ) ) );
				},
				focus: function() {
					// prevent value inserted on focus
					return false;
				},
				select: function( event, ui ) {
					var terms = split( this.value );
					// remove the current input
					terms.pop();
					// add the selected item
					terms.push( ui.item.value );
					// add placeholder to get the comma-and-space at the end
					terms.push( "" );
					this.value = terms.join( ", " );
					return false;
				}
			});

	});  
</script>
