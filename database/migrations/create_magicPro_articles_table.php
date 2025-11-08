<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;


return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            if (!Schema::hasTable('articles')) {
                Schema::create('articles', function (Blueprint $table) {
                    $table->id();
                    $table->integer('parentId')->default(0)->index();
                    $table->integer('npp')->default(0);
                    $table->string('name')->index();
                    $table->string('title')->default('');
                    $table->text('controller')->nullable();
                    $table->text('body')->nullable();
                    $table->string('templateName')->default('');
                    $table->boolean('directory')->default(false);
                    $table->boolean('menuOn')->default(false);
                    $table->boolean('isRoute')->default(false);
                    $table->text('routeParams')->nullable();
                    $table->timestamps();
                });

                DB::table('articles')->insert([
                    'parentId'     => 0,
                    'name'         => 'root',
                    'title'        => 'root',
                    'controller'   => '',
                    'body'         => '',
                    'templateName' => '',
                    'directory'    => false,
                    'menuOn'       => false,
                    'isRoute'      => false,
                    'routeParams'  => '{}',
                ]);

                DB::table('articles')->insert([
                    'parentId'     => 1,
                    'npp'          => 0,
                    'name'         => ART_NAME_404,
                    'title'        => ART_NAME_404,
                    'controller'   => '',
                    'body'         => '<p>Error 404</p>',
                    'templateName' => '',
                    'directory'    => false,
                    'menuOn'       => false,
                    'isRoute'      => false,
                    'routeParams'  => '{}',
                ]);

                DB::table('articles')->insert([
                    'parentId'     => 1,
                    'npp'          => 1,
                    'name'         => 'index',
                    'title'        => 'index',
                    'controller'   => '',
                    'body'         => '<p>Index page</p>',
                    'templateName' => '',
                    'directory'    => false,
                    'menuOn'       => false,
                    'isRoute'      => true,
                    'routeParams'  => '{
                                "useController": false,
                                "adminOnly": false,
                                "getEnable": false,
                                "utmParamsEnable": true,
                                "bindKeys": false,
                                "keysArr": []
                            }',
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
