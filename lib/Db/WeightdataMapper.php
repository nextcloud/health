<?php
namespace OCA\Health\Db;

use OCP\IDbConnection;
use OCP\AppFramework\Db\QBMapper;

class WeightdataMapper extends QBMapper {

    public function __construct(IDbConnection $db) {
        parent::__construct($db, 'health_weightdata', Weightdata::class);
    }

    public function find(int $id) {
        $qb = $this->db->getQueryBuilder();

                    $qb->select('*')
                             ->from($this->getTableName())
                             ->where(
                                     $qb->expr()->eq('id', $qb->createNamedParameter($id))
                             );

        return $this->findEntity($qb);
    }

    public function findAll(int $personId) {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
           ->from($this->getTableName())
           ->where(
            $qb->expr()->eq('person_id', $qb->createNamedParameter($personId))
           );

        return $this->findEntities($qb);
    }

}