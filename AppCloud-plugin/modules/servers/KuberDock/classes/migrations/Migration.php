<?php

namespace migrations;

use base\CL_Query;

class Migration
{
    private static $versions_path = '../modules/servers/KuberDock/classes/migrations/versions';

    public static function check()
    {
        $last = \KuberDock_Migrations::getLast();
        $available = self::getAvailable($last);

        return (bool) $available;
    }

    public static function up()
    {
        $last = \KuberDock_Migrations::getLast();

        $available = self::getAvailable($last);

        foreach ($available as $versionNumber) {
            self::run($versionNumber, 'up');
            \KuberDock_Migrations::addVersion($versionNumber);
        }
    }

    public static function down($target = null)
    {
        $existing = \KuberDock_Migrations::loadByMin($target);

        foreach ($existing as $version) {
            $versionNumber = $version['version'];
            self::run($versionNumber, 'down');
            \KuberDock_Migrations::removeVersion($versionNumber);
        }
    }

    private static function run($versionNumber, $direction)
    {
        $className = '\migrations\versions\Version' . $versionNumber;

        /** @var VersionInterface $version */
        $version = new $className;

        foreach ($version->$direction() as $sql) {
            CL_Query::model()->query($sql);
        };
    }

    private static function getAvailable($min)
    {
        $files = glob(self::$versions_path . '/*.php');

        # get versions of available migrations classes
        $path = preg_replace('#(\.|\/)#i', '\\\\${1}', self::$versions_path);
        $files = preg_replace("#(" . $path . "\/Version)|(\.php)#i", '', $files);

        $files = array_filter($files, function($item) use ($min) {
            return $item > $min;
        });

        sort($files);

        return $files;
    }
}