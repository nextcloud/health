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
    protected $enabledModuleBreaks;
    protected $enabledModuleFeeling;
    protected $enabledModuleMedicin;
    protected $enabledModuleActivities;
    protected $weightMeasurementName;
    protected $weightUnit;
    protected $weightTarget;
    protected $weightTargetInitialWeight;
    protected $weightTargetStartDate;
    protected $weightTargetBounty;
    protected $personalMission;

    public function __construct() {
        $this->addType('id','integer');
        $this->addType('age','integer');
        $this->addType('size','integer');
        $this->addType('weightTarget', 'float');
        $this->addType('weightTargetInitialWeight', 'float');
        $this->addType('enabledModuleWeight', 'boolean');
        $this->addType('enabledModuleBreaks', 'boolean');
        $this->addType('enabledModuleFeeling', 'boolean');
        $this->addType('enabledModuleMedicin', 'boolean');
        $this->addType('enabledModuleActivities', 'boolean');
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
            'enabledModuleBreaks' => $this->enabledModuleBreaks,
            'enabledModuleFeeling' => $this->enabledModuleFeeling,
            'enabledModuleMedicin' => $this->enabledModuleMedicin,
            'enabledModuleActivities' => $this->enabledModuleActivities,
            'weightMeasurementName' => $this->weightMeasurementName,
            'weightUnit' => $this->weightUnit,
            'weightTarget' => $this->weightTarget,
            'weightTargetInitialWeight' => $this->weightTargetInitialWeight,
            'weightTargetStartDate' => $this->weightTargetStartDate,
            'weightTargetBounty' => $this->weightTargetBounty,
            'personalMission' => $this->personalMission,
        ];
    }
}