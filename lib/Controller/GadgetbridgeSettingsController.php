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

namespace OCA\Health\Controller;

use OCA\Health\Services\GadgetbridgeImportService;
use OCA\Health\Services\GadgetbridgeSettingsService;
use OCP\AppFramework\Http;
use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;

class GadgetbridgeSettingsController extends Controller {

	protected $service;
	protected $importService;

	public function __construct($appName, IRequest $request, GadgetbridgeSettingsService $gadgetbridgeSettingsService, GadgetbridgeImportService $gadgetbridgeImportService) {
		parent::__construct($appName, $request);
		$this->service = $gadgetbridgeSettingsService;
		$this->importService = $gadgetbridgeImportService;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param int $personId
	 * @return DataResponse
	 */
	public function find(int $personId): DataResponse
	{
		try {
			return new DataResponse($this->service->findOrNew($personId));
		} catch (\Exception $e) {
			return new DataResponse([], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $personId
	 * @param bool $backgroundImport
	 * @return DataResponse
	 */
	public function setBackgroundImport(int $personId, bool $backgroundImport = false): DataResponse
	{
		try {
			return new DataResponse($this->service->setBackgroundImport($personId, $backgroundImport));
		} catch (\Exception $e) {
			return new DataResponse([], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $personId
	 * @param string $sqliteSourcePath
	 * @return DataResponse
	 */
	public function setSourcePath(int $personId, string $sqliteSourcePath = ''): DataResponse
	{
		try {
			return new DataResponse($this->service->setSourcePath($personId, $sqliteSourcePath));
		} catch (\Exception $e) {
			return new DataResponse([], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $personId
	 * @return DataResponse
	 */
	public function triggerImport(int $personId): DataResponse
	{
		return new DataResponse($this->importService->importFromSqlite($personId));
	}
}
