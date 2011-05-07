<?php

namespace Propel\Builder\ORM;

use Doctrine\ORM\Mapping\ClassMetadataInfo;

class AnnotationBuilder
{
    protected $metadata;

    public function __construct(ClassMetadataInfo $metadata)
    {
        $this->metadata = $metadata;
    }
    
    public static function getFieldMappingColumnAnnotation(array $fieldMapping)
    {
        $column = array(); // could be renamed to options to be consistent

        // it should be also passed in foreach loop, but we have to know all default options
        if (isset($fieldMapping['columnName']) && ($fieldMapping['columnName'] != $fieldMapping['fieldName'])) {
            $column[] = 'name="' . $fieldMapping['columnName'] . '"';
        }

        foreach (array(
            'type',
            'length',
            'precision',
            'scale',
            'nullable',
            'columnDefinition',
            'unique',
        ) as $key) {

            if (isset($fieldMapping[$key])) {
                $column[] = sprintf('%s="%s"', $key, $fieldMapping[$key]);
            }
        }

        return sprintf('Column(%s)', implode(', ', $column));
    }
    
    
    public function getSequenceGeneratorAnnotation()
    {
        $sequenceGenerator = array(); // could be renamed to options to be consistent

        // we could add a loop here too
        
        if (isset($this->metadata->sequenceGeneratorDefinition['sequenceName'])) {
            $sequenceGenerator[] = 'sequenceName="' . $this->metadata->sequenceGeneratorDefinition['sequenceName'] . '"';
        }

        if (isset($this->metadata->sequenceGeneratorDefinition['allocationSize'])) {
            $sequenceGenerator[] = 'allocationSize="' . $this->metadata->sequenceGeneratorDefinition['allocationSize'] . '"';
        }

        if (isset($this->metadata->sequenceGeneratorDefinition['initialValue'])) {
            $sequenceGenerator[] = 'initialValue="' . $this->metadata->sequenceGeneratorDefinition['initialValue'] . '"';
        }

        return 'SequenceGenerator(' . implode(', ', $sequenceGenerator) . ')';
    }

    public function getDiscriminatorColumnAnnotation()
    {
        $discrColumn = $this->metadata->discriminatorColumn;
        $discrColumnDetails = array();
        
        // we could add a loop here too

        if (isset($discrColumn['name'])) {
            $discrColumnDetails[] = 'name="' . $discrColumn['name'] . '"';
        }
        if (isset($discrColumn['type'])) {
            $discrColumnDetails[] = 'type="' . $discrColumn['type'] . '"';
        }
        if (isset($discrColumn['length']) && '' !== $discrColumn['length']) {
            $discrColumnDetails[] = 'length=' . $discrColumn['length'];
        }

        return 'DiscriminatorColumn(' . implode(', ', $discrColumnDetails) . ')';
    }

    public function getDiscriminatorMapAnnotation()
    {
        $inheritanceClassMap = array();

        foreach ($this->metadata->discriminatorMap as $type => $class) {
            $inheritanceClassMap[] .= '"' . $type . '" = "' . $class . '"';
        }

        return 'DiscriminatorMap({' . implode(', ', $inheritanceClassMap) . '})';
    }

    public function getEntityAnnotation()
    {
        if ($this->metadata->isMappedSuperclass) {
            $annotation = 'MappedSupperClass';
        } else {
            $annotation = 'Entity';
        }

        if ($this->metadata->customRepositoryClassName) {
            $annotation .= '(repositoryClass="' . $this->metadata->customRepositoryClassName . '")';
        }
        
        return $annotation;
    }
    
    public function getAssociationMappingAnnotation(array $associationMapping)
    {
        $typeOptions = array(); // could be renamed to options to be consistent

        foreach (array(
            'targetEntity',
            'inversedBy',
            'mappedBy',
        ) as $key) {

            if (isset($associationMapping[$key])) {
                $typeOptions[] = sprintf('%s="%s"', $key, $associationMapping[$key]);
            }
        }

        if ($associationMapping['cascade']) {

            $cascades = array();

            foreach (array(
                'persist',
                'remove',
                'detach',
                'merge',
                'refresh',
            ) as $key) {
                
                if ($associationMapping[sprintf('isCascade%s', ucfirst($key))]) {
                    $cascades[] = sprintf('"%s"', $key);
                }
            }

            $typeOptions[] = sprintf('cascade={%s}', implode(', ', $cascades));
        }

        if (isset($associationMapping['orphanRemoval']) && $associationMapping['orphanRemoval']) {
            $typeOptions[] = 'orphanRemoval=' . ($associationMapping['orphanRemoval'] ? 'true' : 'false');
        }

        $type = null;

        switch ($associationMapping['type']) {
            case ClassMetadataInfo::ONE_TO_ONE:
                $type = 'OneToOne';
                break;
            case ClassMetadataInfo::MANY_TO_ONE:
                $type = 'ManyToOne';
                break;
            case ClassMetadataInfo::ONE_TO_MANY:
                $type = 'OneToMany';
                break;
            case ClassMetadataInfo::MANY_TO_MANY:
                $type = 'ManyToMany';
                break;
        }

        return $type . '(' . implode(', ', $typeOptions) . ')';
   }
   
   public function getJoinColumnAnnotation($joinColumn)
   {
        $joinColumnAnnot = array(); // could be renamed to options to be consistent

        foreach (array(
            'name',
            'referencedColumnName',
            'unique',
            'nullable',
            'onDelete',
            'onUpdate',
            'columnDefinition',
        ) as $key) {

            if (isset($joinColumn[$key])) {
                $joinColumnAnnot[] = sprintf('%s="%s"', $key, $joinColumn[$key]);
            }
        }
        
        return 'JoinColumn(' . implode(', ', $joinColumnAnnot) . ')'; // sprintf
   }

   public function getJoinTableAnnotation($joinTable)
   {
        $joinTableAnnot = array();  // could be renamed to options to be consistent
        
        // we could add a loop here too
        
        $joinTableAnnot[] = 'name="' . $joinTable['name'] . '"';

        if (isset($joinTable['schema'])) {
            $joinTableAnnot[] = 'schema="' . $joinTable['schema'] . '"';
        }

        return 'JoinTable(' . implode(', ', $joinTableAnnot) . ',';
   }
}