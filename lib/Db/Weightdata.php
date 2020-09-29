<?php
namespace OCA\Health\Db;

use JsonSerializable;

use OCP\AppFramework\Db\Entity;

class Weightdata extends Entity implements JsonSerializable {

    protected $insertTime;
    protected $lastupdateTime;
    protected $personId;
    protected $bodyfat;
    protected $measurement;
    protected $weight;
    protected $date;

    public function __construct() {
        $this->addType('id','integer');
        $this->addType('personId','integer');
        $this->addType('bodyfat','float');
        $this->addType('measurement','float');
        $this->addType('weight','float');
    }

    public function jsonSerialize() {
        $date = new \DateTime($this->date);
        return [
            'id' => $this->id,
            'insertTime' => $this->insertTime,
            'lastupdateTime' => $this->lastupdateTime,
            'personId' => $this->personId,
            'bodyfat' => $this->bodyfat,
            'measurement' => $this->measurement,
            'weight' => $this->weight,
            'date' => $date->format('Y-m-d'),
        ];
    }
}