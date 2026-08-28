<?php

declare(strict_types=1);

namespace OCA\Health\Controller;

use OCA\Health\AppInfo\Application;
use OCA\Health\Exception\EntryNotFoundException;
use OCA\Health\Exception\InvalidEntryException;
use OCA\Health\ResponseDefinitions;
use OCA\Health\Service\MeasurementService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use OCP\IUserSession;

/**
 * @psalm-import-type HealthMeasurement from ResponseDefinitions
 * @psalm-import-type HealthMeasurementsPage from ResponseDefinitions
 * @psalm-suppress UnusedClass Controller routes are discovered by Nextcloud.
 */
class MeasurementsController extends OCSController {
	public function __construct(
		IRequest $request,
		private MeasurementService $measurementService,
		private IUserSession $userSession,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	/**
	 * List measurements
	 *
	 * Returns only measurements owned by the authenticated user. Supplied timestamps delimit a half-open `[from, to)` range.
	 *
	 * @param string|null $from Optional inclusive RFC3339 lower bound.
	 * @param string|null $to Optional exclusive RFC3339 upper bound.
	 * @return DataResponse<Http::STATUS_OK, HealthMeasurementsPage, array{}>
	 * @throws OCSBadRequestException A timestamp is invalid.
	 *
	 * 200: Measurements returned
	 * 400: Invalid timestamp filter
	 */
	#[NoAdminRequired] #[OpenAPI] #[ApiRoute(verb: 'GET', url: '/api/v2/measurements')]
	public function index(mixed $from = null, mixed $to = null): DataResponse {
		try {
			return new DataResponse(['measurements' => $this->measurementService->list($this->userId(), $from, $to)]);
		} catch (InvalidEntryException $exception) {
			throw new OCSBadRequestException($exception->getMessage(), $exception);
		}
	}

	/**
	 * Create a measurement
	 *
	 * Records one owner-scoped measurement. Blood pressure uses `values`; other metrics use `numericValue`.
	 *
	 * @param string $metricKey Stable measurement metric identifier.
	 * @param int|float|null $numericValue Numeric value for a non-blood-pressure metric.
	 * @param array{systolic: int|float, diastolic: int|float}|null $values Blood pressure values; required only for `blood_pressure`.
	 * @param string|null $unit Supported unit for the metric.
	 * @param string|null $recordedAt RFC3339 timestamp with an explicit timezone or offset.
	 * @param string|null $note Optional plain-text note of at most 1000 characters.
	 * @param 'manual'|'checkin'|'checkout' $context Recording context. Defaults to `manual`.
	 * @param 'web'|'api'|'mobile'|'notification' $source Recording source. Defaults to `api`.
	 * @return DataResponse<Http::STATUS_CREATED, HealthMeasurement, array{}>
	 * @throws OCSBadRequestException The measurement fields are invalid.
	 *
	 * 201: Measurement created
	 * 400: Invalid measurement data
	 */
	#[NoAdminRequired] #[OpenAPI] #[ApiRoute(verb: 'POST', url: '/api/v2/measurements')]
	public function create(mixed $metricKey, mixed $numericValue = null, mixed $values = null, mixed $unit = null, mixed $recordedAt = null, mixed $note = null, mixed $context = 'manual', mixed $source = 'api'): DataResponse {
		try {
			return new DataResponse($this->measurementService->create($this->userId(), $metricKey, $numericValue, $values, $unit, $recordedAt, $note, $context, $source), 201);
		} catch (InvalidEntryException $exception) {
			throw new OCSBadRequestException($exception->getMessage(), $exception);
		}
	}

	/**
	 * Update a measurement
	 *
	 * Replaces mutable values on a measurement owned by the authenticated user; its metric and source cannot be changed.
	 *
	 * @param int $id Measurement ID.
	 * @param int|float|null $numericValue Numeric value for a non-blood-pressure metric.
	 * @param array{systolic: int|float, diastolic: int|float}|null $values Blood pressure values; required only for `blood_pressure`.
	 * @param string|null $unit Supported unit for the metric.
	 * @param string|null $recordedAt RFC3339 timestamp with an explicit timezone or offset.
	 * @param string|null $note Optional plain-text note of at most 1000 characters.
	 * @param 'manual'|'checkin'|'checkout' $context Recording context.
	 * @return DataResponse<Http::STATUS_OK, HealthMeasurement, array{}>
	 * @throws OCSBadRequestException The measurement fields are invalid.
	 * @throws OCSNotFoundException The measurement does not exist or is not owned by the authenticated user.
	 *
	 * 200: Measurement updated
	 * 400: Invalid measurement data
	 * 404: Measurement not found
	 */
	#[NoAdminRequired] #[OpenAPI] #[ApiRoute(verb: 'PUT', url: '/api/v2/measurements/{id}', requirements: ['id' => '\\d+'])]
	public function update(int $id, mixed $numericValue = null, mixed $values = null, mixed $unit = null, mixed $recordedAt = null, mixed $note = null, mixed $context = 'manual'): DataResponse {
		try {
			return new DataResponse($this->measurementService->update($this->userId(), $id, $numericValue, $values, $unit, $recordedAt, $note, $context));
		} catch (InvalidEntryException $exception) {
			throw new OCSBadRequestException($exception->getMessage(), $exception);
		} catch (EntryNotFoundException $exception) {
			throw new OCSNotFoundException($exception->getMessage(), $exception);
		}
	}

	/**
	 * Delete a measurement
	 *
	 * Permanently removes one measurement owned by the authenticated user.
	 *
	 * @param int $id Measurement ID.
	 * @return DataResponse<Http::STATUS_OK, null, array{}>
	 * @throws OCSNotFoundException The measurement does not exist or is not owned by the authenticated user.
	 *
	 * 200: Measurement deleted
	 * 404: Measurement not found
	 */
	#[NoAdminRequired] #[OpenAPI] #[ApiRoute(verb: 'DELETE', url: '/api/v2/measurements/{id}', requirements: ['id' => '\\d+'])]
	public function destroy(int $id): DataResponse {
		try {
			$this->measurementService->delete($this->userId(), $id);
			return new DataResponse(null);
		} catch (EntryNotFoundException $exception) {
			throw new OCSNotFoundException($exception->getMessage(), $exception);
		}
	}
	private function userId(): string {
		$user = $this->userSession->getUser();
		if ($user === null) {
			throw new OCSBadRequestException('Authentication required.');
		} return $user->getUID();
	}
}
