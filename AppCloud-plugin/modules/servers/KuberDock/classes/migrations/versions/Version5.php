<?php

namespace migrations\versions;

use models\addon\ItemInvoice;
use models\addon\Resources;
use models\addon\resourceTypes\ResourceFactory;

class Version5 implements \migrations\VersionInterface
{
    public function up()
    {
        $scheme = \models\Model::getConnectionResolver()->connection()->getSchemaBuilder();

        $scheme->create('KuberDock_item_invoices', function ($table) {
            /* @var \Illuminate\Database\Schema\Blueprint $table */
            $table->integer('id');
            $table->integer('item_id');
            $table->integer('invoice_id');
            $table->string('status', 16);
            $table->enum('type', [
                ItemInvoice::TYPE_ORDER,
                ItemInvoice::TYPE_EDIT,
                ItemInvoice::TYPE_SWITCH,
            ])->default(ItemInvoice::TYPE_ORDER);
            $table->text('params')->nullable();
            $table->timestamps();

            $table->primary('id');
            $table->index('invoice_id');
            $table->index('item_id');

            $table->foreign('item_id')->references('id')->on('KuberDock_items')->onDelete('cascade');
        });

        $scheme->table('KuberDock_preapps', function ($table) {
            /* @var \Illuminate\Database\Schema\Blueprint $table */
            $table->enum('type', [
                ResourceFactory::TYPE_POD,
                ResourceFactory::TYPE_YAML,
            ])->default(ResourceFactory::TYPE_POD);

            $table->dropColumn('session_id');
        });

        $scheme->table('KuberDock_items', function ($table) {
            /* @var \Illuminate\Database\Schema\Blueprint $table */
            $table->increments('id');
            $table->string('pod_id', 64)->nullable()->change();
            $table->string('status', 16)->default(Resources::STATUS_ACTIVE)->change();
            $table->dropColumn('app_id');
            $table->dropColumn('invoice_id');
        });
    }

    public function down()
    {
        return [];
    }
}
