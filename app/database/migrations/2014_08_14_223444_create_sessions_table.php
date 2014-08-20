<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateSessionsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('sessions', function(Blueprint $table)
		{
			$table->boolean('id')->primary();
			$table->text('url');
			$table->char('type', 20);
			$table->enum('kadencija', array('1996-2000','2000-2004','2004-2008','2008-2012','2012-2016'));
			$table->boolean('number');
			$table->date('start_date');
			$table->date('end_date');
			$table->time('effective_length');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('sessions');
	}

}
