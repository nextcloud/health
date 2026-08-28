<?php

declare(strict_types=1);

namespace OCA\Health\Controller;

use OCA\Health\AppInfo\Application;
use OCA\Health\Exception\InvalidEntryException;
use OCA\Health\ResponseDefinitions;
use OCA\Health\Service\DailyValueService;
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
 * @psalm-import-type HealthDailyValue from ResponseDefinitions
 * @psalm-import-type HealthDailyValuesPage from ResponseDefinitions
 * @psalm-suppress UnusedClass Controller routes are discovered by Nextcloud.
 */
class DailyValuesController extends OCSController {
	public function __construct(
		IRequest $request,
		private DailyValueService $dailyValueService,
		private IUserSession $userSession,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	/**
	 * List daily values for one local day
	 *
	 * Returns only values owned by the authenticated user, including a descriptive BMI for weight when a profile height is available.
	 *
	 * @param string $date Local calendar date in YYYY-MM-DD format.
	 * @return DataResponse<Http::STATUS_OK, HealthDailyValuesPage, array{}>
	 * @throws OCSBadRequestException The date is invalid.
	 *
	 * 200: Daily values returned
	 * 400: Invalid local date
	 */
	#[NoAdminRequired] #[OpenAPI] #[ApiRoute(verb: 'GET', url: '/api/v2/daily-values')]
	public function index(mixed $date): DataResponse {
		try {
			return new DataResponse(['values' => $this->dailyValueService->list($this->userId(), $date)]);
		} catch (InvalidEntryException $exception) {
			throw new OCSBadRequestException($exception->getMessage(), $exception);
		}
	}

	/**
	 * Create or update a daily value
	 *
	 * Stores one owner-scoped daily metric in its canonical unit.
	 *
	 * @param string $metricKey Stable daily value metric identifier.
	 * @param string $date Local calendar date in YYYY-MM-DD format.
	 * @param int|float $numericValue Numeric value in the supplied unit.
	 * @param string|null $unit Supported display unit for the metric, or null for unitless scale metrics.
	 * @return DataResponse<Http::STATUS_OK, HealthDailyValue, array{}>
	 * @throws OCSBadRequestException A metric, value, unit, or date is invalid.
	 *
	 * 200: Daily value saved
	 * 400: Invalid daily value data
	 */
	#[NoAdminRequired] #[OpenAPI] #[ApiRoute(verb: 'PUT', url: '/api/v2/daily-values/{metricKey}/{date}')]
	public function upsert(string $metricKey, string $date, mixed $numericValue, mixed $unit): DataResponse {
		try {
			return new DataResponse($this->dailyValueService->upsert($this->userId(), $metricKey, $date, $numericValue, $unit));
		} catch (InvalidEntryException $exception) {
			throw new OCSBadRequestException($exception->getMessage(), $exception);
		}
	}

	/**
	 * Delete a daily value
	 *
	 * Permanently removes one daily value owned by the authenticated user.
	 *
	 * @param string $metricKey Stable daily value metric identifier.
	 * @param string $date Local calendar date in YYYY-MM-DD format.
	 * @return DataResponse<Http::STATUS_OK, null, array{}>
	 * @throws OCSBadRequestException A metric or date is invalid, or the value does not exist.
	 *
	 * 200: Daily value deleted
	 * 400: Invalid or missing daily value
	 */
	#[NoAdminRequired] #[OpenAPI] #[ApiRoute(verb: 'DELETE', url: '/api/v2/daily-values/{metricKey}/{date}')]
	public function destroy(string $metricKey, string $date): DataResponse {
		try {
			$this->dailyValueService->delete($this->userId(), $metricKey, $date);
			return new DataResponse(null);
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
