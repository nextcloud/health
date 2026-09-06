<?php

declare(strict_types=1);

namespace OCA\Health\Db;

use DateTimeImmutable;
use OCP\AppFramework\Db\Entity;
use OCP\DB\Types;

/** @psalm-suppress PropertyNotSetInConstructor The inherited ID is assigned by QBMapper. */
class Entry extends Entity {
	protected ?string $userId = null;
	protected ?string $metricKey = null;
	protected ?string $numericValue = null;
	protected ?string $optionValue = null;
	protected ?string $context = null;
	protected ?string $source = null;
	protected ?string $clientOperationId = null;
	protected ?DateTimeImmutable $recordedAt = null;
	protected ?DateTimeImmutable $createdAt = null;
	protected ?DateTimeImmutable $updatedAt = null;
	protected ?string $note = null;

	public function __construct() {
		$this->addType('id', Types::BIGINT);
		$this->addType('numericValue', Types::DECIMAL);
		$this->addType('recordedAt', Types::DATETIME_IMMUTABLE);
		$this->addType('createdAt', Types::DATETIME_IMMUTABLE);
		$this->addType('updatedAt', Types::DATETIME_IMMUTABLE);
	}

	/** @psalm-suppress PossiblyUnusedMethod Called reflectively by QBMapper. */
	public function getUserId(): string {
		return $this->userId ?? '';
	}

	public function setUserId(string $userId): void {
		$this->setter('userId', [$userId]);
	}

	public function getMetricKey(): string {
		return $this->metricKey ?? '';
	}

	public function setMetricKey(string $metricKey): void {
		$this->setter('metricKey', [$metricKey]);
	}

	public function getNumericValue(): ?string {
		return $this->numericValue;
	}

	public function setNumericValue(?string $numericValue): void {
		$this->setter('numericValue', [$numericValue]);
	}

	public function getOptionValue(): ?string {
		return $this->optionValue;
	}

	public function setOptionValue(?string $optionValue): void {
		$this->setter('optionValue', [$optionValue]);
	}

	public function getContext(): string {
		return $this->context ?? '';
	}

	public function setContext(string $context): void {
		$this->setter('context', [$context]);
	}

	public function getSource(): string {
		return $this->source ?? '';
	}

	public function setSource(string $source): void {
		$this->setter('source', [$source]);
	}

	/** @psalm-suppress PossiblyUnusedMethod Used by Nextcloud entity hydration. */
	public function getClientOperationId(): ?string {
		return $this->clientOperationId;
	}

	public function setClientOperationId(?string $clientOperationId): void {
		$this->setter('clientOperationId', [$clientOperationId]);
	}

	public function getRecordedAt(): DateTimeImmutable {
		if ($this->recordedAt === null) {
			throw new \LogicException('recordedAt has not been set.');
		}

		return $this->recordedAt;
	}

	public function setRecordedAt(DateTimeImmutable $recordedAt): void {
		$this->setter('recordedAt', [$recordedAt]);
	}

	public function getCreatedAt(): DateTimeImmutable {
		if ($this->createdAt === null) {
			throw new \LogicException('createdAt has not been set.');
		}

		return $this->createdAt;
	}

	public function setCreatedAt(DateTimeImmutable $createdAt): void {
		$this->setter('createdAt', [$createdAt]);
	}

	public function getUpdatedAt(): DateTimeImmutable {
		if ($this->updatedAt === null) {
			throw new \LogicException('updatedAt has not been set.');
		}

		return $this->updatedAt;
	}

	public function setUpdatedAt(DateTimeImmutable $updatedAt): void {
		$this->setter('updatedAt', [$updatedAt]);
	}

	public function getNote(): ?string {
		return $this->note;
	}

	public function setNote(?string $note): void {
		$this->setter('note', [$note]);
	}
}
