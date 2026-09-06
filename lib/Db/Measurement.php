<?php

declare(strict_types=1);

namespace OCA\Health\Db;

use DateTimeImmutable;
use OCP\AppFramework\Db\Entity;
use OCP\DB\Types;

/**
 * @psalm-suppress PropertyNotSetInConstructor The ID is populated by Nextcloud's Entity/Mapper lifecycle.
 * @psalm-suppress PossiblyUnusedMethod Entity accessors are used by hydration and mapper consumers.
 */
class Measurement extends Entity {
	protected ?string $userId = null;
	protected ?string $metricKey = null;
	protected ?string $numericValue = null;
	protected ?string $groupId = null;
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

	/** @psalm-suppress PossiblyUnusedMethod Used by Nextcloud entity hydration and mapper consumers. */
	public function getUserId(): string {
		return $this->userId ?? '';
	}
	public function setUserId(string $value): void {
		$this->setter('userId', [$value]);
	}
	public function getMetricKey(): string {
		return $this->metricKey ?? '';
	}
	public function setMetricKey(string $value): void {
		$this->setter('metricKey', [$value]);
	}
	public function getNumericValue(): string {
		return $this->numericValue ?? '';
	}
	public function setNumericValue(string $value): void {
		$this->setter('numericValue', [$value]);
	}
	public function getGroupId(): ?string {
		return $this->groupId;
	}
	public function setGroupId(?string $value): void {
		$this->setter('groupId', [$value]);
	}
	public function getContext(): string {
		return $this->context ?? '';
	}
	public function setContext(string $value): void {
		$this->setter('context', [$value]);
	}
	public function getSource(): string {
		return $this->source ?? '';
	}
	public function setSource(string $value): void {
		$this->setter('source', [$value]);
	}
	/** @psalm-suppress PossiblyUnusedMethod Used by Nextcloud entity hydration. */
	public function getClientOperationId(): ?string {
		return $this->clientOperationId;
	}
	public function setClientOperationId(?string $value): void {
		$this->setter('clientOperationId', [$value]);
	}
	public function getRecordedAt(): DateTimeImmutable {
		return $this->recordedAt ?? throw new \LogicException('recordedAt has not been set.');
	}
	public function setRecordedAt(DateTimeImmutable $value): void {
		$this->setter('recordedAt', [$value]);
	}
	public function getCreatedAt(): DateTimeImmutable {
		return $this->createdAt ?? throw new \LogicException('createdAt has not been set.');
	}
	public function setCreatedAt(DateTimeImmutable $value): void {
		$this->setter('createdAt', [$value]);
	}
	public function getUpdatedAt(): DateTimeImmutable {
		return $this->updatedAt ?? throw new \LogicException('updatedAt has not been set.');
	}
	public function setUpdatedAt(DateTimeImmutable $value): void {
		$this->setter('updatedAt', [$value]);
	}
	public function getNote(): ?string {
		return $this->note;
	}
	public function setNote(?string $value): void {
		$this->setter('note', [$value]);
	}
}
