<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLogTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('log', function(Blueprint $table) {
			$table->increments('id');
			$table->timestamp('timestamp')->index('log_timestamp_idx');
			$table->string('key')->index('log_key_idx');
			$table->double('value')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('log');
	}

}
