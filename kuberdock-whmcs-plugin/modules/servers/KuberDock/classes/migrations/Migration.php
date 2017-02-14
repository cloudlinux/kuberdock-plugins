<?php

namespace migrations;

use models\addon\Migration as MigrationModel;
use Illuminate\Database\Capsule\Manager as Capsule;

class Migration
{
    public static function check()
    {
        $last = self::last();
        $available = self::getAvailable($last);

        return (bool) $available;
    }

    public static function up()
    {
        if (!Capsule::schema()->hasTable(MigrationModel::tableName())) {
            throw new \Exception('There is no table ' . MigrationModel::tableName());
        }

        $last = self::last();

        $available = self::getAvailable($last);

        foreach ($available as $versionNumber) {
            self::run($versionNumber, 'up');

            MigrationModel::firstOrCreate([
                'version' => $versionNumber,
            ]);
        }
    }

    public static function down($target = null)
    {
        $existing = MigrationModel::where('version', '>', $target)->orderBy('version', 'desc')->first();

        foreach ($existing as $row) {
            self::run($row->version, 'down');

            MigrationModel::where('version', $row->version)->delete();
        }
    }

    private static function run($versionNumber, $direction)
    {
        $className = '\migrations\versions\Version' . $versionNumber;

        /** @var VersionInterface $version */
        $version = new $className;

        if (!is_array($version->$direction())) {
            return;
        }

        $db = \models\Model::getConnectionResolver();

        foreach ($version->$direction() as $sql) {
            $db->statement($sql);
        };
    }

    public static function last()
    {
        $last = MigrationModel::orderBy('version', 'desc')->first();

        if ($last) {
            return (int) $last->version;
        }

        return -1;
    }

    public static function getAvailable($min = 0)
    {
        $versions_path = __DIR__ . '/versions';

        $files = glob($versions_path . '/*.php');

        # get versions of available migrations classes
        $path = preg_replace('#(\.|\/)#i', '\\\\${1}', $versions_path);
        $files = preg_replace("#(" . $path . "\/Version)|(\.php)#i", '', $files);

        $files = array_filter($files, function($item) use ($min) {
            return $item > $min;
        });

        sort($files);

        return $files;
    }

    public static function fillByActivation()
    {
        $migrations = \migrations\Migration::getAvailable();
        foreach ($migrations as $version) {
            MigrationModel::create(['version' => $version]);
        }
    }
}