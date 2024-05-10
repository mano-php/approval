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
        if (!Schema::hasTable('approval_bind')) {
            Schema::create('approval_bind', function (Blueprint $table) {
                $table->comment('审批流关联');
                $table->increments('id');
                $table->string('name')->index()->comment('名称');
                $table->string('table_name')->index()->default('')->comment('表名');
                $table->string('process_code')->nullable()->index()->default('')->comment('表单ID');
                $table->string('description')->nullable()->default('')->comment('备注');
                $table->text('columns')->nullable()->comment('表字段信息');
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
        Schema::dropIfExists('approval_bind');
    }
};
