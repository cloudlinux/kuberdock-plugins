<?php

namespace migrations\versions;

use \models\addon\Resources;
use \models\addon\ResourcePods;
use \models\addon\ResourceItems;

class Version4 implements \migrations\VersionInterface
{
    public function up()
    {
        $db = \models\Model::getConnectionResolver();

        $db->statement('ALTER TABLE KuberDock_items MODIFY id INT(10) UNSIGNED AUTO_INCREMENT;');

        Resources::createTable();
        ResourcePods::createTable();
        ResourceItems::createTable();
    }

    public function down()
    {
        return [];
    }
}
