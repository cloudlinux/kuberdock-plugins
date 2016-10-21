<?php

namespace migrations\versions;

use models\addon\ItemInvoice;
use models\addon\Resources;
use models\addon\resource\ResourceFactory;
use models\billing\EmailTemplate;

class Version5 implements \migrations\VersionInterface
{
    public function up()
    {
        $db = \models\Model::getConnectionResolver();
        $scheme = $db->connection()->getSchemaBuilder();

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
            $table->string('type', 64)->default(Resources::TYPE_POD);
            $table->dropColumn('app_id');
            $table->dropColumn('invoice_id');
        });

        $db->statement('ALTER TABLE KuberDock_items CHANGE COLUMN pod_id pod_id VARCHAR(64) DEFAULT NULL');
        $db->statement('ALTER TABLE KuberDock_items CHANGE COLUMN billable_item_id billable_item_id INT(11) DEFAULT NULL');
        $db->statement('ALTER TABLE KuberDock_items CHANGE COLUMN status status VARCHAR(16) DEFAULT \'?\'', [Resources::STATUS_ACTIVE]);

        $emailTemplate = new EmailTemplate();
        $emailTemplate->createFromView($emailTemplate::RESOURCES_NOTICE_NAME,
            'KuberDock Resources Notice', 'resources_notice');
        $emailTemplate->createFromView($emailTemplate::RESOURCES_TERMINATION_NAME,
            'KuberDock Resources Termination', 'resources_expired');

        $emailTemplate->createFromView($emailTemplate::INVOICE_REMINDER_NAME,
            'KuberDock Invoice reminder', 'invoice_reminder');
    }

    public function down()
    {
        return [];
    }
}
