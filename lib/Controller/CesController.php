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

use OCA\Health\Services\CesService;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use OCP\AppFramework\Controller;

class CesController extends Controller {

	protected $cesService;

	public function __construct($appName, IRequest $request, CesService $cesService) {
		parent::__construct($appName, $request);
		$this->cesService = $cesService;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param $data
	 * @return DataResponse
	 */
	public function index(array $data) {
		$contextFilter = (isset($data['contextFilter']) && is_array($data['contextFilter']) ) ? $data['contextFilter'] : null;
		$contextData = (isset($data['contextData']) && is_array($data['contextData'])) ? $data['contextData'] : null;
		$entityFilter = (isset($data['entityFilter']) && is_array($data['entityFilter'])) ? $data['entityFilter'] : null;
		$entityData = (isset($data['entityData']) && is_array($data['entityData'])) ? $data['entityData'] : null;

		$result = $this->cesService->handleRequest($contextFilter, $contextData, $entityFilter, $entityData);

		return new DataResponse($result);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param array|null $filter
	 * @return DataResponse
	 */
	public function contexts($filter = null) {
		return new DataResponse($this->cesService->getContexts($filter));
	}
}
