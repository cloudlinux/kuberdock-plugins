<?php


namespace tests\fixtures;


use Carbon\Carbon;
use models\billing\Admin;

class DatabaseFixture
{
    public static $packageId = 2;
    public static $podId = 'pod_id';
    public static $serviceId = 124;
    public static $userId = 13;
    public static $billableItemId = 45;

    public static function getPackage()
    {
        return [
            'id' => self::$packageId,
            'gid' => 1,
            'type' => 'other',
            'name' => 'KuberDock',
            'paytype' => 'onetime',
            'autosetup' => 'order',
            'servertype' => KUBERDOCK_MODULE_NAME,
            'servergroup' => 1,
            'hidden' => 0,
            'configoption3' => 'monthly',   // payment type
            'configoption5' => 1,           // price IP
            'configoption6' => 1,           // price PD
        ];
    }

    public static function getPackageRelation()
    {
        return [
            'product_id' => self::$packageId,
            'kuber_product_id' => 0,
        ];
    }

    public static function getKubeTemplates()
    {
        return [
            [
                'id' => 1,
                'kuber_kube_id' => 1,
                'kube_name' => 'Standard',
                'kube_type' => 0,
                'cpu_limit' => 0.25,
                'memory_limit' => 128,
                'hdd_limit' => 1,
                'traffic_limit' => 0,
                'server_id' => 1,
            ],
        ];
    }

    public static function getKubePrices()
    {
        return [
            [
                'id' => 1,
                'template_id' => 1,
                'product_id' => self::$packageId,
                'kuber_product_id' => 0,
                'kube_price' => 0.2,
            ],
        ];
    }

    public static function getFixedItem()
    {
        return [
            'id' => 23,
            'user_id' => self::$userId,
            'service_id' => self::$serviceId,
            'pod_id' => self::$podId,
            'billable_item_id' => self::$billableItemId,
            'due_date' => (new Carbon())->addDays(10)->format('Y-m-d'),
        ];
    }

    public static function getService()
    {
        return [
            'id' => self::$serviceId,
            'userid' => self::$userId,
            'packageid' => self::$packageId,
            'server' => 2,
            'username' => 'Service username',
            'domain' => 'Service domain',
            'domainstatus' => 'Active',
        ];
    }

    public static function getAdmin()
    {
        return [
            'roleid' => Admin::FULL_ADMINISTRATOR_ROLE_ID,
            'disabled' => 0,
            'username' => 'admin',
        ];
    }
}