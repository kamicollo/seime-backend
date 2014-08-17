<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateSittingsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('sittings', function(Blueprint $table)
		{
			$table->integer('id', true);
			$table->boolean('number');
			$table->text('type');
			$table->text('transcript_url');
			$table->text('recording_url');
			$table->text('protocol_url');
			$table->dateTime('start_time');
			$table->time('effective_length');
			$table->text('url');
			$table->dateTime('end_time');
			$table->text('participation_url');
			$table->boolean('sessions_id');
			$table->enum('cadency', array('2008-2012','2012-2016','2004-2008','1996-2000','2000-2004'))->default('2012-2016')->index('cadency');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('sittings');
	}

}
