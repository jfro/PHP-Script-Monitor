<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

	<title>Script Events</title>
	<link rel="stylesheet" type="text/css" href="css/modalbox.css" />
	<link rel="stylesheet" type="text/css" href="css/base.css" />
	<link rel="stylesheet" type="text/css" media="all" href="css/tree.css" />
    <script type="text/javascript" src="javascripts/tree.js"></script>
	<script type="text/javascript" src="javascripts/prototype.js"></script>
	<script type="text/javascript" src="javascripts/scriptaculous.js"></script>
	<script type="text/javascript" src="javascripts/modalbox.js"></script>
	<!-- graphing includes -->
	<script type="text/javascript" src="javascripts/mochikit/MochiKit.js"></script>
	<script type="text/javascript" src="javascripts/plotkit/Base.js"></script>
	<script type="text/javascript" src="javascripts/plotkit/Layout.js"></script>
	<script type="text/javascript" src="javascripts/plotkit/Canvas.js"></script>
	<script type="text/javascript" src="javascripts/plotkit/SweetCanvas.js"></script>
</head>

<body>
<ul id="main-nav">
	<li><a href='index.php'>Events Summary</a></li>
	<li><a href='list.php'>List Events</a></li>
</ul>

<?=$this->content?>

<p style="font-size: 60%; text-align: center; clear: both;">
	Jerome's <a href="http://jeremyknope.com/pages/scriptlogger">Script Logger</a> v<?=SL_VERSION?><br />
	&copy;<?=date('Y')?> <a href="http://jeremyknope.com">Jeremy Knope</a><br />
</p>
</body>
</html>
