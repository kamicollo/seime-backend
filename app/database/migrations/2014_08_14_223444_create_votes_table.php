<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateVotesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('votes', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('actions_id')->index('actions_id_2');
			$table->integer('members_id')->index('members_id');
			$table->char('fraction', 10);
			$table->char('vote', 10)->index('vote');
			$table->unique(['actions_id','members_id'], 'actions_id');
			$table->index(['actions_id','vote'], 'actions_id_3');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('votes');
	}

}
