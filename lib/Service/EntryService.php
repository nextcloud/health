<?php

declare(strict_types=1);

namespace OCA\Health\Service;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use JsonException;
use OCA\Health\Db\Entry;
use OCA\Health\Db\EntryMapper;
use OCA\Health\Exception\EntryNotFoundException;
use OCA\Health\Exception\InvalidEntryException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;

class EntryService {
	private const ALLOWED_CONTEXTS = ['manual', 'checkin', 'checkout', 'reminder'];
	private const ALLOWED_SOURCES = ['web', 'api', 'mobile', 'notification'];
	private const DEFAULT_LIMIT = 50;
	private const MAXIMUM_LIMIT = 200;
	private const MAXIMUM_NOTE_LENGTH = 1000;

	private DateTimeZone $utc;

	/** @psalm-suppress PossiblyUnusedMethod Instantiated by Nextcloud dependency injection. */
	public function __construct(
		private EntryMapper $entryMapper,
		private MetricService $metricService,
	) {
		$this->utc = new DateTimeZone('UTC');
	}

	/**
	 * @return array{
	 *   id: int,
	 *   metricKey: string,
	 *   numericValue: int|float|null,
	 *   optionValue: string|null,
	 *   context: string,
	 *   source: 'web'|'api'|'mobile'|'notification',
	 *   recordedAt: string,
	 *   createdAt: string,
	 *   updatedAt: string,
	 *   note: string|null,
	 * }
	 */
	public function create(
		string $userId,
		mixed $metricKey,
		mixed $numericValue,
		mixed $optionValue,
		mixed $context,
		mixed $recordedAt,
		mixed $note,
		mixed $source = 'api',
	): array {
		$this->requireUserId($userId);
		$validatedMetricKey = $this->metricService->validateJournalMetricKey($metricKey);
		$validatedValue = $this->metricService->validateValue(
			$validatedMetricKey,
			$numericValue,
			$optionValue,
		);

		$validatedContext = $this->validateContext($context);
		$validatedSource = $this->validateSource($source);
		$validatedRecordedAt = $this->parseTimestamp($recordedAt, 'recordedAt');
		$validatedNote = $this->validateNote($note);
		$now = new DateTimeImmutable('now', $this->utc);

		$entry = new Entry();
		$entry->setUserId($userId);
		$entry->setMetricKey($validatedMetricKey);
		$entry->setNumericValue($validatedValue['numericValue'] === null ? null : (string)$validatedValue['numericValue']);
		$entry->setOptionValue($validatedValue['optionValue']);
		$entry->setContext($validatedContext);
		$entry->setSource($validatedSource);
		$entry->setRecordedAt($validatedRecordedAt);
		$entry->setCreatedAt($now);
		$entry->setUpdatedAt($now);
		$entry->setNote($validatedNote);

		return $this->formatEntry($this->entryMapper->create($entry));
	}

	/**
	 * @return array{
	 *   id: int,
	 *   metricKey: string,
	 *   numericValue: int|float|null,
	 *   optionValue: string|null,
	 *   context: string,
	 *   source: 'web'|'api'|'mobile'|'notification',
	 *   recordedAt: string,
	 *   createdAt: string,
	 *   updatedAt: string,
	 *   note: string|null,
	 * }
	 * @throws EntryNotFoundException
	 */
	public function update(
		string $userId,
		int $id,
		mixed $numericValue,
		mixed $optionValue,
		mixed $context,
		mixed $recordedAt,
		mixed $note,
	): array {
		$this->requireUserId($userId);
		$entry = $this->findEntryForUser($id, $userId);
		$validatedValue = $this->metricService->validateValue(
			$entry->getMetricKey(),
			$numericValue,
			$optionValue,
		);

		$entry->setNumericValue($validatedValue['numericValue'] === null ? null : (string)$validatedValue['numericValue']);
		$entry->setOptionValue($validatedValue['optionValue']);
		$entry->setContext($this->validateContext($context));
		$entry->setRecordedAt($this->parseTimestamp($recordedAt, 'recordedAt'));
		$entry->setUpdatedAt(new DateTimeImmutable('now', $this->utc));
		$entry->setNote($this->validateNote($note));

		return $this->formatEntry($this->entryMapper->updateForUser($entry));
	}

	/** @throws EntryNotFoundException */
	public function delete(string $userId, int $id): void {
		$this->requireUserId($userId);
		$this->entryMapper->deleteForUser($this->findEntryForUser($id, $userId));
	}

