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
        if (!Schema::hasTable('news')) {
            Schema::create('news', function (Blueprint $table) {
                $table->comment('新闻发布');
                $table->increments('id');
                $table->string('title')->default('')->comment('标题');
                $table->string('desc')->default('')->comment('描述');
                $table->string('content')->default('')->comment('新闻内容');
                $table->integer('status')->default(new \Illuminate\Database\Query\Expression('1'))->comment('状态');
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
        Schema::dropIfExists('news');
    }
};
