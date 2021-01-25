<?php
declare(strict_types=1);
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

namespace OCA\Health\Services;

use OCA\Health\Controller\FeelingdataController;
use OCA\Health\Controller\MeasurementdataController;
use OCA\Health\Controller\SleepdataController;
use OCA\Health\Controller\WeightdataController;
use OCA\Health\Db\CesContextMapper;
use OCA\Health\Db\CesEntityMapper;
use OCA\Health\Db\Context;
use OCA\Health\Db\Item;
use OCA\Health\Db\Measurementdata;
use OCP\AppFramework\Db\Entity;

class CesService {

	protected $userId;
	protected $cesContextMapper;
	protected $cesEntityMapper;

	protected $measurementController;
	protected $sleepController;
	protected $feelingController;
	protected $weightController;

	private $magicKeys;

	public function __construct($userId, CesContextMapper $cesContextMapper, CesEntityMapper $cesEntityMapper, SleepdataController $sleepdataController, FeelingdataController $feelingdataController, WeightdataController $weightdataController, MeasurementdataController $measurementdataController) {
		$this->userId = $userId;
		$this->cesContextMapper = $cesContextMapper;
		$this->cesEntityMapper = $cesEntityMapper;
		$this->magicKeys = [
			'alwaysCreate'
		];
		$this->measurementController = $measurementdataController;
		$this->sleepController = $sleepdataController;
		$this->weightController = $weightdataController;
		$this->feelingController = $feelingdataController;
	}

	/**
	 *
	 * magic keys
	 * 'noCreate' as boolean in *Data -> do not create new context or item if no one was found
	 * 'alwaysCreate' as boolean in entityData -> do not look for items, always create new items
	 * 'id' as integer is always the id from the db
	 *
	 * @param $contextFilter
	 * @param $contextData
	 * @param $entityFilter
	 * @param $entityData
	 * @return array|Entity[]
	 */
	public function handleRequest($contextFilter, $contextData, $entityFilter, $entityData): array
	{
		// if contextFilter exists -> find all contexts
		$contexts = $this->cesContextMapper->find($contextFilter);
		// if none was found OR magic key 'alwaysCreate' is set, create new
		if(count($contexts) === 0 || (isset($contextData['alwaysCreate']) && $contextData['alwaysCreate'])) {
			$contexts[] = $this->createContext($contextFilter, $contextData);
		}

		// if magic key is set
		$remove = false;
		if (isset($entityFilter['remove']) && $entityFilter['remove']) {
			$remove = true;
			unset($entityFilter['remove']);
		}

		// if entityFilter exists -> find all entities
		$entities = ($entityFilter !== null) ? $this->cesEntityMapper->find($contexts, $entityFilter): [];

		// if $remove -> remove!
		if($remove) {
			foreach ($entities as $entity) {
				$this->cesEntityMapper->delete($entity);
			}
			return [];
		}

		// finished if no data is to set
		if($entityData === null) {
			return $entities;
		}

		// if no entities were found or no filter data are set, create new for all contexts
		if(count($entities) === 0 || $entityFilter === null) {
			foreach ($contexts as $context) {
				$entities[] = $this->createEntity($entityData, $context);
			}
		// else: update found entities
		} else {
			$results = [];
			foreach ($entities as $entity) {
				$eD = \GuzzleHttp\json_decode($entity->getData(), true);
				foreach ($entityData as $key => $value) {
					$eD[$key] = $value;
				}
				$entity->setData(\GuzzleHttp\json_encode($eD));
				$results[] = $this->cesEntityMapper->update($entity);
			}
			$entities = $results;
		}

		return $entities;
	}

	private function createContext($contextFilter, $contextData) {
		$context = new Context();

		// context definition is set by filterData and contextData
		$data = (is_array($contextFilter)) ? $contextFilter : [];
		$data = (is_array($contextData)) ? array_merge($contextFilter, $contextData) : $data;

		// remove magic keys
		foreach ($this->magicKeys as $magicKey) {
			if(isset($data[$magicKey])) {
				unset($data[$magicKey]);
			}
		}

		// set the context description
		/** @noinspection PhpUndefinedMethodInspection */
		$context->setDescription(\GuzzleHttp\json_encode($data));

		return $this->cesContextMapper->insert($context);
	}

	/** @noinspection PhpUndefinedMethodInspection */
	private function createEntity(Array $data, $context) {
		$entity = new Item();
		$timestamp = date('Y-m-d H:i:s');
		$entity->setData(\GuzzleHttp\json_encode($data));
		$entity->setContext($context->getId());
		$entity->setOwnership(\GuzzleHttp\json_encode(['owner' => $this->userId]));
		// $entity->setDatetime($timestamp);
		$entity->setInsertTime($timestamp);
		$entity->setLastupdateTime($timestamp);
		return $this->cesEntityMapper->insert($entity);
	}

	public function getContexts($filter = null): array
	{
		return $this->cesContextMapper->find($filter);
	}

	public function transformDatasets() {
		$contextFilter = [
			'type' => 'datasets'
			];
		$contexts = $this->cesContextMapper->find($contextFilter);
		foreach ($contexts as $context) {
			$contextData = \GuzzleHttp\json_decode($context->getDescription());
			$entities = $this->cesEntityMapper->find($context);
			foreach ($entities as $e) {
				$eData = \GuzzleHttp\json_decode($e->getData());
				if($contextData->module === 'measurement') {
					$personId = $eData->getPersonId() ? $eData->getPersonId() : null;
					$temperature = $eData->getTemperature() ? $eData->getTemperature() : null;
					$datetime = $eData->getDatetime() ? $eData->getDatetime() : null;
					$heartRate = $eData->getHeartRate() ? $eData->getHeartRate() : null;
					$bloodPressureS = $eData->getBloodPres() ? $eData->getBloodPres() : null;
					$temperature = $eData->getTemperature() ? $eData->getTemperature() : null;
					$temperature = $eData->getTemperature() ? $eData->getTemperature() : null;
					$temperature = $eData->getTemperature() ? $eData->getTemperature() : null;
					$temperature = $eData->getTemperature() ? $eData->getTemperature() : null;

				} elseif($contextData->module === 'sleep') {
				} elseif($contextData->module === 'feeling') {
				} else {
				}
			}
		}
	}

}
