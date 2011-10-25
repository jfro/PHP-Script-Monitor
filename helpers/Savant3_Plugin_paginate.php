<?php
class Savant3_Plugin_paginate extends Savant3_Plugin {
	
	function paginate($page, $page_count) {
		if(array_key_exists('QUERY_STRING', $_SERVER)) {
			$query = $_SERVER['QUERY_STRING'].'&amp;';
		}
		else {
			$query = '';
		}
		$html = "<p>";
		if($page > 0) {
			$html .= "<a href='?${query}page=0'><img src=\"images/resultset_first.png\"  alt=\"first\" /></a>";
		}
		if($page > 0) {
			$html .= "<a href='?${query}page=".($page-1)."'><img src=\"images/resultset_previous.png\"  alt=\"previous\" /></a>";
		}
		$html .= "Page ".($page+1)." of $page_count";
		if($page+1 < $page_count) {
			$html .= "<a href='?${query}page=".($page+1)."'><img src=\"images/resultset_next.png\"  alt=\"next\" /></a>";
		}
		if($page+1 < $page_count) {
			$html .= "<a href='?${query}page=".($page_count-1)."'><img src=\"images/resultset_last.png\"  alt=\"last\" /></a>";
		}
		$html .= "</p>";
		return $html;
	}
}