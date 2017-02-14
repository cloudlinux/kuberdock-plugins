<?php

namespace migrations\versions;

class Version2 implements \migrations\VersionInterface
{
    public function up()
    {
        return array('
            ALTER TABLE `KuberDock_price_changes` ADD INDEX `new_value` (`new_value`);
        ');
    }

    public function down()
    {
        return array('
            ALTER TABLE `KuberDock_price_changes` DROP INDEX `new_value`;
        ');
    }
}
