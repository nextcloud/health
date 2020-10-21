<?php
namespace OCA\Health\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
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

    public function findLast(int $personId) {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('person_id', $qb->createNamedParameter($personId))
        );
        $qb->orderBy('date', 'desc');
        $qb->setMaxResults(1);
		try {
			return $this->findEntity($qb);
		} catch (DoesNotExistException $e) {
			return null;
		} catch (MultipleObjectsReturnedException $e) {
			return null;
		}
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
