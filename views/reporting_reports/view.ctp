<div class="reports view">
  <?php echo $this->element('reporting_reports/render_sql_results'); ?>
</div>
<div class="actions">
  <h3><?php  __('Report');?></h3>
  	<?php $trs[] = $this->Html->tableCells(array('Id:',$this->data['ReportingReport']['id'])); ?>
    <?php $trs[] = $this->Html->tableCells(array('Name:',$this->data['ReportingReport']['name'])); ?>
    	<?php 
		if(!empty($this->data['ReportingReport']['config']['params'])) 
			foreach($this->data['ReportingReport']['config']['params'] as $param){ ?>
			 <?php $params[] = $param; ?> 
		<?php 
		} ?>
		<?php if(isset($params) && !empty($params)){
            $trs[] = $this->Html->tableCells(array('Params:',implode(',',$params)));
        }  ?>
    <?php echo $this->Html->tag('table',implode($trs)); ?>
	<h3><?php __('Actions'); ?></h3>
	<ul>
		<li><?php echo $this->Html->link(__('Edit Report', true), array('action' => 'edit', $this->data['ReportingReport']['id'])); ?> </li>
		<li><?php echo $this->Html->link(__('Delete Report', true), array('action' => 'delete', $this->data['ReportingReport']['id']), null, sprintf(__('Are you sure you want to delete # %s?', true), $this->data['ReportingReport']['id'])); ?> </li>
		<li><?php echo $this->Html->link(__('List Reports', true), array('action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(__('New Report', true), array('action' => 'add')); ?> </li>
	</ul>
</div>
