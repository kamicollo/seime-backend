<?php

	function DOMinnerHTML(DOMElement $element) 
	{ 
		  $innerHTML = ""; 
		  $children = $element->childNodes; 
		  foreach ($children as $child) 
		  { 
		      $tmp_dom = new DOMDocument(); 
		      $tmp_dom->appendChild($tmp_dom->importNode($child, true)); 
		      $innerHTML.=trim($tmp_dom->saveHTML()); 
		  } 
		  return $innerHTML; 
	} 

	function print_f($array) {
		echo '<pre>';
	
		if ($array instanceof DOMNodeList) {
		print_r($array->length);
			foreach ($array as $node) {
				echo $node->nodeValue;
			}
		}
		elseif ($array instanceof DOMNode) {
			echo $array->nodeValue;
		}
		else print_r($array);
		echo '</pre>';
	}

	function log_f($message, $object_id) {
		echo '<br>' . $message . '<br>';
	}
	
	class ScrapingUtilities {		
		public static function cleanHTML($html) {
			$html = @iconv('windows-1257', 'UTF-8//IGNORE', $html);
			$html = str_replace('charset=windows-1257"', 'charset=UTF-8"', $html);
			$tidy = new tidy();
			$config = array('indent' => true, 'output-xhtml' => true, 'wrap' => 200);
			$tidy->parseString($html, $config, 'UTF8');
			$tidy->cleanRepair();
			return (string) $tidy;
		}	
	}
	
	function __ending($number, $endings = array('narių', 'narys', 'nariai')) {
		$count = $number % 100;
		if (($count > 9) && ($count < 20)) return $endings[0];
		elseif ($count % 10 == 0) return $endings[0];
		elseif ($count % 10 == 1) return $endings[1];
		else return $endings[2];
	}