	/**
	 * @return array{
	 *   entries: list<array{
	 *     id: int,
	 *     metricKey: string,
	 *     numericValue: int|float|null,
	 *     optionValue: string|null,
	 *     context: string,
	 *     source: 'web'|'api'|'mobile'|'notification',
	 *     recordedAt: string,
	 *     createdAt: string,
	 *     updatedAt: string,
	 *     note: string|null,
	 *   }>,
	 *   nextCursor: string|null,
	 * }
	 */
	public function list(
		string $userId,
		mixed $metricKey = null,
		mixed $from = null,
		mixed $to = null,
		mixed $limit = self::DEFAULT_LIMIT,
		mixed $cursor = null,
	): array {
		$this->requireUserId($userId);

		$validatedMetricKey = null;
		if ($metricKey !== null) {
			$validatedMetricKey = $this->metricService->validateJournalMetricKey($metricKey);
		}

		$validatedFrom = $from === null ? null : $this->parseTimestamp($from, 'from');
		$validatedTo = $to === null ? null : $this->parseTimestamp($to, 'to');
		if ($validatedFrom !== null && $validatedTo !== null && $validatedFrom >= $validatedTo) {
			throw new InvalidEntryException('from must be earlier than to.');
		}

		$validatedLimit = $this->validateLimit($limit);
		$cursorPosition = $this->decodeCursor($cursor);

		$entries = $this->entryMapper->findPageForUser(
			$userId,
			$validatedMetricKey,
			$validatedFrom,
			$validatedTo,
			$cursorPosition['recordedAt'] ?? null,
			$cursorPosition['id'] ?? null,
			$validatedLimit + 1,
		);

		$hasNextPage = count($entries) > $validatedLimit;
		if ($hasNextPage) {
			array_pop($entries);
		}

		$nextCursor = null;
		if ($hasNextPage && $entries !== []) {
			$lastEntry = $entries[array_key_last($entries)];
			$nextCursor = $this->encodeCursor($lastEntry);
		}

		return [
			'entries' => array_map($this->formatEntry(...), $entries),
			'nextCursor' => $nextCursor,
		];
	}

	private function requireUserId(string $userId): void {
		if ($userId === '') {
			throw new InvalidEntryException('An authenticated user is required.');
		}
	}

	private function validateContext(mixed $context): string {
		if (!is_string($context) || !in_array($context, self::ALLOWED_CONTEXTS, true)) {
			throw new InvalidEntryException('Invalid entry context.');
		}

		return $context;
	}

	private function validateSource(mixed $source): string {
		if (!is_string($source) || !in_array($source, self::ALLOWED_SOURCES, true)) {
			throw new InvalidEntryException('Invalid entry source.');
		}

		return $source;
	}

	/** @throws EntryNotFoundException */
	private function findEntryForUser(int $id, string $userId): Entry {
		try {
			return $this->entryMapper->findForUser($id, $userId);
		} catch (DoesNotExistException|MultipleObjectsReturnedException $exception) {
			throw new EntryNotFoundException('Entry not found.', 0, $exception);
		}
	}

	private function validateNote(mixed $note): ?string {
		if ($note === null) {
			return null;
		}

		if (!is_string($note)) {
			throw new InvalidEntryException('Entry note must be plain text.');
		}

		if (mb_strlen($note) > self::MAXIMUM_NOTE_LENGTH) {
			throw new InvalidEntryException('Entry note must not exceed 1000 characters.');
		}

		return $note;
	}

	private function validateLimit(mixed $limit): int {
		if (is_string($limit) && preg_match('/^[0-9]+$/D', $limit) === 1) {
			$limit = (int)$limit;
		}

		if (!is_int($limit) || $limit < 1 || $limit > self::MAXIMUM_LIMIT) {
			throw new InvalidEntryException('limit must be between 1 and 200.');
		}

		return $limit;
	}

