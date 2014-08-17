<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateActionsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('actions', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('number', 20);
			$table->time('start_time');
			$table->time('end_time');
			$table->char('type', 20);
			$table->text('url')->index('url');
			$table->boolean('total_participants');
			$table->char('outcome', 20)->index('outcome');
			$table->text('voting_topic');
			$table->text('title');
			$table->integer('questions_id')->index('questions_id');
			$table->text('dom');
			$table->unique(['number','questions_id'], 'number');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('actions');
	}

}
