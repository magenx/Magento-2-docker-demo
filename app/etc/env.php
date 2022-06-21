<?php
return [
    'backend' => [
        'frontName' => getenv('ADMIN_PATH')
    ],
    'remote_storage' => [
        'driver' => 'file'
    ],
    'queue' => [
        'amqp' => [
            'host' => 'rabbitmq',
            'port' => '5672',
            'user' => 'magento',
            'password' => getenv('RABBITMQ_PASSWORD'),
            'virtualhost' => '/'
        ],
        'consumers_wait_for_messages' => 0
    ],
    'crypt' => [
        'key' => getenv('CRYPT_KEY')
    ],
    'db' => [
        'table_prefix' => '',
        'connection' => [
            'default' => [
                'host' => 'mariadb',
                'dbname' => getenv('DATABASE_NAME'),
                'username' => getenv('DATABASE_USER'),
                'password' => getenv('DATABASE_PASSWORD'),
                'model' => 'mysql4',
                'engine' => 'innodb',
                'initStatements' => 'SET NAMES utf8;',
                'active' => '1',
                'driver_options' => [
                    1014 => false
                ]
            ]
        ]
    ],
    'resource' => [
        'default_setup' => [
            'connection' => 'default'
        ]
    ],
    'x-frame-options' => 'SAMEORIGIN',
    'MAGE_MODE' => 'production',
    'session' => [
        'save' => 'redis',
        'redis' => [
            'host' => 'redis-session',
            'port' => '6379',
            'password' => getenv('REDIS_PASSWORD'),
            'timeout' => '2.5',
            'persistent_identifier' => '',
            'database' => '0',
            'compression_threshold' => '2048',
            'compression_library' => 'lz4',
            'log_level' => '3',
            'max_concurrency' => '6',
            'break_after_frontend' => '5',
            'break_after_adminhtml' => '30',
            'first_lifetime' => '600',
            'bot_first_lifetime' => '60',
            'bot_lifetime' => '7200',
            'disable_locking' => '0',
            'min_lifetime' => '60',
            'max_lifetime' => '2592000',
            'sentinel_master' => '',
            'sentinel_servers' => '',
            'sentinel_connect_retries' => '5',
            'sentinel_verify_master' => '0'
        ]
    ],
    'cache' => [
        'frontend' => [
            'default' => [
                'id_prefix' => '71f_',
                'backend' => 'Magento\\Framework\\Cache\\Backend\\Redis',
                'backend_options' => [
                    'server' => 'redis-cache',
                    'database' => '0',
                    'port' => '6380',
                    'password' => getenv('REDIS_PASSWORD'),
                    'compress_data' => '1',
                    'compression_lib' => 'l4z'
                ]
            ],
            'page_cache' => [
                'id_prefix' => '71f_'
            ]
        ],
        'allow_parallel_generation' => false
    ],
    'lock' => [
        'provider' => 'db',
        'config' => [
            'prefix' => ''
        ]
    ],
    'directories' => [
        'document_root_is_pub' => true
    ],
    'cache_types' => [
        'config' => 0,
        'layout' => 0,
        'block_html' => 0,
        'collections' => 0,
        'reflection' => 0,
        'db_ddl' => 0,
        'compiled_config' => 0,
        'eav' => 0,
        'customer_notification' => 0,
        'full_page' => 0,
        'config_integration' => 0,
        'config_integration_api' => 0,
        'translate' => 0,
        'config_webservice' => 0
    ],
    'downloadable_domains' => [
        getenv('DOMAIN')
    ],
    'install' => [
        'date' => 'Sun, 19 Jun 2022 18:45:26 +0000'
    ],
    'http_cache_hosts' => [
        [
            'host' => 'varnish',
            'port' => '8081'
        ]
    ],
	'deployment' => [
        'blue_green' => [
             'enabled' => true
        ]
    ],
    'system' => [
        'default' => [
            'catalog' => [
                'search' => [
                    'engine' => 'elasticsearch7',
                    'elasticsearch7_server_hostname' => 'elasticsearch',
                    'elasticsearch7_enable_auth' => '1',
                    'elasticsearch7_server_port' => '9200',
                    'elasticsearch7_index_prefix' => getenv('BRAND'),
                    'elasticsearch7_username' => 'elastic',
                    'elasticsearch7_password' => getenv('ELK_PASSWORD')
                ]
            ]
        ]
    ]
];