	private function parseTimestamp(mixed $value, string $field): DateTimeImmutable {
		if (!is_string($value)) {
			throw new InvalidEntryException($field . ' must be an RFC3339 timestamp.');
		}

		$pattern = '/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})(?:\.\d+)?(Z|[+-]\d{2}:\d{2})$/D';
		if (preg_match($pattern, $value, $matches) !== 1) {
			throw new InvalidEntryException($field . ' must be an RFC3339 timestamp.');
		}

		$year = (int)$matches[1];
		$month = (int)$matches[2];
		$day = (int)$matches[3];
		$hour = (int)$matches[4];
		$minute = (int)$matches[5];
		$second = (int)$matches[6];
		$offset = $matches[7];

		if (!checkdate($month, $day, $year) || $hour > 23 || $minute > 59 || $second > 59) {
			throw new InvalidEntryException($field . ' must be a valid RFC3339 timestamp.');
		}

		if ($offset !== 'Z') {
			$offsetHour = (int)substr($offset, 1, 2);
			$offsetMinute = (int)substr($offset, 4, 2);
			if ($offsetHour > 14 || $offsetMinute > 59 || ($offsetHour === 14 && $offsetMinute !== 0)) {
				throw new InvalidEntryException($field . ' must contain a valid timezone offset.');
			}
		}

		return (new DateTimeImmutable($value))->setTimezone($this->utc);
	}

	/**
	 * @return array{recordedAt: DateTimeImmutable, id: int}|null
	 */
	private function decodeCursor(mixed $cursor): ?array {
		if ($cursor === null) {
			return null;
		}

		if (!is_string($cursor) || $cursor === '' || preg_match('/^[A-Za-z0-9_-]+$/D', $cursor) !== 1) {
			throw new InvalidEntryException('Invalid pagination cursor.');
		}

		$encoded = strtr($cursor, '-_', '+/');
		$padding = strlen($encoded) % 4;
		if ($padding !== 0) {
			$encoded .= str_repeat('=', 4 - $padding);
		}

		$decoded = base64_decode($encoded, true);
		if ($decoded === false) {
			throw new InvalidEntryException('Invalid pagination cursor.');
		}

		try {
			$data = json_decode($decoded, true, 4, JSON_THROW_ON_ERROR);
		} catch (JsonException) {
			throw new InvalidEntryException('Invalid pagination cursor.');
		}

		if (!is_array($data)
			|| array_keys($data) !== ['version', 'recordedAt', 'id']
			|| $data['version'] !== 1
			|| !is_string($data['recordedAt'])
			|| !is_int($data['id'])
			|| $data['id'] < 1
		) {
			throw new InvalidEntryException('Invalid pagination cursor.');
		}

		return [
			'recordedAt' => $this->parseTimestamp($data['recordedAt'], 'cursor'),
			'id' => $data['id'],
		];
	}

	private function encodeCursor(Entry $entry): string {
		$data = json_encode([
			'version' => 1,
			'recordedAt' => $this->formatTimestamp($entry->getRecordedAt()),
			'id' => $entry->getId(),
		], JSON_THROW_ON_ERROR);

		return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
	}

	/**
	 * @return array{
	 *   id: int,
	 *   metricKey: string,
	 *   numericValue: int|float|null,
	 *   optionValue: string|null,
	 *   context: string,
	 *   source: 'web'|'api'|'mobile'|'notification',
	 *   recordedAt: string,
	 *   createdAt: string,
	 *   updatedAt: string,
	 *   note: string|null,
	 * }
	 */
	private function formatEntry(Entry $entry): array {
		$numericValue = $entry->getNumericValue();

		return [
			'id' => $entry->getId(),
			'metricKey' => $entry->getMetricKey(),
			'numericValue' => $numericValue === null ? null : (int)$numericValue,
			'optionValue' => $entry->getOptionValue(),
			'context' => $entry->getContext(),
			'source' => $this->formatSource($entry->getSource()),
			'recordedAt' => $this->formatTimestamp($entry->getRecordedAt()),
			'createdAt' => $this->formatTimestamp($entry->getCreatedAt()),
			'updatedAt' => $this->formatTimestamp($entry->getUpdatedAt()),
			'note' => $entry->getNote(),
		];
	}

	private function formatTimestamp(DateTimeInterface $timestamp): string {
		return DateTimeImmutable::createFromInterface($timestamp)
			->setTimezone($this->utc)
			->format('Y-m-d\TH:i:s\Z');
	}

	/** @return 'web'|'api'|'mobile'|'notification' */
	private function formatSource(string $source): string {
		return match ($source) {
			'web' => 'web',
			'api' => 'api',
			'mobile' => 'mobile',
			'notification' => 'notification',
			default => throw new \LogicException('Unknown persisted entry source.'),
		};
	}
}
