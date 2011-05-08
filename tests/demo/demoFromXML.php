#!/usr/bin/php
<?php

/*
$project = 'AcmePizza';

$dir     = __DIR__ . '/Model/xml/' . $project;
echo shell_exec(' php /var/www/' . $project . '/app/console doctrine:mapping:convert --force xml ' . $dir);
*/

$dir = '/var/www/AcmePizza/src/Acme/PizzaBundle/Resources/config/doctrine';
//$dir = '/var/www/RdfIntranet2/src/Rdf/AgendaBundle/Resources/config/doctrine';

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

/**
 * some customization
 */

if (true) foreach ($generator->getBuilders() as $i => $builder) {

    if (false) if ($i < 1) {
        continue;
    }

    /**
     * fetching position of columns
     */

    $xml_filename = $dir . '/' . str_replace(array('/Base', '/', '.php'), array('', '.', $driverImpl->getFileExtension()), $builder->getOutputName());
    $xml = file_get_contents($xml_filename);

    $xml = new SimpleXMLElement(file_get_contents($xml_filename));
    $xml->registerXPathNamespace('orm', 'http://doctrine-project.org/schemas/orm/doctrine-mapping');

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

    /**
     * feeding columns with fragments of code (ordered by type fe: set/get/add/remove/property)
     */

    $code = $builder->getCode();

    $methods = array();

    preg_match_all('%^    /\*\*(.*?)\*/.*?^    (?:protected \$[a-zA-Z0-9_]+;|\})\r?\n\r?\n%sm', $code, $matchesA, PREG_PATTERN_ORDER);

    for ($i = 0; $i < count($matchesA[0]); $i++) {

        $fragment = $matchesA[0][$i];

        if (preg_match('/protected \$([a-zA-Z0-9_]+);/', $fragment, $matchesB)) {

            $columns[$matchesB[1]]['property'] = $fragment;

        } elseif (preg_match('/public function (set|add|remove)[A-Z][a-zA-Z0-9]+\(\$([a-zA-Z0-9_]+)\)/', $fragment, $matchesB)) {

            $column = $matchesB[2];

            if (in_array($matchesB[1], array('add', 'remove'))) {
                $column = \Propel\Util\Inflector::pluralize($column);
            }

            $columns[$column][$matchesB[1]] = $fragment;

        } elseif (preg_match('/public function (get)[A-Z][a-zA-Z0-9]+\(\).*?return \$this->(.*?);/s', $fragment, $matchesB)) {

            $columns[$matchesB[2]][$matchesB[1]] = $fragment;

        } elseif (preg_match('/public function (.*?)\(/', $fragment, $matchesB)) {

            $methods[$matchesB[1]] = $fragment;
        }
    }

    /**
     * adding type hint casting
     */
    if (true) {

        if (preg_match('/namespace (.*?)\\\\Base;/', $code, $matches)) {
            $namespace = '\\' . $matches[1];
        }
        //var_dump($namespace);

        foreach ($columns as $column => $fragments) {

            foreach ($fragments as $type => $fragment) {
                if (preg_match('/@param (.*?) (\$[a-z][a-zA-Z0-9_]+)$/m', $fragment, $matches)) {

                    $typeHint = $matches[1];

                    // make typeHint relative to current namespace
                    if (strpos($typeHint, $namespace) === 0) {
                        $typeHint = substr($typeHint, strlen($namespace) + 1);
                    }

                    if (false === in_array($typeHint, array('string', 'integer', 'float'))) {
                        $columns[$column][$type] = preg_replace('/\((\$[a-z][a-zA-Z0-9_]+)\)$/m', sprintf('(%s $1)', $typeHint), $fragment);
                    }
                }
            }
        }
    }

    /**
     * rendering everything
     */

    $code_cleaned = '';
    
    if (true) foreach ($columns as $column => $fragments) {
        if (array_key_exists($type = 'property', $fragments)) {
            $code_cleaned.= $fragments[$type];
        }
    }

    if (array_key_exists($name = '__construct', $methods)) {
        $code_cleaned.= $methods[$name];
    }

    if (true) foreach ($columns as $column => $fragments) {
      //foreach (array('get', 'set', 'add', 'remove') as $type) {
        foreach (array('set', 'add', 'remove', 'get') as $type) {

            if ($type === 'set' && $column === 'id') {
                continue;
            }

            if (array_key_exists($type, $fragments)) {
                $code_cleaned.= $fragments[$type];
            } else if ($type === 'set' || $type === 'get') {
                throw new \Exception(sprintf('no "%s" for column "%s"', $type, $column));
            }
        }
    }

    if (true) foreach (array(
        /*
        'setByName',
        'getByName',
        'fromArray',
        'toArray',
        */
        'loadMetadata',
    ) as $name) {
        if (array_key_exists($name, $methods)) {
            $code_cleaned.= $methods[$name];
        }
    }

    /**
     * last clean up (fe: removed namespace declarations)
     */

    $code = preg_replace('/^\{(.*?)^\}/sm', '{' . PHP_EOL . rtrim($code_cleaned) . PHP_EOL . '}', $code);

    $code = preg_replace('/\\\\Base;.*?use Propel\\\\ActiveEntity;/s', ';', $code);
    $code = str_replace(' extends ActiveEntity', '', $code);
    
    /**
     * overwriting generated code by custom ones
     */

    $path = $outputDirectory . DIRECTORY_SEPARATOR . $builder->getOutputName();
    file_put_contents($path, $code);
}
