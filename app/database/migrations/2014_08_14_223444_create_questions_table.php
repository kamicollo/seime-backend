<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateQuestionsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('questions', function(Blueprint $table)
		{
			$table->integer('id', true);
			$table->dateTime('start_time');
			$table->dateTime('end_time');
			$table->text('url');
			$table->text('title');
			$table->integer('sittings_id')->index('sittings_id_2');
			$table->time('effective_length');
			$table->boolean('number');
			$table->unique(['sittings_id','number'], 'sittings_id');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('questions');
	}

}
