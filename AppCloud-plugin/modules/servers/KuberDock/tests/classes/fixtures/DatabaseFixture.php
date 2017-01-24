<?php


namespace tests\fixtures;


use Carbon\Carbon;
use models\addon\App;
use models\addon\resource\ResourceFactory;
use models\addon\Resources;
use models\billing\Admin;

class DatabaseFixture
{
    public static $packageIdFixed = 2;
    public static $packageIdPayg = 3;
    public static $podId = 'pod_id';
    public static $serviceId = 124;
    public static $userId = 13;
    public static $billableItemId = 45;
    public static $pdBillableItemId = 22;
    public static $pdResourceId = 1;

    public static function package()
    {
        return [
            [
                'id' => self::$packageIdFixed,
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
                'configoption8' => 0,           // first deposit
                'configoption9' => 'Fixed price', // Payment type
            ],
            [
                'id' => self::$packageIdPayg,
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
                'configoption8' => 5,           // first deposit
                'configoption9' => 'PAYG',
            ],
        ];
    }

    public static function packageRelation()
    {
        return [
            [
                'product_id' => self::$packageIdFixed,
                'kuber_product_id' => 0,
            ],
            [
                'product_id' => self::$packageIdPayg,
                'kuber_product_id' => 1,
            ],
        ];
    }

    public static function kubeTemplates()
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

    public static function kubePrices()
    {
        return [
            [
                'id' => 1,
                'template_id' => 1,
                'product_id' => self::$packageIdFixed,
                'kuber_product_id' => 0,
                'kube_price' => 0.2,
            ],
            [
                'id' => 2,
                'template_id' => 1,
                'product_id' => self::$packageIdPayg,
                'kuber_product_id' => 1,
                'kube_price' => 0.4,
            ],
        ];
    }

    public static function fixedItem()
    {
        return [
            'id' => 23,
            'user_id' => self::$userId,
            'service_id' => self::$serviceId,
            'pod_id' => self::$podId,
            'billable_item_id' => self::$billableItemId,
            'due_date' => (new Carbon())->addDays(10)->toDateString(),
        ];
    }

    public static function paygItem()
    {
        return [
            'id' => 13,
            'user_id' => self::$userId,
            'service_id' => self::$serviceId,
            'pod_id' => null,
            'billable_item_id' => null,
            'due_date' => null,
        ];
    }

    public static function pdItem()
    {
        return [
            'id' => 14,
            'user_id' => self::$userId,
            'service_id' => self::$serviceId,
            'pod_id' => self::$pdResourceId,
            'billable_item_id' => self::$pdBillableItemId,
            'type' => Resources::TYPE_PD,
            'due_date' => null,
        ];
    }

    public static function billableItem()
    {
        return [
            'id' => self::$billableItemId,
            'userid' => self::$userId,
            'description' => 'Standard package - Pod Name #1',
            'amount' => 2.2,
            'recur' => 1,
            'recurcycle' => 'Months',
            'invoiceaction' => 4,
            'duedate' => (new Carbon())->addDays(30),
        ];
    }

    public static function billableItemPD()
    {
        return [
            'id' => self::$pdBillableItemId,
            'userid' => self::$userId,
            'description' => 'Storage: some storage name #1',
            'amount' => 1,
            'recur' => 1,
            'recurcycle' => 'Months',
            'invoiceaction' => 4,
            'duedate' => (new Carbon())->addDays(30),
        ];
    }

    public static function resourcePD()
    {
        return [
            'id' => self::$pdResourceId,
            'user_id' => self::$userId,
            'name' => 'nginx_test',
            'type' => Resources::TYPE_PD,
            'status' => Resources::STATUS_DIVIDED,
        ];
    }

    public static function service()
    {
        return [
            'id' => self::$serviceId,
            'userid' => self::$userId,
            'packageid' => self::$packageIdFixed,
            'server' => 2,
            'username' => 'Service username',
            'domain' => 'Service domain',
            'domainstatus' => 'Active',
        ];
    }

    public static function admin()
    {
        return [
            'roleid' => Admin::FULL_ADMINISTRATOR_ROLE_ID,
            'disabled' => 0,
            'username' => 'admin',
        ];
    }

    public static function currency()
    {
        return [
            [
                'id' => 1,
                'code' => 'USD',
                'prefix' => '$',
                'suffix' => 'USD',
                'format' => 1,
                'rate' => 1,
                'default' => 1,
            ],
            [
                'id' => 2,
                'code' => 'GBP',
                'prefix' => '£',
                'suffix' => 'GBP',
                'format' => 1,
                'rate' => 0.5,
                'default' => 0,
            ],
        ];
    }

