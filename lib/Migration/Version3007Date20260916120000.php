<?php

declare(strict_types=1);

namespace OCA\Health\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/** @psalm-suppress UnusedClass Migration is discovered by Nextcloud. */
class Version3007Date20260916120000 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();
		$measurements = $schema->getTable('health_measurements');
		if (!$measurements->hasColumn('option_value')) {
			$measurements->addColumn('option_value', Types::STRING, ['length' => 64, 'notnull' => false]);
		}
		return $schema;
	}
}
