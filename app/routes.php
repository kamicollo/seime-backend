<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/

Route::get('/x', function()
{
	try {
		$http_client = new Seimas\http\GuzzleHttpClient(new \GuzzleHttp\Client(), true);
		$s_factory = new Seimas\factories\ScraperFactory($http_client);
		$o_factory = new Seimas\factories\ObjectFactory();
		$p_factory = new Seimas\factories\ParserFactory();
		$dataloader = new Seimas\DataLoader($s_factory, $o_factory, $p_factory);
		$object = new Seimas\models\Seimas();
		$url = 'http://www3.lrs.lt/pls/inter/w5_sale.kad_ses';
		if (PHP_SAPI != 'cli') {
			\Debugbar::info('no more free lunch in the main page!');
			return View::make('hello');
		} else {
			$dataloader->initialise($url, $object);
			$dataloader->initialiseDeeper($object, false);
			$cadency = $object->getChildren()[1];
			$dataloader->initialiseDeeper($cadency, true);
			return View::make('cli', ['object' => $object]);
		}
	} catch(\Exception $e) {
		\Debugbar::info($e);
	}
		
});

Route::get('/test', ['as' => 'test', function() {
	$http_client = new Seimas\http\GuzzleHttpClient(new \GuzzleHttp\Client(), true);
	$s_factory = new Seimas\factories\ScraperFactory($http_client);
	$p_factory = new Seimas\factories\ParserFactory();
	$dataloader = new Seimas\DataLoader($s_factory, $p_factory);
	$object = new Seimas\models\Session();
	//$url = 'http://www3.lrs.lt/pls/inter/w5_sale.kad_ses';
	$url = 'http://www3.lrs.lt/pls/inter/w5_sale.ses_pos?p_ses_id=95';
	//$url = 'http://www3.lrs.lt/pls/inter/w5_sale.fakt_pos?p_fakt_pos_id=-500676';
	//$url = 'http://www3.lrs.lt/pls/inter/w5_sale.klaus_stadija?p_svarst_kl_stad_id=-14189';
	//$url = 'http://www3.lrs.lt/pls/inter/w5_sale.klaus_stadija?p_svarst_kl_stad_id=-14201';
	//$object = new Seimas\models\Question();
	//$url = 'http://www3.lrs.lt/pls/inter/w5_sale.klaus_stadija?p_svarst_kl_stad_id=-13890';
	DB::table('sessions')->delete();
	//$object->sessions_id = 95;
	$dataloader->initialise($url, $object, true);
	//$dataloader->initialiseDeeper($object, false);
	//foreach($object->getChildren() as  $ch) {
		//$dataloader->initialiseDeeper($ch, false);
	//}
	DB::beginTransaction();
	try {
		$object->save([], true);
	} catch(\Exception $e) {
		Log::info($e);
	}
	DB::commit();
	return View::make('hello');
	return View::make('cli', ['object' => $object]);
	
}]);

Route::get('/createCadencies/', ['as' => 'cadencies', function() {
	$http_client = new Seimas\http\GuzzleHttpClient(new \GuzzleHttp\Client(), true);
	$s_factory = new Seimas\factories\ScraperFactory($http_client);
	$o_factory = new Seimas\factories\ObjectFactory();
	$p_factory = new Seimas\factories\ParserFactory();
	$dataloader = new Seimas\DataLoader($s_factory, $o_factory, $p_factory);
	$object = new Seimas\models\Seimas();
	$url = 'http://www3.lrs.lt/pls/inter/w5_sale.kad_ses';
	$dataloader->initialise($url, $object, false);
	$dataloader->initialiseDeeper($object, false);
	foreach($object->getChildren() as $ch) {
		$ch->save();
	}
	return View::make('hello');
}]);