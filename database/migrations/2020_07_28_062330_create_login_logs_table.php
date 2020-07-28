<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoginLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('login_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('uuid')->nullable()->index();
            $table->string('email');
            $table->string('password');
            $table->tinyInteger('type')->default(User::LOGIN_SUCCESS);
            $table->timestamp('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('login_logs');
    }
}
