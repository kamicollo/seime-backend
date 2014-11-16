<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Seime.dev</title>
	<style>
		@import url(//fonts.googleapis.com/css?family=Lato:700);

		body {
			margin:0;
			font-family:'Lato', sans-serif;
			text-align:center;
			color: #999;
		}

		.welcome {
			width: 300px;
			height: 200px;
			position: absolute;
			left: 50%;
			top: 50%;
			margin-left: -150px;
			margin-top: -100px;
		}

		a, a:visited {
			text-decoration:none;
		}

		h1 {
			font-size: 32px;
			margin: 16px 0 0 0;
		}
	</style>
</head>
<body>
	<pre>
<?php super_print($object); ?>
	</pre>
</body>
</html>


<?php 
function super_print($object, $indent = 0) {
	print_indent('Object: ' . get_class($object), $indent);
	print_properties($object, $indent);
	$i = 1;
	if ($object instanceof \Seimas\models\AncestorInterface) {
		foreach($object->getDescendants() as $d) {
			if (!empty($d)) {
				print_indent('  Descendants Group ' . $i++, $indent);
				foreach($d as $do) {
					super_print($do, $indent + 1);
				}
			}
		}
	}
}

function print_indent($text, $indent) {
	print str_repeat(' ', $indent * 5) . $text . PHP_EOL;
}

function print_properties($object, $indent) {
	$props = ['id', 'url', 'title', 'presenter', 'name', 'years', 'kadencija'];
	foreach($props as $p) {
		if ($object->$p != null) {
			print_indent('  ' . $p . ': ' . $object->$p, $indent);
		}
	}
}
?>
