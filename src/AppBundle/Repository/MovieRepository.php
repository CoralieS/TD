<?php

namespace AppBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Class MovieRepository
 * @package AppBundle\Repository
 */
class MovieRepository extends EntityRepository
{
    public function bestMovie()
    {
        $query = $this->getEntityManager()->createQuery(
            'SELECT title, poster as m,
            FROM AppBundle:Movie 
            COUNT m.id
            GROUP BY m.id
            ORDER BY DESC
            '
        );

        return $query->setMaxResults(1)->getOneOrNullResult();

    }
}