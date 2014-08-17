<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateParticipationDataTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('participation_data', function(Blueprint $table)
		{
			$table->bigInteger('id', true)->unsigned();
			$table->integer('sittings_id')->index('sittings_id_2');
			$table->integer('members_id')->index('members_id');
			$table->float('hours_available', 10, 0);
			$table->float('hours_present', 10, 0);
			$table->boolean('official_presence')->index('official_presence');
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
		Schema::drop('participation_data');
	}

}
