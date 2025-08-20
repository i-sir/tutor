<?php return array(
    'root' => array(
        'name' => 'thinkcmf/plugins-oss',
        'pretty_version' => '1.0.0+no-version-set',
        'version' => '1.0.0.0',
        'reference' => NULL,
        'type' => 'cmf-plugin',
        'install_path' => __DIR__ . '/../../',
        'aliases' => array(),
        'dev' => true,
    ),
    'versions' => array(
        'aliyuncs/oss-sdk-php' => array(
            'pretty_version' => 'v2.6.0',
            'version' => '2.6.0.0',
            'reference' => '572d0f8e099e8630ae7139ed3fdedb926c7a760f',
            'type' => 'library',
            'install_path' => __DIR__ . '/../aliyuncs/oss-sdk-php',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'thinkcmf/plugins-oss' => array(
            'pretty_version' => '1.0.0+no-version-set',
            'version' => '1.0.0.0',
            'reference' => NULL,
            'type' => 'cmf-plugin',
            'install_path' => __DIR__ . '/../../',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
    ),
);
