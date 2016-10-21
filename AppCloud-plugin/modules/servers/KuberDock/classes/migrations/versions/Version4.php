<?php

namespace migrations\versions;

use models\addon\Resources;

class Version4 implements \migrations\VersionInterface
{
    public function up()
    {
        $scheme = \models\Model::getConnectionResolver()->connection()->getSchemaBuilder();

        $scheme->create('KuberDock_resources', function ($table) {
            /* @var \Illuminate\Database\Schema\Blueprint $table */
            $table->increments('id');
            $table->integer('user_id');
            $table->string('name');
            $table->enum('type', array(
                Resources::TYPE_IP,
                Resources::TYPE_PD,

            ));
            $table->string('status', 32)->default(Resources::STATUS_ACTIVE );

            $table->index('name');
        });

        $scheme->create('KuberDock_resource_pods', function ($table) {
            /* @var \Illuminate\Database\Schema\Blueprint $table */
            $table->string('pod_id', 64)->nullable();
            $table->integer('resource_id', false, true);
            $table->integer('item_id');

            $table->index('pod_id');
            $table->index('resource_id');

            $table->foreign('resource_id')->references('id')->on('KuberDock_resources')->onDelete('cascade');
            $table->foreign('item_id')->references('id')->on('KuberDock_items')->onDelete('cascade');
        });
    }

    public function down()
    {
        return [];
    }
}
