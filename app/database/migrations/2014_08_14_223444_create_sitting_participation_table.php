<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateSittingParticipationTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('sitting_participation', function(Blueprint $table)
		{
			$table->increments('id');
			$table->boolean('presence')->index('presence');
			$table->integer('sittings_id')->index('sittings_id_2');
			$table->integer('members_id')->index('members_id');
			$table->unique(['sittings_id','members_id'], 'sittings_id');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('sitting_participation');
	}

}
