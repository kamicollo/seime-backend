<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateSubquestionsParticipationTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('subquestions_participation', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('subquestions_id')->index('subquestions_id_2');
			$table->integer('members_id')->index('members_id');
			$table->boolean('presence');
			$table->unique(['subquestions_id','members_id'], 'subquestions_id');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('subquestions_participation');
	}

}
