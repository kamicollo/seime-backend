<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateSpeakersTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('speakers', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('members_id');
			$table->integer('actions_id')->index('actions_id');
			$table->unique(['members_id','actions_id'], 'members_id');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('speakers');
	}

}
