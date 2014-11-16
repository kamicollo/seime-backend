<?php

namespace Seimas\factories;
use Seimas\DOM\DOMElement;
use Seimas\models\Member;
use \Log as Log;

class MemberFactory {
	
	public static $useDB = false;
	protected static $members = [];
	
	public static function getMember($id) {
		if (array_key_exists($id, self::$members)) {
			return self::$members[$id];
		} else {
			$member = Member::find($id);
			if  ($member !== null) {
				self::$members[$id] = $member;
			}
			return $member;
		}
	}
	
	protected static function create($id, $name) {
		$member = new Member();
		$member->id = $id;
		$member->name = $name;
		self::$members[$id] = $member;
		return $member;
	}
	
	public static function createFromAnchor(DOMElement $element = null) {
		if ($element !== null) {
			$id = self::parseId($element);
			$name = self::parseName($element->getTrimmedValue());
			$member = self::getMember($id);
			if ($member === null) {
				$member = self::create($id, $name);
			}
			return $member;
		} else {
			Log::warning('Could not parse member anchor data',['anchor' => $element]);
		}		
	}
	
	protected static function parseName($name) {
		$words = explode(' ', $name);
		$last_name = array_pull($words, 0);
		return implode(' ', $words) . ' ' . $last_name;
	}
	
	protected static function parseId(DOMElement $element) {
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
}
