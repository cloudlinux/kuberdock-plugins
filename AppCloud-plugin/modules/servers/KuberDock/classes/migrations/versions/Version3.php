<?php

namespace migrations\versions;

use models\addon\Resources;

class Version3 implements \migrations\VersionInterface
{
    public function up()
    {
        return array(
            'CREATE TABLE IF NOT EXISTS `KuberDock_resources` (
                id INT AUTO_INCREMENT,
                user_id INT,
                billable_item_id INT DEFAULT NULL,
                name VARCHAR (255),
                type ENUM("'. Resources::TYPE_IP .'", "'. Resources::TYPE_PD .'"),
                status VARCHAR(32) DEFAULT "'. Resources::STATUS_ACTIVE .'",
                PRIMARY KEY (id),
                INDEX (name)
            ) ENGINE=INNODB',

            'CREATE TABLE IF NOT EXISTS `KuberDock_resource_pods` (
                pod_id VARCHAR (255),
                resource_id INT,
                FOREIGN KEY (resource_id)
                    REFERENCES KuberDock_resources(id)
                    ON UPDATE CASCADE ON DELETE CASCADE
            ) ENGINE=INNODB',

            'ALTER TABLE KuberDock_items ADD COLUMN `type` VARCHAR (64) DEFAULT "'. Resources::TYPE_POD  .'"',
            'ALTER TABLE KuberDock_items CHNAGE COLUMN pod_id pod_id VARCHAR (64) NULL',
        );
    }

    public function down()
    {
        return array(
            'DROP TABLE IF EXISTS `KuberDock_resources`',
            'ALTER TABLE KuberDock_items DROP COLUMN `type`',
        );
    }
}
