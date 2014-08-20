<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class RegistrationLink extends Action {
	
	private static $link_sql = 'SELECT registration_id FROM voting_registration WHERE voting_id = ?';
	
	private $link;
	
	public function initialiseLink() {
		if ($this->getType() != 'voting') return;		
		if (false == $this->populateLink()) {
			$this->determineLink();
			$this->saveLink();
		}	
	}
	
	protected function determineLink() {
		$i = 1;
		$found = false;
		$sibling_id = 0;
		//try to find a registration
		while(!$found) {
			try {				
				$sibling_type = $this->getSiblingInfoByPosition($this->getNumber(), -$i, 'getType');
				if ($sibling_type == 'registration') {
					$found = true;
					$sibling_id = $this->getSiblingInfoByPosition($this->getNumber(), -$i, 'getId');
				}
				else {
					$i++;
				}
			}
			catch(Exception $e) {
				$found = true;
			}
		}
		$this->link = $sibling_id;
	}
	
	protected function saveLink() {
		$this->Factory->SaveObject('voting_registration', array('registration_id' => $this->link, 'voting_id' => $this->getId()), array('id'));
	}
	
	protected function populateLink() {
		$id = $this->Factory->getVar(self::$link_sql, array($this->getId()));
		if (NULL === $id) {			
			return false;			
		}
		else {
			$this->link = $id;
			return true;
		}
	}
	
}

?>
