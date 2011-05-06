#!/usr/bin/env php
<?php

/*
$project = 'AcmePizza';

$dir     = __DIR__ . '/Model/xml/' . $project;
echo shell_exec(' php /var/www/' . $project . '/app/console doctrine:mapping:convert --force xml ' . $dir);
*/

$dir = '/var/www/AcmePizza/src/Acme/PizzaBundle/Resources/config/doctrine/';

require_once __DIR__ . '/../../autoload.php';

use Propel\Builder\ORM\BaseActiveRecord;
use Propel\Builder\ORM\ActiveRecord;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\ORM\Tools\DisconnectedClassMetadataFactory;
use Propel\Builder\Generator;

$config = new Configuration();
$config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache);

$driverImpl = new XmlDriver($dir);
$driverImpl->setFileExtension('.orm.xml');
$config->setMetadataDriverImpl($driverImpl);

$config->setProxyDir(__DIR__ . '/Proxies');
$config->setProxyNamespace('Proxies');

$cmf = new DisconnectedClassMetadataFactory();
$cmf->setEntityManager(\Doctrine\ORM\EntityManager::create(array(
    'driver' => 'pdo_sqlite',
    'path' => 'database.sqlite'
), $config));

$generator = new Generator();

foreach ($cmf->getAllMetadata() as $metadata) {

    /* @var $metadata Doctrine\ORM\Mapping\ClassMetadataInfo */
//var_dump($metadata->table['name']);
    /* identifiers are put on top of fieldMappings */
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
    
    $builder = new BaseActiveRecord($metadata);

    $builder->setMappingDriver(BaseActiveRecord::MAPPING_ANNOTATION);
    $builder->setAnnotationPrefix('orm');

    $generator->addBuilder($builder);
    if (false)$generator->addBuilder(new ActiveRecord($metadata));
}
echo "Generating classes for xml schemas...\n";
$generator->writeClasses(__DIR__ . '/Model');
echo "Class generation complete\n";
