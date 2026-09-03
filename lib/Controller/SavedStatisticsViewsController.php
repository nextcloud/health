<?php

declare(strict_types=1);

namespace OCA\Health\Controller;

use OCA\Health\AppInfo\Application;
use OCA\Health\Exception\InvalidEntryException;
use OCA\Health\Exception\SavedStatisticsViewNotFoundException;
use OCA\Health\ResponseDefinitions;
use OCA\Health\Service\SavedStatisticsViewService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use OCP\IUserSession;

/**
 * @psalm-import-type HealthSavedStatisticsView from ResponseDefinitions
 * @psalm-import-type HealthSavedStatisticsViewsPage from ResponseDefinitions
 * @psalm-suppress UnusedClass Controller routes are discovered by Nextcloud.
 */
class SavedStatisticsViewsController extends OCSController {
	public function __construct(
		IRequest $request,
		private SavedStatisticsViewService $savedStatisticsViewService,
		private IUserSession $userSession,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	/**
	 * List the authenticated user's saved Statistics views
	 *
	 * @return DataResponse<Http::STATUS_OK, HealthSavedStatisticsViewsPage, array{}>
	 *
	 * 200: Saved Statistics views returned
	 */
	#[NoAdminRequired]
	#[OpenAPI]
	#[ApiRoute(verb: 'GET', url: '/api/v2/statistics/views')]
	public function index(): DataResponse {
		return new DataResponse(['views' => $this->savedStatisticsViewService->list($this->userId())]);
	}

	/**
	 * Get one saved Statistics view
	 *
	 * @param int $id Saved Statistics view ID.
	 * @return DataResponse<Http::STATUS_OK, HealthSavedStatisticsView, array{}>
	 * @throws OCSNotFoundException The view is missing or belongs to another user.
	 *
	 * 200: Saved Statistics view returned
	 * 404: Saved Statistics view not found
	 */
	#[NoAdminRequired]
	#[OpenAPI]
	#[ApiRoute(verb: 'GET', url: '/api/v2/statistics/views/{id}', requirements: ['id' => '\\d+'])]
	public function show(int $id): DataResponse {
		try {
			return new DataResponse($this->savedStatisticsViewService->get($this->userId(), $id));
		} catch (SavedStatisticsViewNotFoundException $exception) {
			throw new OCSNotFoundException($exception->getMessage(), $exception);
		}
	}

	/**
	 * Create a saved Statistics view from an explicit configuration
	 *
	 * @param string $title User-visible view title, up to 120 characters.
	 * @param string $icon Native emoji selected through the official Nextcloud emoji picker.
	 * @param list<string> $metricKeys Selected stable metric identifiers.
	 * @param 'this_week'|'last_week'|'last_7_days'|'last_30_days'|'this_month'|'last_month'|'this_year'|'last_year' $period Statistics period preset.
	 * @return DataResponse<Http::STATUS_CREATED, HealthSavedStatisticsView, array{}>
	 * @throws OCSBadRequestException The title, icon, metric selection, or period is invalid.
	 *
	 * 201: Saved Statistics view created
	 * 400: Invalid saved Statistics view configuration
	 */
	#[NoAdminRequired]
	#[OpenAPI]
	#[ApiRoute(verb: 'POST', url: '/api/v2/statistics/views')]
	public function create(mixed $title, mixed $icon, mixed $metricKeys, mixed $period): DataResponse {
		try {
			return new DataResponse($this->savedStatisticsViewService->create($this->userId(), $title, $icon, $metricKeys, $period), Http::STATUS_CREATED);
		} catch (InvalidEntryException $exception) {
			throw new OCSBadRequestException($exception->getMessage(), $exception);
		}
	}

	/**
	 * Replace the title, icon, metric selection, and period of one saved Statistics view
	 *
	 * @param int $id Saved Statistics view ID.
	 * @param string $title User-visible view title, up to 120 characters.
	 * @param string $icon Native emoji selected through the official Nextcloud emoji picker.
	 * @param list<string> $metricKeys Selected stable metric identifiers.
	 * @param 'this_week'|'last_week'|'last_7_days'|'last_30_days'|'this_month'|'last_month'|'this_year'|'last_year' $period Statistics period preset.
	 * @return DataResponse<Http::STATUS_OK, HealthSavedStatisticsView, array{}>
	 * @throws OCSBadRequestException The title, icon, metric selection, or period is invalid.
	 * @throws OCSNotFoundException The view is missing or belongs to another user.
	 *
	 * 200: Saved Statistics view updated
	 * 400: Invalid saved Statistics view configuration
	 * 404: Saved Statistics view not found
	 */
	#[NoAdminRequired]
	#[OpenAPI]
	#[ApiRoute(verb: 'PUT', url: '/api/v2/statistics/views/{id}', requirements: ['id' => '\\d+'])]
	public function update(int $id, mixed $title, mixed $icon, mixed $metricKeys, mixed $period): DataResponse {
		try {
			return new DataResponse($this->savedStatisticsViewService->update($this->userId(), $id, $title, $icon, $metricKeys, $period));
		} catch (InvalidEntryException $exception) {
			throw new OCSBadRequestException($exception->getMessage(), $exception);
		} catch (SavedStatisticsViewNotFoundException $exception) {
			throw new OCSNotFoundException($exception->getMessage(), $exception);
		}
	}

	/**
	 * Delete one saved Statistics view
	 *
	 * @param int $id Saved Statistics view ID.
	 * @return DataResponse<Http::STATUS_OK, array{}, array{}>
	 * @throws OCSNotFoundException The view is missing or belongs to another user.
	 *
	 * 200: Saved Statistics view deleted
	 * 404: Saved Statistics view not found
	 */
	#[NoAdminRequired]
	#[OpenAPI]
	#[ApiRoute(verb: 'DELETE', url: '/api/v2/statistics/views/{id}', requirements: ['id' => '\\d+'])]
	public function destroy(int $id): DataResponse {
		try {
			$this->savedStatisticsViewService->delete($this->userId(), $id);
			return new DataResponse([]);
		} catch (SavedStatisticsViewNotFoundException $exception) {
			throw new OCSNotFoundException($exception->getMessage(), $exception);
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
