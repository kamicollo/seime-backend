<?php

namespace Seimas\models;
use \Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Validator;
/**
 * Description of DefaultParameterTrait
 *
 * @author aurimas
 */
trait DefaultParameterTrait {
	
	public function defaultPivotParameter(BelongsToMany $pivotQuery, $parameter, $value, $validation = null) {
		$validator = Validator::make([$parameter => $value], [$parameter => $validation]);
		if ($value === null) {
			return $pivotQuery;
		} elseif (
			($validation === null) or
			($validator->passes())
			) {
			return $pivotQuery->wherePivot($parameter, $value);
		} else { 
			throw new \InvalidArgumentException($validator->messages()->first());			
		}
	}
	
}
