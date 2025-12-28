<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Jan-Philipp Litza
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch_SQL\Migration;

use OCP\IConfig;
use OCP\IDBConnection;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version10000Date20251228000000 extends SimpleMigrationStep {
	public const TABLE = 'fts_documents';

	public function __construct(
		private IDBConnection $db,
	) {
	}

	public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options) {
		if ($this->db->getDatabaseProvider() != IDBConnection::PLATTFORM_MYSQL) {
			return;
		}

		$query = $this->db->getQueryBuilder();
		$query->update(self::TABLE)
				->set('content', $query->func()->lower('content'));
		$query->executeStatement();
	}
}