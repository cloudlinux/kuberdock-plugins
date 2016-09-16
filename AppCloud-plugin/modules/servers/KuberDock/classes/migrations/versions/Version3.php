<?php

namespace migrations\versions;

class Version2 implements \migrations\VersionInterface
{
    public function up()
    {
        return array('
            ALTER TABLE `KuberDock_states` CHANGE COLUMN total_sum total_sum FLOAT(8,2) DEFAULT NULL;
        ');
    }

    public function down()
    {
        return array('
            ALTER TABLE `KuberDock_states` CHANGE COLUMN total_sum total_sum FLOAT(8,2) DEFAULT NOT NULL;
        ');
    }
}
