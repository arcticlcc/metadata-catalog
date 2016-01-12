<?php

namespace MetaCat\Service;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Symfony\Component\HttpFoundation\Request;

class PaginationService implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['mc.paginator'] = $app->protect(function (Request $request, $sql, $class) use ($app) {
            $em = $app['orm.em'];
            //get params
            $params = [
              'sort' => $request->query->getAlnum('sort', 'title'),
              'direction' => $request->query->getAlpha('direction', 'ASC'),
              'owner' => $request->query->get('owner', ''),
            ];
            //check owner
            $owners = array_flip(array_column($app['mc.cache.owners']($class), 'owner'));
            $owner = $params['owner'];
            if ($owner && !isset($owners[$owner])) {
                $owner = '';
                $error = 'Invalid owner submitted.';
            }
            //add filter
            if ($owner) {
                $sql .= ' WHERE "owner" = ? ';
            }
            //get count
            $cSql = "SELECT count(*) FROM ($sql) q;";
            $count = $app['db']->fetchColumn($cSql, $owner ? [$owner] : [], 0);

            $rsm = new ResultSetMappingBuilder($em);
            $rsm->addRootEntityFromClassMetadata($class, 'p');
            $rsm->addScalarResult('id', 'id');
            $rsm->addScalarResult('has_html', 'has_html');
            $rsm->addScalarResult('has_xml', 'has_xml');
            $rsm->addScalarResult('title', 'title');
            $rsm->addScalarResult('owner', 'owner');
            //only visible columns may be sorted
            if ($rsm->isScalarResult($params['sort'])) {
                $dir = strtoupper($params['direction']) == 'ASC' ? 'ASC' : 'DESC';
                $sql .= " ORDER BY {$params['sort']} $dir";
            }
            //add limit and offset
            $size = $request->query->getInt('size', 10);
            $page = $request->query->getInt('page', 1);
            $page = min($page, intval(ceil($count / $size)));
            $offset = max(0, ($page - 1) * $size);
            $sql .= " LIMIT $size OFFSET $offset";

            $query = $em->createNativeQuery($sql, $rsm);

            if ($owner) {
                $query->setParameter(1, $owner);
            }

            $results = $query->getArrayResult();
            //pagination
            $pagination = $app['knp_paginator']->paginate(
                [], //using LIMIT/OFFSET, so pass empty result and set items below
                $page,
                $size
            );
            $pagination->setTotalItemCount($count);
            $pagination->setItems($results);
            $pagination->setCustomParameters([
              'owner' => $owner,
              'error' => isset($error) ? $error : false
            ]);

            return $pagination;

        });
    }
}
