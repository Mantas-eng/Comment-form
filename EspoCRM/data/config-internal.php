<?php
return [
  'database' => [
    'host' => 'localhost',
    'port' => '',
    'charset' => NULL,
    'dbname' => 'Espocrm_db',
    'user' => 'admin',
    'password' => '',
    'platform' => 'Mysql'
  ],
  'smtpPassword' => 'Songokas*012',
  'logger' => [
    'path' => 'data/logs/espo.log',
    'level' => 'WARNING',
    'rotation' => true,
    'maxFileNumber' => 30,
    'printTrace' => false,
    'databaseHandler' => false,
    'sql' => false,
    'sqlFailed' => false
  ],
  'restrictedMode' => false,
  'cleanupAppLog' => true,
  'cleanupAppLogPeriod' => '30 days',
  'webSocketMessager' => 'ZeroMQ',
  'clientSecurityHeadersDisabled' => false,
  'clientCspDisabled' => false,
  'clientCspScriptSourceList' => [
    0 => 'https://maps.googleapis.com'
  ],
  'adminUpgradeDisabled' => false,
  'isInstalled' => true,
  'microtimeInternal' => 1739900218.869207,
  'cryptKey' => 'cd77dc3f42ed3b5e24c650fd1934bfe4',
  'hashSecretKey' => 'b3ec5f50a28c28d0c5ae419924240bbb',
  'defaultPermissions' => [
    'user' => 1,
    'group' => 1
  ],
  'actualDatabaseType' => 'mariadb',
  'actualDatabaseVersion' => '10.4.28',
  'instanceId' => '79ea2110-8a2b-4573-b818-862993c3a1e5'
];
