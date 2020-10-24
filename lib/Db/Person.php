<?php
/**
 * @copyright Copyright (c) 2020 Florian Steffens <flost-dev@mailbox.org>
 *
 * @author Florian Steffens <flost-dev@mailbox.org>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

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
    protected $enabledModuleMedicine;
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
        $this->addType('enabledModuleMedicine', 'boolean');
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
            'enabledModuleMedicine' => $this->enabledModuleMedicine,
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
