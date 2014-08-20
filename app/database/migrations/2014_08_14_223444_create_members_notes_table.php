<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateMembersNotesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('members_notes', function(Blueprint $table)
		{
			$table->integer('members_id');
			$table->enum('sittings_cadency', array('2008-2012','2012-2016','1996-2000','2000-2004','2004-2008'));
			$table->date('cadency_start')->nullable();
			$table->date('cadency_end')->nullable();
			$table->char('notes', 100);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('members_notes');
	}

}
