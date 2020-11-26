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

use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCA\Health\Services\FeelingdataService;

class FeelingdataController extends Controller {

	protected $userId;
	protected $feelingdataService;

	public function __construct($appName, IRequest $request, FeelingdataService $fS, $userId) {
		parent::__construct($appName, $request);
		$this->feelingdataService = $fS;
		$this->userId = $userId;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param int $personId
	 */
	public function index(int $personId) {
        return new DataResponse($this->feelingdataService->getAllByPersonId($personId));
	}

	/**
	 * @NoAdminRequired
	 *
     * @param int $personId
     * @param float $weight
     * @param float $measurement
     * @param float $bodyfat
     * @param string $date
	 */
	public function create($personId, $weight, $measurement, $bodyfat, $date) {
		return new DataResponse($this->feelingdataService->create($personId, $weight, $measurement, $bodyfat, $date));
	}

	/**
	 * @NoAdminRequired
	 *
     * @param int $id
	 */
	public function destroy($id) {
		return new DataResponse($this->feelingdataService->delete($id));
	}

	/**
	 * @NoAdminRequired
	 *
     * @param int $id
     * @param float $weight
     * @param float $measurement
     * @param float $bodyfat
     * @param string $date
	 */
	public function update($id, $date, $weight, $measurement, $bodyfat) {
		return new DataResponse($this->feelingdataService->update($id, $date, $weight, $measurement, $bodyfat));
	}
}
