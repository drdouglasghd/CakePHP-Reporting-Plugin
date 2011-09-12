<?php if(isset($this->params['isAjax']) && $this->params['isAjax'] == 1){ $class = 'ajaxForm'; }else{ $class = 'form';}  ?>
<div class="reports <?php echo $class; ?>">
<style type="text/css">.collapsible-fieldset{ display:none;}</style>
<?php echo $this->Form->create('ReportingReport');
  //pr($this->params);
?>
	<fieldset>
 		<legend><?php __('Edit Report'); ?></legend>
	<?php
		echo $this->Form->hidden('id');
		echo $this->element('reporting_reports/build_form');
	?>
	</fieldset>
<?php echo $this->Form->end();?>

<?php 
  echo $this->element('reporting_reports/render_sql_results');
?>


</div>
<?php if(!isset($this->params['isAjax']) || (isset($this->params['isAjax']) && $this->params['isAjax'] != 1)){ ?>
<div class="actions">
	<h3><?php __('Actions'); ?></h3>
	<ul>

		<li><?php echo $this->Html->link(__('Delete', true), array('action' => 'delete', $this->Form->value('ReportingReport.id')), null, sprintf(__('Are you sure you want to delete # %s?', true), $this->Form->value('ReportingReport.id'))); ?></li>
		<li><?php echo $this->Html->link(__('List Reports', true), array('action' => 'index'));?></li>
	</ul>
</div>
<?php } ?>