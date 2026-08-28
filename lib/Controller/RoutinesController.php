<?php

declare(strict_types=1);

namespace OCA\Health\Controller;

use OCA\Health\AppInfo\Application;
use OCA\Health\Exception\InvalidEntryException;
use OCA\Health\ResponseDefinitions;
use OCA\Health\Service\RoutineService;
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
 * @psalm-import-type HealthRoutineResult from ResponseDefinitions
 * @psalm-suppress UnusedClass Controller routes are discovered by Nextcloud.
 */
class RoutinesController extends OCSController {
	public function __construct(
		IRequest $request,
		private RoutineService $routineService,
		private IUserSession $userSession,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	/**
	 * Submit a Check-in or Check-out
	 *
	 * Creates only enabled metrics assigned to the requested routine for the authenticated user. The operation is atomic.
	 *
	 * @param 'check-in'|'check-out' $context Routine selected by the route.
	 * @param string $date Local calendar date in YYYY-MM-DD format for daily values.
	 * @param string $recordedAt RFC3339 timestamp with an explicit timezone or offset.
	 * @param list<array{metricKey: string, numericValue: int|float|null, optionValue: string|null, note?: string}> $journalMetrics Journal metrics to record.
	 * @param list<array{metricKey: string, numericValue: int|float|null, values: array{systolic: int|float, diastolic: int|float}|null, unit: string, note?: string}> $measurements Measurements to record.
	 * @param list<array{metricKey: string, numericValue: int|float, unit: string}> $dailyValues Daily values to record.
	 * @return DataResponse<Http::STATUS_OK, HealthRoutineResult, array{}>
	 * @throws OCSBadRequestException The routine or any supplied metric is invalid or disabled for it.
	 *
	 * 200: Routine saved
	 * 400: Invalid routine data
	 */
	#[NoAdminRequired] #[OpenAPI] #[ApiRoute(verb: 'POST', url: '/api/v2/routines/{context}', requirements: ['context' => 'check-in|check-out'])]
	public function submit(string $context, mixed $date, mixed $recordedAt, mixed $journalMetrics = [], mixed $measurements = [], mixed $dailyValues = []): DataResponse {
		try {
			$internalContext = $context === 'check-in' ? 'checkin' : ($context === 'check-out' ? 'checkout' : '');
			return new DataResponse($this->routineService->submit($this->userId(), $internalContext, $date, $recordedAt, $journalMetrics, $measurements, $dailyValues));
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
