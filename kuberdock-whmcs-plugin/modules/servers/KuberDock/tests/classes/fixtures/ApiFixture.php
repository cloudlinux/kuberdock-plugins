<?php

namespace tests\fixtures;


class ApiFixture
{
    public static function getKubes()
    {
        return [
            [
                'available' => false,
                'cpu' => 0.12,
                'cpu_units' => 'Cores',
                'disk_space' => 1,
                'disk_space_units' => 'GB',
                'id' => 0,
                'included_traffic' => 0,
                'is_default' => NULL,
                'memory' => 64,
                'memory_units' => 'MB',
                'name' => 'Tiny',
            ],
            [
                'available' => true,
                'cpu' => 0.25,
                'cpu_units' => 'Cores',
                'disk_space' => 1,
                'disk_space_units' => 'GB',
                'id' => 1,
                'included_traffic' => 0,
                'is_default' => true,
                'memory' => 128,
                'memory_units' => 'MB',
                'name' => 'Standard',
            ],
            [
                'available' => false,
                'cpu' => 0.25,
                'cpu_units' => 'Cores',
                'disk_space' => 3,
                'disk_space_units' => 'GB',
                'id' => 2,
                'included_traffic' => 0,
                'is_default' => NULL,
                'memory' => 256,
                'memory_units' => 'MB',
                'name' => 'High memory',
            ],
        ];
    }

    public static function getPackages()
    {
        return [
            [
                'count_type' => 'payg',
                'currency' => 'USD',
                'first_deposit' => 0,
                'id' => 1,
                'is_default' => NULL,
                'kubes' => [
                    [
                        'available' => true,
                        'cpu' => 0.25,
                        'cpu_units' => 'Cores',
                        'disk_space' => 1,
                        'disk_space_units' => 'GB',
                        'id' => 1,
                        'included_traffic' => 0,
                        'is_default' => true,
                        'memory' => 128,
                        'memory_units' => 'MB',
                        'name' => 'Standard',
                        'price' => 0,
                    ],
                ],
                'name' => 'Trial package',
                'period' => 'month',
                'prefix' => '$',
                'price_ip' => 0,
                'price_over_traffic' => 0,
                'price_pstorage' => 0,
                'suffix' => ' USD',
            ],
            [
                'count_type' => 'fixed',
                'currency' => 'USD',
                'first_deposit' => 0,
                'id' => 0,
                'is_default' => true,
                'kubes' => [
                    [
                        'available' => false,
                        'cpu' => 0.25,
                        'cpu_units' => 'Cores',
                        'disk_space' => 3,
                        'disk_space_units' => 'GB',
                        'id' => 2,
                        'included_traffic' => 0,
                        'is_default' => NULL,
                        'memory' => 256,
                        'memory_units' => 'MB',
                        'name' => 'High memory',
                        'price' => 0,
                    ],
                    [
                        'available' => true,
                        'cpu' => 0.25,
                        'cpu_units' => 'Cores',
                        'disk_space' => 1,
                        'disk_space_units' => 'GB',
                        'id' => 1,
                        'included_traffic' => 0,
                        'is_default' => true,
                        'memory' => 128,
                        'memory_units' => 'MB',
                        'name' => 'Standard',
                        'price' => 5,
                    ],
                    [
                        'available' => false,
                        'cpu' => 0.12,
                        'cpu_units' => 'Cores',
                        'disk_space' => 1,
                        'disk_space_units' => 'GB',
                        'id' => 0,
                        'included_traffic' => 0,
                        'is_default' => NULL,
                        'memory' => 64,
                        'memory_units' => 'MB',
                        'name' => 'Tiny',
                        'price' => 2,
                    ],
                ],
                'name' => 'Standard package',
                'period' => 'month',
                'prefix' => '$',
                'price_ip' => 2,
                'price_over_traffic' => 0,
                'price_pstorage' => 3,
                'suffix' => ' USD',
            ],
        ];
    }

    public static function getDefaultKubeType()
    {
        return [
            'default' => [
                'kubeType' => [
                    'available' => true,
                    'cpu' => 0.25,
                    'cpu_units' => 'Cores',
                    'disk_space' => 1,
                    'disk_space_units' => 'GB',
                    'id' => 1,
                    'included_traffic' => 0,
                    'is_default' => true,
                    'memory' => 128,
                    'memory_units' => 'MB',
                    'name' => 'Standard',
                ],
            ],
        ];
    }

    public static function getDefaultPackageId()
    {
        return [
            'count_type' => 'fixed',
            'currency' => 'USD',
            'first_deposit' => 0,
            'id' => 0,
            'is_default' => true,
            'name' => 'Standard package',
            'period' => 'month',
            'prefix' => '$',
            'price_ip' => 2,
            'price_over_traffic' => 0,
            'price_pstorage' => 3,
            'suffix' => ' USD',
        ];
    }

