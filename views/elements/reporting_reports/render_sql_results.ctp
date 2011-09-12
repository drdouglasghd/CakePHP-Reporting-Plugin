<div class="report">
<?php 
//pr($statement);
if(isset($sql_results)){
	// Render Before Block
	if(isset($this->data['ReportingReport']['config']['beforeReportBlock'])) echo $this->data['ReportingReport']['config']['beforeReportBlock'];

	// Report Edit Link
	if($appUser['User']['group_id'] == 1) 
		$reportLinks[] = $this->Html->link('Edit Report',array('controller'=>'reports','action'=>'edit',$this->data['ReportingReport']['id']),array('class'=>'edit-report-handle'));
		
	// Report Url
	$reportLinks[] = $this->Html->link('Report Link',Router::url(),array('class'=>'report-url'));

	// Report Url
	$reportLinks[] = $this->Html->link('SQL Statement',array('#show-sql'),array('onclick'=>"$('.sql_statement').toggle();return false;"));

	// Report Name
	echo $this->Html->tag('h2',$this->data['ReportingReport']['name'] . $this->Html->tag('span',implode(' - ',$reportLinks),array('class'=>'report-header-links')));

	echo $this->Html->div('sql_statement',spr($statement),array('style'=>'display:none;'));

	// Report Filters
	//pr($this->data['ReportingReport']['config']['report_filters']);
	if(!empty($this->data['ReportingReport']['config']['report_filters'])){
		foreach($this->data['ReportingReport']['config']['report_filters'] as $fid => $filter){
			$filterInputs[] = $this->Form->hidden('Filter.'.$filter['field_name'].'.field_name',array(
				'type'=>'text','id'=>'filter-field_'.$fid,'value'=>$filter['field_name']
			));
			$filterInputs[] = $this->Form->input('Filter.'.$filter['field_name'],array(
				'type'=>'text','id'=>'filter-value_'.$fid,'default'=>isset($this->params['named'][$filter['field_name']])?urldecode($this->params['named'][$filter['field_name']]):''
			));
		}
		if(!empty($filterInputs)){
			echo $this->Form->create(null,array('id'=>'ReportFilterForm_'.$this->data['ReportingReport']['url_key'],'class'=>'report-filter-form','style'=>'display:none;'));
			echo $this->Form->hidden('FilterUrl.url',array('value'=>Router::url()));
			echo $this->Html->div('report-filters',implode($filterInputs));
			echo $this->Form->submit('Filter');
			echo $this->Form->end();
			echo $this->Html->link('Show Filters','#filter-show',array('class'=>'filter-show-handle'));
			?>
			<style type="">
				.report h2 {color:black; font-size:1.15em; font-weight:700;}
				.report-filter-form .report-filters { display:inline-block; width:30%; vertical-align:middle; margin:0; padding:0 .5em 1em;}
				.report-filter-form .report-filters div.input { margin:0; padding:0 .5em;}
				.report-filter-form .submit { display:inline-block; width:30%; vertical-align:middle;}
			</style>
			<script type="text/javascript">
				$('.report-filter-form').ajaxForm({
					beforeSubmit: function(formData, jqForm, options){
						loadingText();
						},
					success: function(responseText, statusText, xhr, $form){
						loadingTextRemove();
						var a = $form.closest('div.report-loader');
						var b = $form.closest('div.reports')
						if(a.length){ 
							a.html(responseText); 
						} else {
							b.html(responseText);
						}
					}
				});
				//var labels = new Array(); $('.report-filter-form .report-filters div.input label').each(function(){labels.push($(this).html());});
				$('.filter-show-handle').click(function(event){
					$(this).siblings('.report-filter-form').show();
					$(this).hide();
					event.preventDefault();
				});
			</script>
			<?php
		}
 	}

	// Display Paginator on Top
	if(isset($this->data['ReportingReport']['config']['use_paginator']) && $this->data['ReportingReport']['config']['use_paginator'] && isset($sql_results)){
		$extraParams = $this->params['url'];
		if(isset($extraParams['url'])) unset($extraParams['url']);
		if(!empty($extraParams)) foreach($extraParams as $key => $value){
			$this->Paginator->options['url'][] = $key.':'.$value;
		}
		$paginatorOptions = array(
			'format' => __('Page %page% of %pages%, showing %current% records out of %count% total, starting on record %start%, ending on %end%', true)
		);
		$pagingPrev =  $this->Paginator->prev('<< ' . __('previous', true), array(), null, array('class'=>'disabled'));
		$pagingNumbers = $this->Paginator->numbers();
		$pagingNext = $this->Paginator->next(__('next', true) . ' >>', array(), null, array('class' => 'disabled'));
		if(count($sql_results) > 20){
			echo $this->Html->tag('p',$this->Paginator->counter($paginatorOptions));
			echo $this->Html->div('paging',sprintf('%s | %s | %s',$pagingPrev,$pagingNumbers,$pagingNext));
		}
	}

	// Render as Table or Wrapped Blocks
	$renderAs = isset($this->data['ReportingReport']['config']['renderAs'])?$this->data['ReportingReport']['config']['renderAs']:'';
	if(isset($sql_results)) if($renderAs == 'auto_table'){
		echo $this->element('reporting_reports/sql_results_to_table');
	} elseif($renderAs == 'wrapped_html') {
		echo $this->element('reporting_reports/wrapped_sql_results');
	} elseif($renderAs == 'defined_columns') {
		echo $this->element('reporting_reports/defined_columns_sql_results');	
	} else {
		// Redundant but will develop more options
		echo $this->element('reporting_reports/sql_results_to_table');
	}
	
	// Display Paginator on Bottom
	if(isset($this->data['ReportingReport']['config']['use_paginator']) && $this->data['ReportingReport']['config']['use_paginator'] && isset($sql_results)){
		$extraParams = $this->params['url'];
		if(isset($extraParams['url'])) unset($extraParams['url']);
		if(!empty($extraParams)) foreach($extraParams as $key => $value){
			$this->Paginator->options['url'][$key] = $value;
		}
		echo $this->Html->tag('p',$this->Paginator->counter($paginatorOptions));
		echo $this->Html->div('paging',sprintf('%s | %s | %s',$pagingPrev,$pagingNumbers,$pagingNext));
		?>

  <?php } // /Paginator 
  
	// Render After Report Block
	if(isset($this->data['ReportingReport']['config']['afterReportBlock'])) echo $this->data['ReportingReport']['config']['afterReportBlock'];

} // $sql_results is set?
?>
		<script type="text/javascript">
			$('.paging a, a.edit-report-handle').click(function(event){
				var a = $(this);
				var pageParams = <?php echo json_encode($this->params['named']); ?>;
				loadingText();
				$.get(a.attr('href'),pageParams,function(data,text,xhr){
					//a.closest('div.report-loader').html(data);
					var aa = a.closest('div.report-loader');
					var ab = a.closest('div.reports')
					if(aa.length){ 
						aa.html(data); 
					} else {
						ab.html(data);
					}
					loadingTextRemove();
				}); 
				event.preventDefault();
			});
		</script>
</div>