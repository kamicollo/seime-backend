<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateSubquestionsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('subquestions', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('questions_id')->index('questions_id_2');
			$table->boolean('number')->index('number');
			$table->dateTime('start_time');
			$table->dateTime('end_time');
			$table->unique(['questions_id','number'], 'questions_id');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('subquestions');
	}

}
