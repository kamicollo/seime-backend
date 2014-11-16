<?php

namespace Seimas\factories;
use Log;

class ParserFactory {
	
	protected $map = [
		'Seimas\models\Seimas' => 'Seimas\parsers\SeimasParser',
		'Seimas\models\Cadency' => 'Seimas\parsers\CadencyParser',
		'Seimas\models\Session' => 'Seimas\parsers\SessionParser',
		'Seimas\models\Sitting' => 'Seimas\parsers\SittingParser',
		'Seimas\models\Question' => 'Seimas\parsers\QuestionParser',
		'Seimas\models\Action'	=> 'Seimas\parsers\ActionParser',
		'Seimas\models\Registration' => 'Seimas\parsers\RegistrationParser',
		'Seimas\models\Vote' => 'Seimas\parsers\VoteParser',
		'Seimas\models\Speech' => 'Seimas\parsers\SpeechParser',
		'Seimas\models\UnanimousVote' => 'Seimas\parsers\UnanimousVoteParser',
		'SittingParticipation' => 'Seimas\parsers\SittingParticipationParser',
		'Seimas\models\Item' => 'Seimas\parsers\ItemParser',
		'Seimas\models\Presenter' => 'Seimas\parsers\PresenterParser',
		'VoteData' => 'Seimas\parsers\VoteDataParser',
		'RegistrationData' => 'Seimas\parsers\RegistrationDataParser',
	];
	
	/**
	 *
	 * @param Illuminate\Database\Eloquent
	 * @return \Seimas\parsers\AbstractParser
	 */
	public function resolve($object) {
		$class_name = get_class($object);
		return $this->resolveByClass($class_name);
	}
	/**
	 *
	 * @param string $class_name
	 * @return \Seimas\parsers\AbstractParser
	 */
	public function resolveByClass($class_name) {
		if (array_key_exists($class_name, $this->map)) {
			$class = $this->map[$class_name];
			return new $class($this);
		} 
	}
	
}
