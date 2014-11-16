<?php

namespace Seimas\models;

class SeimasModel extends \Eloquent {
	
	public function save(array $options = [], $recursively = false) {
		if (!($this instanceof ChildInterface)) {
			parent::save($options);
		} else {
			$this->saveAsChild($options);
		}
		if ($recursively) {
			if ($this instanceof AncestorInterface) {
				foreach($this->getDescendants() as $children) {
					foreach($children as $child) {
						if ($child->id === null && $child->incrementing === false) {
							\Log::warning('A child without proper id found, not saving', [$child]);
						} else {
							$child->save($options, true);
						}
					}
				}
			}
		}
	}
	
	protected function saveAsChild(array $options = []) {
		if (!$this->hasParent()) {
			\Log::warning('Saving element without parent - will fail if parent not in DB yet!');
			try {
				parent::save($options, false);
			} catch(\Exception $e) {
				\Log::error($e);
			}
		} elseif($this->getParent()->exists) {
			$this->__parent()->associate($this->getParent());
			parent::save($options, false);
		} elseif ($this->getParent() instanceof \Eloquent) {
			throw new \Exception('Trying to save a model whose parent is not yet saved is not allowed');
		} else {
			parent::save($options, false);
		}
	}
	
	public function __sleep() {
		return ['connection', 'table', 'primaryKey', 'perPage', 'incrementing', 'timestamps', 'attributes', 'original', 'relations', 'hidden', 'visible', 'appends', 'fillable', 'guarded', 'dates', 'touches', 'observables', 'with', 'morphClass', 'exists'];
	}
}
