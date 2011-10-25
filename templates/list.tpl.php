<? $types = array(
	'all' => 'All',
	'slow' => 'Slow Script',
	'notice' => 'Notice',
	'warning' => 'Warning',
	'error' => 'Error',
	'exception' => 'Exception',
	'strict' => 'Strict (PHP 5)'
);
$type_styles = array(
	'slow' => 'php-notice',
	'notice' => 'php-minor',
	'warning' => 'php-warning',
	'error' => 'php-error',
	'exception' => 'php-error',
	'strict' => 'php-minor'
);
?>
<script type="text/javascript">
function check_all_delete() {
	var boxes = document.getElementsByClassName('delete-check');
	for (var i=0; i < boxes.length; i++) {
		boxes[i].checked = !boxes[i].checked;
	}
}
</script>
<p>
<form method="get">
	<strong>Event Type: </strong>
	<select name="type" onchange="this.form.submit()">
		<? foreach($types as $key=>$type) { ?>
			<option <?=$this->selected($key, $this->selected_type)?> value="<?=$key?>"><?=$type?></option>
		<? } ?>
	</select>
	<input id="filter-submit" type="submit" value="Filter" />
</form>
<script type="text/javascript">
Element.hide('filter-submit');
</script>
</p>
<?=$this->paginate($this->page, $this->page_count)?>

<form method="post" action="list.php" enctype="multipart/form-data">
<table class="tabular-list-full" cellpadding="0" cellspacing="0" border="0" summary="script events">
	<tr class="tabular-header">
		<th class="action-heading" style="width:10px;">Del</th>
		<th class="action-heading" style="width:10px;">View</th>
		<th class="active-heading"><a href='?col=script&amp;order=<?=$this->order?>&amp;type=<?=$this->selected_type?>'>Script</a></th>
		<th class="active-heading"><a href='?col=type&amp;order=<?=$this->order?>'>Type</a></th>
		<th class="active-heading"><a href='?col=occurrences&amp;order=<?=$this->order?>'>Occurrences</a></th>
		<th class="active-heading"><a href='?col=time&amp;order=<?=$this->order?>'>Execution Time</a></th>
		<th class="active-heading"><a href='?col=last_occurred_on&amp;order=<?=$this->order?>'>Date</a></th>
	</tr>
	<? if(count($this->events)) { ?>
	<? foreach($this->events as $evt) { ?>
	<tr class="tabular-row <?=$type_styles[$evt['type']]?>">
		<td class="row-action"><input class="delete-check" type="checkbox" name="delete[]" value="<?=$evt['id']?>" /></td>
		<td class="row-action"><a href='view.php?id=<?=$evt['id']?>'><img src='images/magnifier.png' alt="View Details" /></a></td>
		<td class="clickable row-source" class="row-source" onclick="Element.toggle('script-details-<?=$evt['id']?>');">
			<?=$evt['script']?>
			<div class="script-details" id="script-details-<?=$evt['id']?>" style="display: none;">
				<?=$evt['error']?> on line <?=$evt['line']?>
			</div>
		</td>
		<td class="row-source"><img src="images/<?=$evt['type'] == 'slow' ? 'clock_error.png' : 'script_error.png';?>" /> <a href='?type=<?=$evt['type']?>'><?=$evt['type']?></a></td>
		<td class="row-source"><?=$evt['occurrences']?></td>
		<td class="row-source"><?=$evt['time'] > 0 ? $evt['time'] : ''?></td>
		<td class="row-source"><?=$evt['occurred_on']?></td>
	</tr>
	<? } ?>
	<? } else { ?>
	<tr class="tabular-row">
		<td class="row-source" colspan="6">No events found</td>
	</tr>
	<? } ?>
</table>
<?=$this->paginate($this->page, $this->page_count)?>
<p>
<a href="javascript:check_all_delete();">Toggle all delete</a>
<input type="submit" value="Delete Selected" />
</p>
</form>