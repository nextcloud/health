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
use Exception;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Http;

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

	public function find(int $personId)
	{
		if(!$this->permissionService->canAccessGadgetBridgeSettings($this->userId, $personId)) {
			return HTTP::STATUS_FORBIDDEN;
		}

		if(!$personId) {
			return HTTP::STATUS_BAD_REQUEST;
		}

		try {
			return $this->mapper->findBy($this->userId, $personId);
		} catch (DoesNotExistException $e) {
			return HTTP::STATUS_NOT_FOUND;
		} catch (MultipleObjectsReturnedException $e) {
			return HTTP::STATUS_BAD_REQUEST;
		} catch (\OCP\DB\Exception $e) {
			return HTTP::STATUS_INTERNAL_SERVER_ERROR;
		}
	}

	/** @noinspection PhpUndefinedMethodInspection */
	public function createOrUpdate(int $personId, string $sqliteSourcePath, bool $backgroundImport) {
		$settings = null;
		try {
			$settings = $this->mapper->findBy($this->userId, $personId);
			$settings->setSqliteSourcePath($sqliteSourcePath);
			$settings->setBackgroundImport($backgroundImport);
			try {
				$this->mapper->update($settings);
			} catch (\OCP\DB\Exception $e) {
				return HTTP::STATUS_INTERNAL_SERVER_ERROR;
			}
		} catch (Exception $e) {
			$settings = new GadgetbridgeSettings();
			$settings->setPersonId($personId);
			$settings->setUserId($this->userId);
			$settings->setSqliteSourcePath($sqliteSourcePath);
			$settings->setBackgroundImport($backgroundImport);
			try {
				$this->mapper->insert($settings);
			} catch (\OCP\DB\Exception $e) {
				return HTTP::STATUS_INTERNAL_SERVER_ERROR;
			}
		}
	}
}