    public static function apps()
    {
        return [
            [
                'id' => 1,
                'user_id' => 0,
                'product_id' => self::$packageIdFixed,
                'kuber_product_id' => 0,
                'service_id' => self::$serviceId,
                'pod_id' => null,
                'data' => 'apiVersion: v1
appVariables:
  APP_DOMAIN: user_domain_list
  APP_NAME: redis
  PD_RAND: fheglxcm
  REDIS_HOST_PORT: 6379
kind: ReplicationController
kuberdock:
  appPackage:
    goodFor: beginner
    kubeType: 1
    name: S
  kuberdock_template_id: 1
  packageID: 0
  postDescription: |
    You have installed [b]Redis![/b]
    Please find more information about Redis software on the official website [url]http://redis.io/[/url]
    To access [b]Redis[/b] use:
    [b]Host:[/b] %PUBLIC_ADDRESS%
    [b]Port:[/b] 6379
  preDescription: |
    You are installing the application [b]Redis[/b].
    Redis is an open source key-value store that functions as a data structure server.
    Choose the amount of resources or use recommended parameters set by default.
    First choose package.
    When you click "Order now", you will get to order processing page.
  proxy:
    root:
      container: redis
      domain: user_domain_list
metadata:
  name: redis
spec:
  template:
    metadata:
      labels:
        name: redis
    spec:
      containers:
      - image: redis:3
        kubes: 1
        name: redis
        ports:
        - containerPort: 6379
          isPublic: true
          podPort: 6379
        readinessProbe:
          initialDelaySeconds: 1
          tcpSocket:
            port: 6379
        volumeMounts:
        - mountPath: /data
          name: redis-persistent-storage
        workingDir: /data
      restartPolicy: Always
      volumes:
      - name: redis-persistent-storage
        persistentDisk:
          pdName: redis_fheglxcm
          pdSize: 1
                ',
                'type' => ResourceFactory::TYPE_YAML,
                'referer' => 'https://master/apps/4d5f1b0a63abb729e60aeccdb748535cde87702c?plan=0',
            ],
            [
                'id' => 2,
                'user_id' => 0,
                'product_id' => self::$packageIdFixed,
                'kuber_product_id' => 0,
                'service_id' => self::$serviceId,
                'pod_id' => 'pod_id#2',
                'data' => json_encode(ApiFixture::getPodWithResources('pod_id#2')),
                'type' => ResourceFactory::TYPE_POD,
                'referer' => '',
            ],
            [
                'id' => 3,
                'user_id' => 0,
                'product_id' => self::$packageIdFixed,
                'kuber_product_id' => 0,
                'service_id' => self::$serviceId,
                'pod_id' => 'pod_id#2',
                'data' => json_encode([
                    'id' => 'pod_id#3',
                    'name' => 'New Pod #3',
                    'volumes' => [],
                    'kube_type' => 1,
                    'containers' => [
                        [
                            'kubes' => 1,
                            'name' => 'kniyq4s',
                            'image' => 'nginx',
                            'volumeMounts' => [],
                            'ports' => [
                                [
                                    'isPublic' => false,
                                    'protocol' => 'tcp',
                                    'containerPort' => 443,
                                    'hostPort' => 443
                                ]
                            ],
                        ],
                    ],
                ]),
                'type' => ResourceFactory::TYPE_POD,
                'referer' => '',
            ],
            [
                'id' => 4,
                'user_id' => 0,
                'product_id' => self::$packageIdPayg,
                'kuber_product_id' => 0,
                'service_id' => self::$serviceId,
                'pod_id' => 'pod_id#2',
                'data' => json_encode(ApiFixture::getPodWithResources('pod_id#2')),
                'type' => ResourceFactory::TYPE_POD,
                'referer' => '',
            ],
        ];
    }

    public static function client()
    {
        return [
            'id' => self::$userId,
            'defaultgateway' => 'mailin',
            'status' => 'Active',
            'currency' => 1,
            'email' => 'prev@mail.com',
            'lastname' => "prevlastname",
            'firstname' => "prevfirstname",
        ];
    }

    public static function pricing()
    {
        return [
            // USD
            [
                'type' => 'product',
                'currency' => 1,
                'relid' => self::$packageIdFixed,
                'msetupfee' => 0,
                'qsetupfee' => 0,
                'ssetupfee' => 0,
                'asetupfee' => 0,
                'bsetupfee' => 0,
                'tsetupfee' => 0,
                'monthly' => 0,
                'quarterly' => -1,
                'semiannually' => -1,
                'annually' => -1,
                'biennially' => -1,
                'triennially' => -1,
            ],
            // USD
            [
                'type' => 'product',
                'currency' => 1,
                'relid' => self::$packageIdPayg,
                'msetupfee' => 0,
                'qsetupfee' => 0,
                'ssetupfee' => 0,
                'asetupfee' => 0,
                'bsetupfee' => 0,
                'tsetupfee' => 0,
                'monthly' => 0,
                'quarterly' => -1,
                'semiannually' => -1,
                'annually' => -1,
                'biennially' => -1,
                'triennially' => -1,
            ],
            // GBP
            [
                'type' => 'product',
                'currency' => 2,
                'relid' => self::$packageIdFixed,
                'msetupfee' => 5,
                'qsetupfee' => 10,
                'ssetupfee' => 15,
                'asetupfee' => 20,
                'bsetupfee' => 25,
                'tsetupfee' => 30,
                'monthly' => 10,
                'quarterly' => -1,
                'semiannually' => -1,
                'annually' => -1,
                'biennially' => -1,
                'triennially' => -1,
            ],
            // Non product type
            [
                'type' => 'domainregister',
                'currency' => 1,
                'relid' => self::$packageIdFixed,
                'msetupfee' => 500,
                'qsetupfee' => 0,
                'ssetupfee' => 0,
                'asetupfee' => 0,
                'bsetupfee' => 0,
                'tsetupfee' => 0,
                'monthly' => 1000,
                'quarterly' => -1,
                'semiannually' => -1,
                'annually' => -1,
                'biennially' => -1,
                'triennially' => -1,
            ],
        ];
    }
}