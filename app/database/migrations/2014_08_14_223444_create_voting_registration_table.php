<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateVotingRegistrationTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('voting_registration', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('registration_id');
			$table->integer('voting_id')->unique('voting_id');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('voting_registration');
	}

}
