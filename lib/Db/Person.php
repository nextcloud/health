<?php
namespace OCA\Health\Db;

use JsonSerializable;

use OCP\AppFramework\Db\Entity;

class Person extends Entity implements JsonSerializable {

    protected $name;
    protected $insertTime;
    protected $lastupdateTime;
    protected $userId;
    protected $age;
    protected $size;
    protected $sex;
    protected $enabledModuleWeight;
    protected $weightMeasurementName;
    protected $weightUnit;
    protected $weightTarget;
    protected $weightTargetInitialWeight;
    protected $weightTargetStartDate;
    protected $personalMission;
    protected $weightdata;

    public function __construct() {
        $this->addType('id','integer');
        $this->addType('age','integer');
        $this->addType('size','integer');
        $this->addType('enabledModuleWeight', 'boolean');
        $this->weightdata = [];
    }

    public function jsonSerialize() {
        return [
            'id' => $this->id,
            'insertTime' => $this->insertTime,
            'lastupdateTime' => $this->lastupdateTime,
            'name' => $this->name,
            'userId' => $this->userId,
            'age' => $this->age,
            'size' => $this->size,
            'sex' => $this->sex,
            'enabledModuleWeight' => $this->enabledModuleWeight,
            'weightMeasurementName' => $this->weightMeasurementName,
            'weightUnit' => $this->weightUnit,
            'weightTarget' => $this->weightTarget,
            'weightTargetInitialWeight' => $this->weightTargetInitialWeight,
            'weightTargetStartDate' => $this->weightTargetStartDate,
            'personalMission' => $this->personalMission,
            'weightdata' => $this->weightdata,
        ];
    }
}