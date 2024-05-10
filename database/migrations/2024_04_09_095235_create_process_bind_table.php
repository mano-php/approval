<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('process_code_bind')) {
            Schema::create('process_code_bind', function (Blueprint $table) {
                $table->comment('审批模板数据关联');
                $table->increments('id');
                $table->string('data_sources')->index()->comment('数据源');
                $table->string('process_code')->index()->default('')->comment('流程ID');
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
        Schema::dropIfExists('process_code_bind');
    }
};
