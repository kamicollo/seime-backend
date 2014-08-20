<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

abstract class Utilities {
	const BASE_URL = 'http://www3.lrs.lt/pls/inter/';

	protected function clearCache($url) {
		$md5 = md5($url);
		$base_folder = dirname(__FILE__) . '/../cache';		
		$folder = substr($md5, 0, 2);
		$path = "$base_folder/$folder/$md5";
		if (file_exists($path))	{
			unlink($path);
		}
	}
	
	protected function getHTMLDOM($url, $reload = FALSE) {

		/* cache implementation */
		$md5 = md5($url);
		$base_folder = dirname(__FILE__) . '/../cache';		
		$folder = substr($md5, 0, 2);
		$path = "$base_folder/$folder/$md5";
		if ( (file_exists($path)) && (!$reload) ) {
			//if file in cache, take it from there
			$html = file_get_contents($path);
		} 
		else {
			$old_path = "$base_folder/$md5";
			if ((file_exists($old_path)) && (!$reload)) {
				//file in old cache, take it from there
				$html = file_get_contents($old_path);
			} 
			else {
				//file not present, download it
				$html = file_get_contents($url);
				if ($html === false) {
					return false;
				}
			}
			
			if (!is_dir("$base_folder/$folder")) {
				//create a dir, if not existing
				$oldumask = umask(0);
				mkdir("$base_folder/$folder");
				umask($oldumask);
			}
			file_put_contents($path, $html); //save the file
		}
		$html = @iconv('windows-1257', 'UTF-8//IGNORE', $html);
		$html = str_replace('charset=windows-1257"', 'charset=UTF-8"', $html);
		$tidy = new tidy;
		$config = array(
			'indent' => true,
			'output-xhtml' => true,
			'wrap' => 200);
		$tidy->parseString($html, $config, 'UTF8');
		$tidy->cleanRepair();
		$dom = new DOMDocument('1.0', 'UTF-8');
		@$dom->loadHTML((string) $tidy);
		return $dom;
	}

	final protected function clean($string) {
		return trim(str_replace(array('&nbsp;', '&Acirc;'), '', htmlentities($string, ENT_NOQUOTES, 'UTF-8')));
	}

	final protected function decode($string) {
		$string = html_entity_decode($string, ENT_QUOTES, 'UTF-8');
		return preg_replace('/\s+/', ' ', $string);
	}

	final protected function getMemberId(DOMElement $dom) {
		$variables = array();
		$a = $dom->getElementsByTagName('a')->item(0);
		if (is_object($a)) {
			$variables = array();
			$query = parse_url($a->getAttribute('href'), PHP_URL_QUERY);
			parse_str($query, $variables);
			if (isset($variables['p_asm_id'])) {
				return $variables['p_asm_id'];
			} else {
				$member_id = str_replace('a', '', $a->getAttribute('name'));
				if (!empty($member_id)) {
					return $member_id;
				} else {
					log_f('parsing error: lankomumo lentele - person a asm_id . ', $this->getId());
				}
			}
		}
		else
			log_f('parsing error: lankomumo lentele - person a . ', $this->getId());
	}

}

abstract class HTMLObject extends Utilities implements Seimas {

	protected $parent = NULL;
	protected $Factory = NULL;
	protected $children = array();
	protected $id = '';
	protected $url = '';
	protected $PDO = 0;

	public function __construct($url, Seimas $parent = NULL, $params = NULL, Factory $Factory = NULL) {
		/* Determine if not created via PDO */
		if (empty($this->PDO)) {
			$this->url = $url;
			$query = parse_url($url, PHP_URL_QUERY);
			$class_name = get_class($this);
			$class = new ReflectionClass($class_name);
			$token = $class->getStaticPropertyValue('url_token');
			$this->id = str_replace($token, '', $query);
		}
		/* Add reference to parent */
		$this->parent = $parent;
		/* Add reference to Factory */
		$this->Factory = $Factory;
	}

