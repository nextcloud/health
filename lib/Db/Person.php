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

	// general
    protected $name;
    protected $insertTime;
    protected $lastupdateTime;
    protected $userId;
    protected $age;
    protected $size;
    protected $sex;
	protected $personalMission;

    // modules
    protected $enabledModuleWeight;
    protected $enabledModuleBreaks;
    protected $enabledModuleFeeling;
    protected $enabledModuleMedicine;
    protected $enabledModuleActivities;
    protected $enabledModuleMeasurement;
    protected $enabledModuleNutrition;
    protected $enabledModuleSleep;

    // module weight
    protected $weightMeasurementName;
    protected $weightUnit;
    protected $weightTarget;
    protected $weightTargetInitialWeight;
    protected $weightTargetStartDate;
    protected $weightTargetBounty;

    // module feeling
	protected $feelingColumnMood;
	protected $feelingColumnSadness;
	protected $feelingColumnSymptoms;
	protected $feelingColumnAttacks;
	protected $feelingColumnMedication;
	protected $feelingColumnPain;
	protected $feelingSpecialSymptomName;
	protected $feelingSpecialAttackName;
	protected $feelingDefaultMedication;

    public function __construct() {
    	// general
        $this->addType('id','integer');
        $this->addType('age','integer');
        $this->addType('size','integer');

		// modules
		$this->addType('enabledModuleWeight', 'boolean');
		$this->addType('enabledModuleBreaks', 'boolean');
		$this->addType('enabledModuleFeeling', 'boolean');
		$this->addType('enabledModuleMedicine', 'boolean');
		$this->addType('enabledModuleActivities', 'boolean');
		$this->addType('enabledModuleMeasurement', 'boolean');
		$this->addType('enabledModuleNutrition', 'boolean');
		$this->addType('enabledModuleSleep', 'boolean');

        // module weight
        $this->addType('weightTarget', 'float');
        $this->addType('weightTargetInitialWeight', 'float');

        // module feeling
		$this->addType('feelingColumnMood', 'boolean');
		$this->addType('feelingColumnSadness', 'boolean');
		$this->addType('feelingColumnSymptoms', 'boolean');
		$this->addType('feelingColumnAttacks', 'boolean');
		$this->addType('feelingColumnMedication', 'boolean');
		$this->addType('feelingColumnPain', 'boolean');
    }

    public function jsonSerialize() {
        return [
        	// general
            'id' => $this->id,
            'insertTime' => $this->insertTime,
            'lastupdateTime' => $this->lastupdateTime,
            'name' => $this->name,
            'userId' => $this->userId,
            'age' => $this->age,
            'size' => $this->size,
            'sex' => $this->sex,
			'personalMission' => $this->personalMission,

			// modules
            'enabledModuleWeight' => $this->enabledModuleWeight,
            'enabledModuleBreaks' => $this->enabledModuleBreaks,
            'enabledModuleFeeling' => $this->enabledModuleFeeling,
            'enabledModuleMedicine' => $this->enabledModuleMedicine,
            'enabledModuleActivities' => $this->enabledModuleActivities,
			'enabledModuleMeasurement' => $this->enabledModuleMeasurement,
			'enabledModuleSleep' => $this->enabledModuleSleep,
			'enabledModuleNutrition' => $this->enabledModuleNutrition,

			// module weight
            'weightMeasurementName' => $this->weightMeasurementName,
            'weightUnit' => $this->weightUnit,
            'weightTarget' => $this->weightTarget,
            'weightTargetInitialWeight' => $this->weightTargetInitialWeight,
            'weightTargetStartDate' => $this->weightTargetStartDate,
            'weightTargetBounty' => $this->weightTargetBounty,

			// module feeling
			'feelingColumnMood' => $this->feelingColumnMood,
			'feelingColumnSadness' => $this->feelingColumnSadness,
			'feelingColumnSymptoms' => $this->feelingColumnSymptoms,
			'feelingColumnAttacks' => $this->feelingColumnAttacks,
			'feelingColumnMedication' => $this->feelingColumnMedication,
			'feelingColumnPain' => $this->feelingColumnPain,
			'feelingSpecialSymptomName' => $this->feelingSpecialSymptomName,
			'feelingSpecialAttackName' => $this->feelingSpecialAttackName,
			'feelingDefaultMedication' => $this->feelingDefaultMedication,
        ];
    }
}
