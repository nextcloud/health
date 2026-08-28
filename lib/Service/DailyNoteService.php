<?php

declare(strict_types=1);

namespace OCA\Health\Service;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use OCA\Health\Db\DailyNote;
use OCA\Health\Db\DailyNoteMapper;
use OCA\Health\Exception\InvalidEntryException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;

class DailyNoteService {
	private const MAXIMUM_CONTENT_LENGTH = 2000;

	private DateTimeZone $utc;

	public function __construct(
		private DailyNoteMapper $dailyNoteMapper,
	) {
		$this->utc = new DateTimeZone('UTC');
	}

	/** @return array{date: string, content: string|null, createdAt: string|null, updatedAt: string|null} */
	public function get(string $userId, mixed $localDate): array {
		$this->requireUserId($userId);
		$date = $this->validateDate($localDate);

		try {
			return $this->formatDailyNote($this->dailyNoteMapper->findForUserAndDate($userId, $date));
		} catch (DoesNotExistException) {
			return [
				'date' => $date,
				'content' => null,
				'createdAt' => null,
				'updatedAt' => null,
			];
		} catch (MultipleObjectsReturnedException $exception) {
			throw new \LogicException('Multiple daily notes exist for one user and date.', 0, $exception);
		}
	}

	/** @return array{date: string, content: string, createdAt: string, updatedAt: string} */
	public function save(string $userId, mixed $localDate, mixed $content): array {
		$this->requireUserId($userId);
		$date = $this->validateDate($localDate);
		$validatedContent = $this->validateContent($content);
		$now = new DateTimeImmutable('now', $this->utc);

		try {
			$dailyNote = $this->dailyNoteMapper->findForUserAndDate($userId, $date);
			$dailyNote->setContent($validatedContent);
			$dailyNote->setUpdatedAt($now);
			return $this->formatDailyNote($this->dailyNoteMapper->updateDailyNote($dailyNote));
		} catch (DoesNotExistException) {
			$dailyNote = new DailyNote();
			$dailyNote->setUserId($userId);
			$dailyNote->setLocalDate($date);
			$dailyNote->setContent($validatedContent);
			$dailyNote->setCreatedAt($now);
			$dailyNote->setUpdatedAt($now);
			return $this->formatDailyNote($this->dailyNoteMapper->create($dailyNote));
		} catch (MultipleObjectsReturnedException $exception) {
			throw new \LogicException('Multiple daily notes exist for one user and date.', 0, $exception);
		}
	}

	private function requireUserId(string $userId): void {
		if ($userId === '') {
			throw new InvalidEntryException('An authenticated user is required.');
		}
	}

	private function validateDate(mixed $localDate): string {
		if (!is_string($localDate)
			|| preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D', $localDate, $matches) !== 1
			|| !checkdate((int)$matches[2], (int)$matches[3], (int)$matches[1])
		) {
			throw new InvalidEntryException('date must be a valid local calendar date in YYYY-MM-DD format.');
		}

		return $localDate;
	}

	private function validateContent(mixed $content): string {
		if (!is_string($content)) {
			throw new InvalidEntryException('Daily note content must be plain text.');
		}

		if (mb_strlen($content) > self::MAXIMUM_CONTENT_LENGTH) {
			throw new InvalidEntryException('Daily note content must not exceed 2000 characters.');
		}

		return $content;
	}

	/** @return array{date: string, content: string, createdAt: string, updatedAt: string} */
	private function formatDailyNote(DailyNote $dailyNote): array {
		return [
			'date' => $dailyNote->getLocalDate(),
			'content' => $dailyNote->getContent(),
			'createdAt' => $this->formatTimestamp($dailyNote->getCreatedAt()),
			'updatedAt' => $this->formatTimestamp($dailyNote->getUpdatedAt()),
		];
	}

	private function formatTimestamp(DateTimeInterface $timestamp): string {
		return DateTimeImmutable::createFromInterface($timestamp)
			->setTimezone($this->utc)
			->format('Y-m-d\TH:i:s\Z');
	}
}
