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

class GadgetbridgeImportService {

	protected $userId;
	protected $formatHelperService;
	protected $permissionService;
	protected $settingsMapper;

	public function __construct($userId,
								FormatHelperService $formatHelperService,
								PermissionService $permissionService,
	GadgetbridgeSettingsMapper $gadgetbridgeSettingsMapper
	) {
		$this->userId = $userId;
		$this->formatHelperService = $formatHelperService;
		$this->permissionService = $permissionService;
		$this->settingsMapper = $gadgetbridgeSettingsMapper;
	}

	public function importFromSqlite()
	{
		// test if sqlite is enabled

		// check if all prerequisites are set

		// open connection

		// private function - import devices

		// more to import?

		// private function - import watch data

		// close connection

		// update timestamps in settings

		// send feedback to FE
	}
}
