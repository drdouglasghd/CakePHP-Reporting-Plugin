<div class="reports form">
<?php echo $this->Form->create('ReportingReport');?>
	<fieldset>
 		<legend><?php __('Add Report'); ?></legend>

<?php 
  echo $this->element('reporting_reports/build_form');
?>
	</fieldset>
<?php
  echo $this->Form->end();
  
  echo $this->element('reporting_reports/render_sql_results');
?>

</div>
<div class="actions">
	<h3><?php __('Actions'); ?></h3>
	<ul>

		<li><?php echo $this->Html->link(__('List Reports', true), array('action' => 'index'));?></li>
	</ul>
</div>