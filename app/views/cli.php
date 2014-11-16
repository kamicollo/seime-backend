Stuff.

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
?>
<html>
<body>
<pre>
<?php super_print($object); ?>
</pre>
</head>
<html>

<?php
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
