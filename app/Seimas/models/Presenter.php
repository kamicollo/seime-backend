<?php

namespace Seimas\models;

class Presenter extends AbstractChild {
	
	protected $fillable = [];
	protected $table = 'presenters';
	protected $primaryKey = 'id';
	public $timestamps = false;
	
	public function item() {
		return $this->belongsTo('Seimas\models\Item', 'items_id', $this->primaryKey);
	}
	
	public function __parent() {
		return $this->item();
	}
}