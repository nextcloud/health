<?php
namespace OCA\Health\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\IDbConnection;
use OCP\AppFramework\Db\QBMapper;

class PersonMapper extends QBMapper {

    public function __construct(IDbConnection $db) {
        parent::__construct($db, 'health_persons', Person::class);
    }

    public function find(int $id, string $userId = "") {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*');
		$qb->from($this->getTableName());
		$qb->where( $qb->expr()->eq('id', $qb->createNamedParameter($id)) );

        if($userId !== "") {
            $qb->andWhere( $qb->expr()->eq('user_id', $qb->createNamedParameter($userId) ));
        }

		try {
			return $this->findEntity($qb);
		} catch (DoesNotExistException $e) {
        	return null;
		} catch (MultipleObjectsReturnedException $e) {
			return null;
		}
	}

    public function findAll(string $userId) {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
           ->from($this->getTableName())
           ->where(
            $qb->expr()->eq('user_id', $qb->createNamedParameter($userId))
           );

        return $this->findEntities($qb);
    }

}
