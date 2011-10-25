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
<div>
	<table class="tabular-list-full tabular-partial" cellpadding="0" cellspacing="0" border="0" summary="recent events">
	<tr class="tabular-header">
		<th class="action-heading" style="width:10px;">View</th>
		<th class="active-heading">Script</th>
		<th class="active-heading">Type</th>
		<th class="active-heading">Date</th>
	</tr>
	<? if(count($this->events)) { ?>
	<? foreach($this->events as $evt) { ?>
	<tr class="tabular-row <?=$type_styles[$evt->type]?>">
		<td class="row-action"><a href='view.php?id=<?=$evt->id?>'><img src="images/magnifier.png" alt="View" /></a></td>
		<td class="clickable row-source">
			...<?=substr($evt->script, -20)?>
		</td>
		<td class="row-source"><img src="images/<?=$evt->icon_name?>.png" /> <a href='list.php?type=<?=$evt->type?>'><?=$evt->type?></a></td>
		<td class="row-source"><?=$evt->formatOccurredOn('h:i:s A')?></td>
	</tr>
	<? } ?>
	<? } else { ?>
	<tr class="tabular-row">
		<td class="row-source" colspan="6">No recent events found</td>
	</tr>
	<? } ?>
	</table>
	
</div>
<div style="float: right;"><canvas id="graph" height="300" width="300"></canvas></div>

<script type="text/javascript">
var xticks = new Array();
var data = new Array();
<?
$i = 0;
foreach(JKScriptEvent::typeCounts(true) as $type=>$count) { 
if($count > 0) {	
?>
data[<?=$i?>] = [<?=$i?>,<?=$count?>];
xticks[<?=$i?>] = {v:<?=$i?>, label:"<?=$type?>"};
<? $i++;
} } ?>
var options = {
   "colorScheme": PlotKit.Base.baseDarkPrimaryColors(),
   "padding": {left: 0, right: 0, top: 10, bottom: 30},
   "xTicks": xticks,
   'drawBackground': false
};
var layout = new PlotKit.Layout("pie", options);

layout.addDataset("Events", data);
layout.evaluate();
var canvas = MochiKit.DOM.getElement("graph");
var plotter = new PlotKit.SweetCanvasRenderer(canvas, layout, options);
plotter.render();
</script>