	final public function initialise() {
		if (false === $this->getId()) {
			throw new Exception('no URL defined!');
		} 
		elseif (false === $this->populateData()) {
			try {
				$this->scrapeData();
				$this->saveData();
			} 
			catch (Exception $e) {
				'<br><br>Unexpected conditions met!<br>' . $e->getMessage();
			}
		}
	}

	public function populateChildren($initialiseSearch = false) {
		$class = get_class($this);
		$class_ = new ReflectionClass($class);
		$child_class = $class_->getStaticPropertyValue('child_class');
		$this->children = $this->Factory->getObjectChildren($class, $child_class, $this->getId(), $this);
		if (empty($this->children) && ($initialiseSearch)) {
			$this->scrapeData();
			$this->saveData();
		}
	}

	public function initialiseChildren($recursive = false) {
		foreach ($this->children as $child) {
			if ($child instanceof Seimas) {
				$child->initialise();
				if ($recursive) {
					$child->initialiseChildren(true);
				}
			}
			else
				throw new Exception('child does not implement Seimas Interface');
		}
	}

	final protected function getParentInfo($function, $parameters = array()) {
		if (NULL === $this->parent) {
			throw new Exception('no parent available');
		} else {
			return call_user_func_array(array($this->parent, $function), $parameters);
		}
	}

	final protected function getSiblingInfoById($sibling_id, $function, $parameters = array()) {
		if (NULL === $this->parent) {
			throw new Exception('no parent available');
		} else {
			$sibling = $this->parent->getChild($sibling_id);
			if (false === $sibling) {
				throw new Exception('no sibling with such ID available');
			} else {
				return call_user_func_array(array($sibling, $function), $parameters);
			}
		}
	}

	final protected function getSiblingInfoByPosition($current_id, $sibling_position, $function, $parameters = array()) {
		if (NULL === $this->parent) {
			throw new Exception('no parent available');
		} else {
			$sibling = $this->parent->getChildByPosition($current_id, $sibling_position);
			if (false === $sibling) {
				throw new Exception('no sibling with such ID available');
			} else {
				return call_user_func_array(array($sibling, $function), $parameters);
			}
		}
	}

	final protected function getChild($child_id) {
		if (isset($this->children[$child_id]))
			return $this->children[$child_id];
		else
			return false;
	}

	final protected function getChildByPosition($child_id, $relative_sibling_position) {
		$children = array_keys($this->children);
		$child_position = array_search($child_id, $children);
		if (false === $child_position) {
			return false;
		} else {
			$sibling_position = $child_position + $relative_sibling_position;
			if (!isset($children[$sibling_position]))
				return false;
			else {
				$sibling_id = $children[$sibling_position];
				return $this->getChild($sibling_id);
			}
		}
	}

	final public function getChildren() {
		return $this->children;
	}

	public function getId() {
		return $this->id;
	}

	public function getUrl() {
		return $this->url;
	}

	public function show() {
		$a = false;
		if ($a = $this->__toString()) {
			echo "<strong>Class " . get_class($this) . '<br></strong>';
			print_f($a);
		}
		else
			print_f($this);
	}

	public function __toString() {
		$array = get_object_vars($this);
		unset($array['PDO']);
		unset($array['Factory']);
		unset($array['parent']);
		unset($array['url_token']);
		unset($array['additional_data']);
		if (is_array($array['children']))
			$array['children'] = $this->cleanChildren($array['children']);
		return $array;
	}

	protected function cleanChildren($children) {
		$array = array();
		foreach ($children as $id => $child) {
			if ($child instanceof Seimas) {
				$child->class_name = '<strong>' . get_class($child) . ' Object</strong>';
				$array[$id] = $child->__toString();
			}
		}
		return $array;
	}

	abstract protected function populateData();

	abstract protected function saveData();

	abstract protected function scrapeData($reload = FALSE);
}

Interface Seimas {

	public function initialise();
}

?>
