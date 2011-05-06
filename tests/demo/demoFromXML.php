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

    if (false) {
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
    
    $builder = new BaseActiveRecord($metadata);

    $builder->setMappingDriver(BaseActiveRecord::MAPPING_ANNOTATION);
    $builder->setAnnotationPrefix('orm');

    $generator->addBuilder($builder);
    if (false)$generator->addBuilder(new ActiveRecord($metadata));
}

echo "Generating classes for xml schemas...\n";
$generator->writeClasses($outputDirectory);
echo "Class generation complete\n";

// somme cleanup

if (!false) foreach ($generator->getBuilders() as $i => $builder) {

if ($i < 0) {
    continue;
}
    
    // fetching position of columns

    $xml_filename = $dir . '/' . str_replace(array('/Base', '/', '.php'), array('', '.', $driverImpl->getFileExtension()), $builder->getOutputName());
    $xml = file_get_contents($xml_filename);

    $xml = new SimpleXMLElement(file_get_contents($xml_filename));
    $xml->registerXPathNamespace('orm', 'http://doctrine-project.org/schemas/orm/doctrine-mapping');

    //var_dump($xml->asXML());

    $columns = array();

    foreach($xml->xpath('//orm:entity/orm:*[@name]') as $node) {
        /* @var $node \SimpleXMLElement */
        $attributes = $node->attributes();
        $columns[(string) $attributes['name']] = array();
    }

    //$positions = array_flip($columns);
    //print_r($columns);
    //print_r($positions);
    
    //exit();

    $code = $builder->getCode();

    $fragments = array();

    preg_match_all('%^    /\*\*(.*?)\*/.*?^    (?:protected \$[a-zA-Z0-9_]+;|\})\r?\n\r?\n%sm', $code, $matchesA, PREG_PATTERN_ORDER);

    for ($i = 0; $i < count($matchesA[0]); $i++) {

        $fragment = $matchesA[0][$i];

        if (preg_match('/protected \$([a-zA-Z0-9_]+);/', $fragment, $matchesB)) {

            $columns[$matchesB[1]]['property'] = $fragment;

        } else {
            if (preg_match('/public function set[^By][a-zA-Z0-9]+\(\$([a-z_0-9]+)\)/', $fragment, $matchesB)) {

                $columns[$matchesB[1]]['setter'] = $fragment;
            }
            
            if (preg_match('/public function get[^By][a-zA-Z0-9]+\(\).*?return \$this->(.*?);/s', $fragment, $matchesB)) {

                $columns[$matchesB[1]]['getter'] = $fragment;
            }
        }
    }

    if (true) foreach ($columns as $column => $fragments) {
        if (array_key_exists($type = 'property', $fragments)) {
            echo $fragments[$type];
        }
    }

    if (!true) foreach ($columns as $column => $fragments) {
        foreach (array('setter', 'getter') as $type) {
            if (array_key_exists($type, $fragments)) {
                echo $fragments[$type];
            }
        }
    }

    exit();

    
    
    
    
    
    
    
    
    
    
    
    
    
    // unneeded stuff deletion
    preg_match_all('%^    /\*\*(.*?)\*/.*?\{.*?^    \}\r?\n\r?\n%sm', $code, $matches, PREG_PATTERN_ORDER);

    for ($i = 0; $i < count($matches[0]); $i++) {

        $fragment = $matches[0][$i];

        if (strpos($fragment, 'public function ') !== false) {
            foreach (array(
                'fromArray',
                'toArray',
                'setByName',
                'getByName',
            ) as $function) {
                if (strpos($fragment, $function) !== false) {
                    $code = str_replace($fragment, '', $code);
                    //var_dump($fragment);
                }
            }
        }
    }

    // removed namespace declarations
    $code = str_replace('\Base;' . PHP_EOL . PHP_EOL . 'use Propel\ActiveEntity;', ';', $code);
    $code = str_replace(' extends ActiveEntity', '', $code);

    // removed the last empty line
    $code = str_replace('    }' . PHP_EOL . PHP_EOL . '}', '    }' . PHP_EOL . '}', $code);
    //var_dump($code);
    
    $path = $outputDirectory . DIRECTORY_SEPARATOR . $builder->getOutputName();
    file_put_contents($path, $code);

    //break;
}