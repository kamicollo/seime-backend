<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateMembersTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('members', function(Blueprint $table)
		{
			$table->integer('id')->primary();
			$table->char('fraction', 20);
			$table->text('image_src');
			$table->char('name', 100);
			$table->date('cadency_start');
			$table->date('cadency_end')->index('cadency_end');
			$table->string('notes', 100);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('members');
	}

}
