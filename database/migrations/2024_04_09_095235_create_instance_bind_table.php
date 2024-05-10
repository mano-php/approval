<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(!Schema::hasTable('instance_bind')){
            Schema::create('instance_bind', function (Blueprint $table) {
                $table->comment('审批模板数据关联');
                $table->increments('id');
                $table->string('instance_id')->index()->comment('实例ID');
                $table->bigInteger('key_id')->index()->comment('主键ID');
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('instance_bind');
    }
};
