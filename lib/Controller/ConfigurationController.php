<?php

declare(strict_types=1);

namespace OCA\Health\Controller;

use OCA\Health\AppInfo\Application;
use OCA\Health\Exception\InvalidEntryException;
use OCA\Health\ResponseDefinitions;
use OCA\Health\Service\ConfigurationService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use OCP\IUserSession;

/**
 * @psalm-import-type HealthConfiguration from ResponseDefinitions
 * @psalm-suppress UnusedClass Controller routes are discovered by Nextcloud.
 */
class ConfigurationController extends OCSController {
	public function __construct(
		IRequest $request,
		private ConfigurationService $configurationService,
		private IUserSession $userSession,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	/**
	 * Get the authenticated user's Health configuration
	 *
	 * Returns profile, metric, and integration preferences scoped to the authenticated user.
	 *
	 * @return DataResponse<Http::STATUS_OK, HealthConfiguration, array{}>
	 *
	 * 200: Configuration returned
	 */
	#[NoAdminRequired]
	#[OpenAPI]
	#[ApiRoute(verb: 'GET', url: '/api/v2/configuration')]
	public function show(): DataResponse {
		return new DataResponse($this->configurationService->get($this->userId()));
	}

	/**
	 * Update the authenticated user's Health configuration
	 *
	 * Updates supplied profile and metric fields while retaining omitted fields.
	 *
	 * @param array{height?: int|float|null, heightUnit?: 'cm'|'in', dateOfBirth?: string|null, growthReferenceSex?: 'female'|'male'|null}|null $profile Profile fields; null values clear optional fields.
	 * @param array<string, array{enabled?: bool, checkInEnabled?: bool, checkOutEnabled?: bool, displayUnit?: string|null}>|null $metrics Metric preferences keyed by stable metric identifier.
	 * @param bool|null $searchDailyNotes Whether this user's Daily Notes are included in Nextcloud Unified Search.
	 * @return DataResponse<Http::STATUS_OK, HealthConfiguration, array{}>
	 * @throws OCSBadRequestException The configuration fields are invalid.
	 *
	 * 200: Configuration updated
	 * 400: Invalid configuration data
	 */
	#[NoAdminRequired]
	#[OpenAPI]
	#[ApiRoute(verb: 'PUT', url: '/api/v2/configuration')]
	public function update(mixed $profile = null, mixed $metrics = null, mixed $searchDailyNotes = null): DataResponse {
		try {
			return new DataResponse($this->configurationService->update($this->userId(), $profile, $metrics, $searchDailyNotes));
		} catch (InvalidEntryException $exception) {
			throw new OCSBadRequestException($exception->getMessage(), $exception);
		}
	}

	private function userId(): string {
		$user = $this->userSession->getUser();
		if ($user === null) {
			throw new OCSBadRequestException('Authentication required.');
		} return $user->getUID();
	}
}
