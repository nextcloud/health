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

use OCA\Health\Services\GadgetbridgeSettingsService;
use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;

class GadgetbridgeSettingsController extends Controller {

	protected $service;

	public function __construct($appName, IRequest $request, GadgetbridgeSettingsService $gadgetbridgeSettingsService) {
		parent::__construct($appName, $request);
		$this->service = $gadgetbridgeSettingsService;
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
        return new DataResponse($this->service->find($personId));
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $personId
	 * @param string $sqliteSourcePath
	 * @param bool $backgroundImport
	 * @return DataResponse
	 */
	public function createOrUpdate(int $personId, string $sqliteSourcePath = '', bool $backgroundImport = false): DataResponse
	{
		return new DataResponse($this->service->createOrUpdate($personId, $sqliteSourcePath, $backgroundImport));
	}
}