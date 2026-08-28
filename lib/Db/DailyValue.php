<?php

declare(strict_types=1);

namespace OCA\Health\Db;

use DateTimeImmutable;
use OCP\AppFramework\Db\Entity;
use OCP\DB\Types;

class DailyValue extends Entity {
	protected ?string $userId = null;
	protected ?string $metricKey = null;
	protected ?string $numericValue = null;
	protected ?string $localDate = null;
	protected ?DateTimeImmutable $createdAt = null;
	protected ?DateTimeImmutable $updatedAt = null;

	public function __construct() {
		$this->addType('id', Types::BIGINT);
		$this->addType('numericValue', Types::DECIMAL);
		$this->addType('createdAt', Types::DATETIME_IMMUTABLE);
		$this->addType('updatedAt', Types::DATETIME_IMMUTABLE);
	}

	public function getUserId(): string { return $this->userId ?? ''; }
	public function setUserId(string $value): void { $this->setter('userId', [$value]); }
	public function getMetricKey(): string { return $this->metricKey ?? ''; }
	public function setMetricKey(string $value): void { $this->setter('metricKey', [$value]); }
	public function getNumericValue(): string { return $this->numericValue ?? ''; }
	public function setNumericValue(string $value): void { $this->setter('numericValue', [$value]); }
	public function getLocalDate(): string { return $this->localDate ?? ''; }
	public function setLocalDate(string $value): void { $this->setter('localDate', [$value]); }
	public function getCreatedAt(): DateTimeImmutable { return $this->createdAt ?? throw new \LogicException('createdAt has not been set.'); }
	public function setCreatedAt(DateTimeImmutable $value): void { $this->setter('createdAt', [$value]); }
	public function getUpdatedAt(): DateTimeImmutable { return $this->updatedAt ?? throw new \LogicException('updatedAt has not been set.'); }
	public function setUpdatedAt(DateTimeImmutable $value): void { $this->setter('updatedAt', [$value]); }
}
