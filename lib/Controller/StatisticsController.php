<?php

declare(strict_types=1);

namespace OCA\Health\Controller;

use OCA\Health\AppInfo\Application;
use OCA\Health\Exception\InvalidEntryException;
use OCA\Health\ResponseDefinitions;
use OCA\Health\Service\StatisticsService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use OCP\IUserSession;

/**
 * @psalm-import-type HealthStatisticsResponse from ResponseDefinitions
 * @psalm-suppress UnusedClass Controller routes are discovered by Nextcloud.
 */
class StatisticsController extends OCSController {
	public function __construct(
		IRequest $request,
		private StatisticsService $statisticsService,
		private IUserSession $userSession,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	/**
	 * List day-level descriptive statistics for selected metrics
	 *
	 * Period bounds are resolved in the authenticated user's Nextcloud timezone.
	 * The response contains canonical values only, with every local date in the
	 * half-open `[from, to)` range represented in each selected metric series.
	 *
	 * @param 'this_week'|'last_week'|'last_7_days'|'last_30_days'|'this_month'|'last_month'|'this_year'|'last_year' $period Period preset. Defaults to `last_30_days`.
	 * @param string|null $metrics Optional comma-separated stable metric keys. Omit to use currently enabled journal metrics; provide an empty string for no metrics.
	 * @return DataResponse<Http::STATUS_OK, HealthStatisticsResponse, array{}>
	 * @throws OCSBadRequestException The period or metric selection is invalid.
	 *
	 * 200: Statistics returned
	 * 400: Invalid period or metric selection
	 */
	#[NoAdminRequired]
	#[OpenAPI]
	#[ApiRoute(verb: 'GET', url: '/api/v2/statistics')]
	public function index(mixed $period = 'last_30_days', mixed $metrics = null): DataResponse {
		try {
			return new DataResponse($this->statisticsService->get($this->userId(), $period, $metrics));
		} catch (InvalidEntryException $exception) {
			throw new OCSBadRequestException($exception->getMessage(), $exception);
		}
	}

	private function userId(): string {
		$user = $this->userSession->getUser();
		if ($user === null) {
			throw new OCSException('Authentication required.', Http::STATUS_UNAUTHORIZED);
		}

		return $user->getUID();
	}
}