    public static function getPodWithResources($podId)
    {
        return [
            'id' => $podId,
            'name' => 'Name ' . $podId,
            'volumes' => [
                [
                    'persistentDisk' => [
                        'pdSize' => 2,
                        'pdName' => 'nginx_test'
                    ],
                    'name' => 'tp5547kq4d'
                ]
            ],
            'kube_type' => 1,
            'containers' => [
                [
                    'kubes' => 1,
                    'name' => 'kniyq4s',
                    'image' => 'nginx',
                    'volumeMounts' => [
                        [
                            'mountPath' => '/var/log',
                            'name' => 'tp5547kq4d'
                        ]
                    ],
                    'ports' => [
                        [
                            'isPublic' => true,
                            'protocol' => 'tcp',
                            'containerPort' => 443,
                            'hostPort' => 443
                        ],
                        [
                            'isPublic' => true,
                            'protocol' => 'tcp',
                            'containerPort' => 80,
                            'hostPort' => 80
                        ],
                    ],
                ],
            ],
            'public_ip' => '192.168.1.1',
        ];
    }

    public static function getPodWithoutResources($podId)
    {
        return [
            'id' => $podId,
            'name' => 'Name ' . $podId,
            'volumes' => [],
            'kube_type' => 1,
            'containers' => [
                [
                    'kubes' => 1,
                    'name' => 'kniyq4s',
                    'image' => 'nginx',
                    'volumeMounts' => [
                        [
                            'mountPath' => '/var/log',
                            'name' => 'tp5547kq4d'
                        ]
                    ],
                    'ports' => [
                        [
                            'isPublic' => false,
                            'protocol' => 'tcp',
                            'containerPort' => 443,
                            'hostPort' => 443
                        ],
                        [
                            'isPublic' => false,
                            'protocol' => 'tcp',
                            'containerPort' => 80,
                            'hostPort' => 80
                        ],
                    ],
                ],
            ],
        ];
    }

    public static function createPodFromYaml()
    {
        return [
            'status' => 'OK',
            'data' => [
                'id' => 'pod_id',
                'name' => 'redis',
                'volumes' => [
                    [
                        'persistentDisk' => [
                            'pdSize' => 1,
                            'pdName' => 'redis_random'
                        ],
                        'name' => 'tp5547kq4d'
                    ]
                ],
                'kube_type' => 1,
                'containers' => [
                    [
                        'kubes' => 1,
                        'name' => 'kniyq4s',
                        'image' => 'redis:3',
                        'volumeMounts' => [
                            [
                                'mountPath' => '/data',
                                'name' => 'redis-persistent-storage'
                            ]
                        ],
                        'ports' => [
                            [
                                'isPublic' => true,
                                'protocol' => 'tcp',
                                'containerPort' => 6379,
                                'hostPort' => 6379
                            ]
                        ],
                    ],
                ],
            ]
        ];
    }

    public static function getIpPoolStatWithIp()
    {
        return [
            'data' => [
                [
                    'id' => '192.168.1.1',
                    'pod' => 'redis',
                    'pod_id' => 'pod_id#1',
                ],
            ],
            'status' => 'OK',
        ];
    }

    public static function getIpPoolStatNoIp()
    {
        return [
            'data' => [],
            'status' => 'OK',
        ];
    }

    public static function getUsage()
    {
        return [
            'status' => 'OK',
            'data' => [
                'ip_usage' =>  [
                    [
                        'start' => '2016-10-02 10:00:00',
                        'end' =>  '2016-10-02 20:00:00',
                        'ip_address' => '192.168.1.1',
                        'pod_id' => 'pod_id'
                    ]
                ],
                'pd_usage' => [
                    [
                        'start' => '2016-10-02 10:00:00',
                        'end' => '2016-10-02 20:00:00',
                        'pd_name' => 'storage name',
                        'size' => 1
                    ]
                ],
                'pods_usage' => [
                    [
                        'id' => 'pod_id',
                        'kube_id' => 1,
                        'kubes' => 2,
                        'name' => 'pod_name',
                        'time' => [
                        'redis' => [
                                [
                                    'start' => '2016-10-02 10:00:00',
                                    'end' => '2016-10-02 20:00:00',
                                    'kubes' => 1
                                ],
                                [
                                    'start' => '2016-10-02 10:00:00',
                                    'end' => '2016-10-02 20:00:00',
                                    'kubes' => 2
                                ]
                            ]
                        ]
                    ]
                ],
            ]
        ];
    }
}