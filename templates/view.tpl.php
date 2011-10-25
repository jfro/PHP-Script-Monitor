<table border="0" cellspacing="0" cellpadding="3">
	<tr>
		<th class="left-header">Type: </th>
		<td><img src="images/<?=$this->event->icon_name?>.png" /> <?=$this->event->type?></td>
	</tr>
	<tr>
		<th class="left-header">Script: </th>
		<td><?=$this->event->script?></td>
	</tr>
	<tr>
		<th class="left-header">URL: </th>
		<td><?=$this->event->url.($this->event->query ? '?'.$this->event->query : '')?></td>
	</tr>
	<tr>
		<th class="left-header">Message: </th>
		<td><?=$this->event->error?></td>
	</tr>
	<tr>
		<th class="left-header">Line: </th>
		<td><a href="#line<?=$this->event->line?>" onclick="Element.show('source-data');"><?=$this->event->line?></a></td>
	</tr>
	<tr>
		<th class="left-header">Recently Occurred on: </th>
		<td><?=$this->event->formatLastOccurredOn()?></td>
	</tr>
	<tr>
		<th class="left-header">First Occurred on Virtual Host: </th>
		<td><?=$this->event->domain?></td>
	</tr>
	<tr>
		<th class="left-header">First Occurred on Server: </th>
		<td><?=$this->event->server?></td>
	</tr>
	<tr>
		<th class="left-header">First Occurred On: </th>
		<td><?=$this->event->formatOccurredOn()?></td>
	</tr>
</table>
<? if($this->event->post) { ?>
<p>
	<a href="#" onclick="Element.toggle('post-data'); return false;">Post Data &raquo;</a>
	<div id="post-data" style="display: none;">
		<?=$this->tree($this->event->post)?>
	</div>
</p>
<? } ?>

<? if($this->event->session) { ?>
<p>
	<a href="#" onclick="Element.toggle('session-data'); return false;">Session Data &raquo;</a>
	<div id="session-data" style="display:none;">
		<?=$this->tree($this->event->session)?>
	</div>
</p>
<? } ?>

<? if($this->event->trace) { ?>
<p>
	<a href="#" onclick="Element.toggle('trace-data'); return false;">Trace Data &raquo;</a>
	<div id="trace-data" style="display:none;">
		<table class="tabular-list-full" style="font-size: 10pt;" border="0" cellpadding="3" cellspacing="0">
			<? foreach($this->event->trace as $i=>$t) { ?>
			<tr class="tabular-row">
				<td class="row-source"><?=$i?></td>
				<td class="row-source"><?=$t['class'].'::'.$t['function'].'('.implode(',',$t['args']).')'?></td>
				<td class="row-source"><?=$t['file']?>:<?=$t['line']?></td>
			</tr>
			<? } ?>
		</table>
	</div>
</p>
<? } ?>

<p>
<a href="#" onclick="Element.toggle('source-data'); return false;">Source Code &raquo;</a>
	<div id="source-data" style="display:none;">
		<pre><?=$this->event->source()?></pre>
	</div>
</p>