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

    public static function getPodById($podId)
    {
        return [
            'id' => $podId,
            'name' => 'New Pod #1',
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
        ];
    }
}