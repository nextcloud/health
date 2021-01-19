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

use OCA\Health\Services\Weight\DatasetService;
use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;

class WeightController extends Controller {

	protected $userId;
	protected $datasetService;

	public function __construct($appName, IRequest $request, DatasetService $wS, $userId) {
		parent::__construct($appName, $request);
		$this->datasetService = $wS;
		$this->userId = $userId;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param int $personId
	 * @return DataResponse
	 */
	public function datasetFind(int $personId): DataResponse
	{
        return new DataResponse($this->datasetService->FindByPersonId($personId));
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param int $personId
	 * @param string $date
	 * @param float|null $weight
	 * @param float|null $measurement
	 * @param float|null $bodyfat
	 * @return DataResponse
	 */
	public function datasetCreate(int $personId, string $date, float $weight = null, float $measurement = null, float $bodyfat = null): DataResponse
	{
		return new DataResponse($this->datasetService->create($personId, $weight, $measurement, $bodyfat, $date));
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param int $id
	 * @return DataResponse
	 */
	public function datasetDelete(int $id): DataResponse
	{
		return new DataResponse($this->datasetService->delete($id));
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param int $id
	 * @param string $date
	 * @param float|null $weight
	 * @param float|null $measurement
	 * @param float|null $bodyfat
	 * @return DataResponse
	 */
	public function datasetUpdate(int $id, string $date, float $weight = null, float $measurement = null, float $bodyfat = null): DataResponse
	{
		return new DataResponse($this->datasetService->update($id, $date, $weight, $measurement, $bodyfat));
	}
}
