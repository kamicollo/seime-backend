<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePresentersTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('presenters', function(Blueprint $table)
		{
			$table->integer('id', true);
			$table->boolean('number');
			$table->integer('items_id')->index('items_id');
			$table->text('presenter');
			$table->unique(['number','items_id'], 'number');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('presenters');
	}

}
