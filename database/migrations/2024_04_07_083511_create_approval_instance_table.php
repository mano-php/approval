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
        if (!Schema::hasTable('approval_instance')) {
            Schema::create('approval_instance', function (Blueprint $table) {
                $table->comment('审批实例');
                $table->increments('id');
                $table->bigInteger('approval_bind_id')->index()->comment('表单');
                $table->bigInteger('originator_user_id')->index()->comment('发起人ID');
                $table->string('title')->default('')->comment('标题');
                $table->string('process_instance_id')->default('')->comment('实例ID');
                $table->string('url')->default('')->comment('详情链接');
                $table->text('approval_bind_data')->comment('审批数据');
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
        Schema::dropIfExists('approval_instance');
    }
};
