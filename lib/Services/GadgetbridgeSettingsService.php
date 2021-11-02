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

use OCA\Health\Db\GadgetbridgeSettings;
use OCA\Health\Db\GadgetbridgeSettingsMapper;
use OCA\Health\Exceptions\NoPermissionException;
use OCA\Health\Exceptions\ParameterInputException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Http;
use OCP\DB\Exception;

class GadgetbridgeSettingsService {

	protected $userId;
	protected $formatHelperService;
	protected $permissionService;
	protected $mapper;

	public function __construct($userId,
								FormatHelperService $formatHelperService,
								PermissionService $permissionService,
	GadgetbridgeSettingsMapper $gadgetbridgeSettingsMapper
	) {
		$this->userId = $userId;
		$this->formatHelperService = $formatHelperService;
		$this->permissionService = $permissionService;
		$this->mapper = $gadgetbridgeSettingsMapper;
	}

	/**
	 * @throws \Exception
	 */
	public function findOrNew(int $personId)
	{
		// try to find the settings item from db, else make new (empty) settings return
		try {
			return $this->find($personId);
		} catch (NoPermissionException | ParameterInputException | MultipleObjectsReturnedException | Exception $e) {
			throw new \Exception($e->getMessage());
		} catch (DoesNotExistException $e) {
			return new GadgetbridgeSettings();
		}
	}


	/**
	 * @param int $personId
	 * @return Entity
	 * @throws DoesNotExistException
	 * @throws MultipleObjectsReturnedException
	 * @throws NoPermissionException
	 * @throws Exception
	 */
	public function find(int $personId): Entity
	{
		if(!$this->permissionService->canAccessGadgetBridgeSettings($this->userId, $personId)) {
			throw new NoPermissionException('No access to the given person id ('.$personId.').');
		}

		return $this->mapper->findBy($this->userId, $personId);
	}

	/** @noinspection PhpUndefinedMethodInspection */
	/**
	 * @param int $personId
	 * @param bool $backgroundImport
	 * @param string|null $userId
	 * @return GadgetbridgeSettings
	 * @throws \Exception
	 */
	public function setBackgroundImport(int $personId, bool $backgroundImport, string $userId = null): GadgetbridgeSettings {
		if($userId === null) {
			$userId = $this->userId;
		}

		try {
			$settings = $this->mapper->findBy($userId, $personId);
			$settings->setBackgroundImport($backgroundImport);
			try {
				return $this->mapper->update($settings);
			} catch (Exception $e) {
				throw new \Exception($e->getMessage());
			}
		} catch (DoesNotExistException $e) {
			$settings = new GadgetbridgeSettings();
			$settings->setPersonId($personId);
			$settings->setUserId($userId);
			$settings->setBackgroundImport($backgroundImport);
			try {
				return $this->mapper->insert($settings);
			} catch (Exception $e) {
				throw new \Exception($e->getMessage());
			}
		} catch (MultipleObjectsReturnedException | Exception $e) {
			throw new \Exception($e->getMessage());
		}
	}

	/** @noinspection PhpUndefinedMethodInspection */
	/**
	 * @param int $personId
	 * @param string $sqliteSourcePath
	 * @param string|null $userId
	 * @return GadgetbridgeSettings
	 * @throws \Exception
	 */
	public function setSourcePath(int $personId, string $sqliteSourcePath, string $userId = null): GadgetbridgeSettings {
		if($userId === null) {
			$userId = $this->userId;
		}

		try {
			$settings = $this->mapper->findBy($userId, $personId);
			$settings->setSqliteSourcePath($sqliteSourcePath);
			try {
				return $this->mapper->update($settings);
			} catch (Exception $e) {
				throw new \Exception($e->getMessage());
			}
		} catch (DoesNotExistException $e) {
			$settings = new GadgetbridgeSettings();
			$settings->setPersonId($personId);
			$settings->setUserId($userId);
			$settings->setSqliteSourcePath($sqliteSourcePath);
			try {
				return $this->mapper->insert($settings);
			} catch (Exception $e) {
				throw new \Exception($e->getMessage());
			}
		} catch (MultipleObjectsReturnedException | Exception $e) {
			throw new \Exception($e->getMessage());
		}
	}
}
