<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-doctrine-querybuilder for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-doctrine-querybuilder/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-doctrine-querybuilder/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools\Doctrine\QueryBuilder\Hydrator\Strategy;

use Doctrine\Laminas\Hydrator\Strategy\AbstractCollectionStrategy;
use Laminas\ApiTools\Hal\Link\Link;
use Laminas\Filter\FilterChain;
use Laminas\ServiceManager\ServiceManager;

/**
 * A field-specific hydrator for collections.
 *
 * This version is for use with laminas-hyrator versions 1 and 2, and will be
 * aliased to CollectionLink in those versions.
 *
 * @returns Link
 */
class CollectionLinkHydratorV2 extends AbstractCollectionStrategy
{
    protected $serviceManager;

    public function setServiceManager(ServiceManager $serviceManager)
    {
        $this->serviceManager = $serviceManager;

        return $this;
    }

    public function getServiceManager()
    {
        return $this->serviceManager;
    }

    public function extract($value, ?object $object = null)
    {
        $config = $this->getServiceManager()->get('config');
        if (! method_exists($value, 'getTypeClass')
            || ! isset($config['api-tools-hal']['metadata_map'][$value->getTypeClass()->name])
        ) {
            return;
        }

        $config = $config['api-tools-hal']['metadata_map'][$value->getTypeClass()->name];
        $mapping = $value->getMapping();

        $filter = new FilterChain();
        $filter->attachByName('WordCamelCaseToUnderscore')
            ->attachByName('StringToLower');

        $link = new Link($filter($mapping['fieldName']));
        $link->setRoute($config['route_name']);
        $link->setRouteParams(['id' => null]);

        if (isset($config['api-tools-doctrine-querybuilder-options']['filter_key'])) {
            $filterKey = $config['api-tools-doctrine-querybuilder-options']['filter_key'];
        } else {
            $filterKey = 'filter';
        }

        $filterValue = [
            'field' => $mapping['mappedBy'] ? : $mapping['inversedBy'],
            'type' => isset($mapping['joinTable']) ? 'ismemberof' : 'eq',
            'value' => $value->getOwner()->getId(),
        ];

        $link->setRouteOptions([
            'query' => [
                $filterKey => [
                    $filterValue,
                ],
            ],
        ]);

        return $link;
    }

    public function hydrate($value)
    {
        // Hydration is not supported for collections.
        // A call to PATCH will use hydration to extract then hydrate
        // an entity. In this process a collection will be included
        // so no error is thrown here.
    }
}
