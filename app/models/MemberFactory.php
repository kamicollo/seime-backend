<?php

namespace Seimas;
use \Log as Log;
class MemberFactory {
	
	public static $useDB = false;
	protected static $members = [];
	
	public static function getMember($id) {
		if (!array_key_exists($id, self::$members)) {
			self::loadMember($id);
		}
		return self::$members[$id];
	}
	
	protected function loadMember($id) {
		self::$members[$id] = new Member();
		self::$members[$id]->id = $id;
	}
	
	public static function createFromAnchor(\DOMElement $element = null) {
		if ($element !== null) {
			$id = self::parseId($element);
			if ($id !== null) {
				$member = new Member();
				$member->id = $id;
				$member->name = self::parseName($element->getNodeValue());
				self::add($member);
				return $member;
			} else {
				Log::warning('Could not parse member anchor data',['anchor' => $element]);
			}		
		}
	}
	
	protected static function parseName($name) {
		$words = explode(' ', $name);
		$last_name = array_pull($words, 0);
		return implode(' ', $words) . ' ' . $last_name;
	}
	
	protected static function parseId(\DOMElement $element) {
		$id = null;
		$alternative_id = null;
		$query_variables = array();
		$matches = array();
		$query = parse_url($element->getAttribute('href'), PHP_URL_QUERY);
		parse_str($query, $query_variables);
		if (isset($query_variables['p_asm_id'])) {
			$id = $query_variables['p_asm_id'];
		} elseif (preg_match('/a([0-9]+)/', $element->getAttribute('name'), $matches)) {
			$alternative_id = $matches[1];
		}
		if (($id !== null) && ($alternative_id !== null) && ($id === $alternative_id)) {
			return $id;
		} elseif (($id !== null) && ($alternative_id !== null)) {
			Log::warning('Ambigious member id found in href/name',['object' => $element]);
			return $id;
		} elseif ($id !== null) {
			return $id;			
		} else {
			return $alternative_id;
		}
	}
	
	protected static function add(Member $member) {
		self::$members[$member->id] = $member;
	}
}
