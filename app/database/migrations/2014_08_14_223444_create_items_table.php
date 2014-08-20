<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateItemsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('items', function(Blueprint $table)
		{
			$table->integer('id', true);
			$table->boolean('number');
			$table->integer('questions_id')->index('questions_id');
			$table->text('title');
			$table->text('document_url');
			$table->text('related_doc_url');
			$table->unique(['number','questions_id'], 'number');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('items');
	}

}
