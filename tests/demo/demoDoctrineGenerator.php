#!/usr/bin/php
<?php
/*
$project = 'AcmePizza';

$dir     = __DIR__ . '/Model/xml/' . $project;
echo shell_exec(' php /var/www/' . $project . '/app/console doctrine:mapping:convert --force xml ' . $dir);
*/

$dir = '/var/www/AcmePizza/src/Acme/PizzaBundle/Resources/config/doctrine/';

require_once __DIR__ . '/../../autoload.php';

$config = new \Doctrine\ORM\Configuration();
$config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache);

$driverImpl = new \Doctrine\ORM\Mapping\Driver\XmlDriver($dir);
$driverImpl->setFileExtension('.orm.xml');
$config->setMetadataDriverImpl($driverImpl);

$config->setProxyDir(__DIR__ . '/Proxies');
$config->setProxyNamespace('Proxies');

$cmf = new \Doctrine\ORM\Tools\DisconnectedClassMetadataFactory();
$cmf->setEntityManager(\Doctrine\ORM\EntityManager::create(array(
    'driver' => 'pdo_sqlite',
    'path' => 'database.sqlite'
), $config));

//print_r($cmf->getAllMetadata());
//print_r($cmf->getLoadedMetadata());
//exit();

$generator = new \Doctrine\ORM\Tools\EntityGenerator();
$generator->setGenerateAnnotations(!true);
$generator->setGenerateStubMethods(!true);
// $generator->setRegenerateEntityIfExists(false);
// $generator->setUpdateEntityIfExists(true);

foreach ($cmf->getAllMetadata() as $i => $metadata) {

    if ($i < 1) {
        continue;
    }

    print_r($metadata);
    exit();

    if (!false) {
        // identifiers are put on top of fieldMappings
        $identifiers = array();
        $positions   = array();

        $i = 0;
        foreach ($metadata->fieldMappings as $fieldMapping) {
            $positions[]   = $i;
            $identifiers[] = isset($fieldMapping['id']) ? 1 : 0;
            $i++;
        }

        array_multisort(
            $identifiers, SORT_DESC, SORT_NUMERIC,
            $positions, SORT_ASC, SORT_NUMERIC,
            $metadata->fieldMappings
        );
    }
    
    echo $generator->generateEntityClass($metadata) . "\n";

    
}
