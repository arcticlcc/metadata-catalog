<?php

namespace MetaCat\Service;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Doctrine\ORM\Query\ResultSetMappingBuilder;

class LoadCacheService implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['mc.cache.owners'] = $app->protect(function ($class) use ($app) {
            $em = $app['orm.em'];
            $isClass = strrpos($class, '\\');
            $class =strtolower($isClass ? substr($class, $isClass + 1) : $class);
            $sql = "SELECT (SELECT value FROM jsonb_array_elements(json#>'{contact}') AS c WHERE
                c->'contactId' = (SELECT value FROM
                jsonb_array_elements(json#>'{metadata,resourceInfo,citation,responsibleParty}') AS rol
            WHERE rol@>'{\"role\":\"owner\"}' LIMIT 1)->'contactId')->>'organizationName' as \"owner\"
            FROM $class p
            GROUP BY owner";
            $rsm = new ResultSetMappingBuilder($em);
            $rsm->addScalarResult('owner', 'owner');

            $query = $em->createNativeQuery($sql, $rsm);
            $query->useResultCache(true, null, "mc_{$class}_owner");
            $result = $query->getArrayResult();

            return $result;

        });
    }
}
