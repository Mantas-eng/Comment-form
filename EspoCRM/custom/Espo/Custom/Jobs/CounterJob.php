<?php

namespace Espo\Custom\Jobs;

use Espo\Core\Jobs\Job;
use Espo\Core\ORM\EntityManager;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\File\Manager as FileManager;

class CounterJob implements Job
{
    protected $entityManager;
    protected $config;
    protected $fileManager;

    public function __construct(
        EntityManager $entityManager,
        Config $config,
        FileManager $fileManager
    ) {
        $this->entityManager = $entityManager;
        $this->config = $config;
        $this->fileManager = $fileManager;
    }

    public function run(): void
    {
        // Create new Counter record
        $counter = $this->entityManager->createEntity('Counter', [
            'name' => 'Metrics - ' . date('Y-m-d H:i:s'),
            'diskSize' => $this->calculateDiskSize(),
            'size' => $this->calculateDatabaseSize(),
            'numberOfRecords' => $this->countAllRecords(),
            'numberOfUsers' => $this->countUsers()
        ]);

        // Send data via API
        $this->sendDataViaApi($counter);
    }

    protected function calculateDiskSize(): float
    {
        $dataDir = $this->config->get('dataDir', 'data');
        $size = 0;

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dataDir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            $size += $file->getSize();
        }

        return round($size / (1024 * 1024), 2);
    }

    protected function calculateDatabaseSize(): float
    {
        $pdo = $this->entityManager->getPDO();
        $dbType = $this->config->get('database.driver');

        if ($dbType === 'mysql') {
            $dbName = $this->config->get('database.dbname');
            $sth = $pdo->prepare("SELECT SUM(data_length + index_length) / 1024 / 1024 as size 
                                 FROM information_schema.TABLES 
                                 WHERE table_schema = ?");
            $sth->execute([$dbName]);
            $row = $sth->fetch(\PDO::FETCH_ASSOC);
            return round($row['size'], 2);
        }

        return 0.0; // Default return if DB type not supported
    }

    protected function countAllRecords(): int
    {
        $pdo = $this->entityManager->getPDO();
        $count = 0;

        // Get all entity tables
        $metadata = $this->entityManager->getMetadata();
        $entityDefs = $metadata->get();

        foreach ($entityDefs as $entityName => $entityDef) {
            if (!isset($entityDef['table']) || !$entityDef['table']) continue;

            try {
                $sth = $pdo->prepare("SELECT COUNT(*) as count FROM " . $entityDef['table']);
                $sth->execute();
                $row = $sth->fetch(\PDO::FETCH_ASSOC);
                $count += $row['count'];
            } catch (\Exception $e) {
                // Skip if table doesn't exist
            }
        }

        return $count;
    }

    protected function countUsers(): int
    {
        $count = $this->entityManager
            ->getRepository('User')
            ->where(['isActive' => true])
            ->count();

        return $count;
    }

    protected function sendDataViaApi($counter): void
    {
        $integration = $this->entityManager
            ->getRepository('Integration')
            ->where(['id' => 'Counter'])
            ->findOne();

        if (!$integration) return;

        $apiKey = $integration->get('apiKey');
        $secretKey = $integration->get('secretKey');
        $destinationUrl = $integration->get('destinationUrl');

        if (!$apiKey || !$secretKey || !$destinationUrl) return;

        // Initialize Guzzle client
        $client = new \GuzzleHttp\Client();

        try {
            $response = $client->request('POST', $destinationUrl . '/api/v1/Counter', [
                'headers' => [
                    'X-Api-Key' => $apiKey,
                    'X-Auth-Method' => 'ApiKey',
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'name' => $counter->get('name'),
                    'diskSize' => $counter->get('diskSize'),
                    'size' => $counter->get('size'),
                    'numberOfRecords' => $counter->get('numberOfRecords'),
                    'numberOfUsers' => $counter->get('numberOfUsers')
                ]
            ]);
        } catch (\Exception $e) {
            // Log error
            $GLOBALS['log']->error('CounterJob: Failed to send data via API: ' . $e->getMessage());
        }
    }
}