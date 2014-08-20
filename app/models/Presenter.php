<?php

namespace Seimas;

class Presenter extends \Eloquent {
	protected $fillable = [];
	protected $table = 'presenters';
	protected $primaryKey = 'id';
	public $timestamps = false;
	
	public function item() {
		return $this->belongsTo('Seimas\Item', 'items_id', $this->primaryKey);
	}
}