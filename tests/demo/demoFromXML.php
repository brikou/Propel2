#!/usr/bin/php
<?php

/*
$project = 'AcmePizza';

$dir     = __DIR__ . '/Model/xml/' . $project;
echo shell_exec(' php /var/www/' . $project . '/app/console doctrine:mapping:convert --force xml ' . $dir);
*/

//$dir = '/var/www/AcmePizza/src/Acme/PizzaBundle/Resources/config/doctrine';
$dir = '/var/www/RdfIntranet2/src/Rdf/AgendaBundle/Resources/config/doctrine';

$outputDirectory = __DIR__ . '/Model';

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

    $builder = new BaseActiveRecord($metadata);
    $builder->setMappingDriver(BaseActiveRecord::MAPPING_STATIC_PHP | BaseActiveRecord::MAPPING_ANNOTATION);
    $builder->setAnnotationPrefix('orm');
    $generator->addBuilder($builder);
    if (false) $generator->addBuilder(new ActiveRecord($metadata));
}

echo "Generating classes for xml schemas...\n";
$generator->writeClasses($outputDirectory);
echo "Class generation complete\n";

// some cleanup

if (true) foreach ($generator->getBuilders() as $i => $builder) {

    // fetching position of columns

    $xml_filename = $dir . '/' . str_replace(array('/Base', '/', '.php'), array('', '.', $driverImpl->getFileExtension()), $builder->getOutputName());
    $xml = file_get_contents($xml_filename);

    $xml = new SimpleXMLElement(file_get_contents($xml_filename));
    $xml->registerXPathNamespace('orm', 'http://doctrine-project.org/schemas/orm/doctrine-mapping');

    //var_dump($xml->asXML());

    $columns = array();

    foreach($xml->xpath('//orm:entity/orm:*[@name or @field]') as $node) {

        /* @var $node \SimpleXMLElement */
        $attributes = $node->attributes();

        foreach (array('name', 'field') as $type) {
            if (isset($attributes[$type])) {
                $columns[(string) $attributes[$type]] = array();
            }
        }
    }

    $code = $builder->getCode();

    $methods = array();

    preg_match_all('%^    /\*\*(.*?)\*/.*?^    (?:protected \$[a-zA-Z0-9_]+;|\})\r?\n\r?\n%sm', $code, $matchesA, PREG_PATTERN_ORDER);

    for ($i = 0; $i < count($matchesA[0]); $i++) {

        $fragment = $matchesA[0][$i];

        if (preg_match('/protected \$([a-zA-Z0-9_]+);/', $fragment, $matchesB)) {

            $columns[$matchesB[1]]['property'] = $fragment;

        } elseif (preg_match('/public function (set|add|remove)[^By][a-zA-Z0-9]+\(\$([a-z_0-9]+)\)/', $fragment, $matchesB)) {

            $column = $matchesB[2];

            if (in_array($matchesB[1], array('add', 'remove'))) {
                $column = \Propel\Util\Inflector::pluralize($column);
            }

            $columns[$column][$matchesB[1]] = $fragment;

        } elseif (preg_match('/public function (get)[^By][a-zA-Z0-9]+\(\).*?return \$this->(.*?);/s', $fragment, $matchesB)) {

            $columns[$matchesB[2]][$matchesB[1]] = $fragment;

        } elseif (preg_match('/public function (.*?)\(/', $fragment, $matchesB)) {

            $methods[$matchesB[1]] = $fragment;
        }
    }

    ob_start();

    if (true) foreach ($columns as $column => $fragments) {
        if (array_key_exists($type = 'property', $fragments)) {
            echo $fragments[$type];
        }
    }

    if (array_key_exists($name = '__construct', $methods)) {
        echo $methods[$name];
    }

    if (true) foreach ($columns as $column => $fragments) {
      //foreach (array('get', 'set', 'add', 'remove') as $type) {
        foreach (array('set', 'get', 'add', 'remove') as $type) {

            if ($type === 'set' && $column === 'id') {
                continue;
            }

            if (array_key_exists($type, $fragments)) {
//print_r(array($column, $type));

                echo $fragments[$type];
            }
        }
    }

    if (false) foreach (array('setByName', 'getByName', 'fromArray', 'toArray', 'loadMetadata') as $name) {
        if (array_key_exists($name, $methods)) {
            echo $methods[$name];
        }
    }

    $code = preg_replace('/^\{(.*?)^\}/sm', '{' . PHP_EOL . rtrim(ob_get_clean()) . PHP_EOL . '}', $code);

    // removed namespace declarations
    $code = preg_replace('/\\\\Base;.*?use Propel\\\\ActiveEntity;/s', ';', $code);
    $code = str_replace(' extends ActiveEntity', '', $code);
    
    $path = $outputDirectory . DIRECTORY_SEPARATOR . $builder->getOutputName();
    file_put_contents($path, $code);
}
