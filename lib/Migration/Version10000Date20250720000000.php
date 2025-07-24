<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Jan-Philipp Litza
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch_SQL\Migration;

use OCP\IDBConnection;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version10000Date20250720000000 extends SimpleMigrationStep {
	public const TABLE = 'fts_documents';
	private $collations = [
		IDBConnection::PLATFORM_MYSQL => 'utf8mb4_unicode_ci',
		IDBConnection::PLATFORM_POSTGRES => 'unicode',
	];

	public function __construct(
		private IDBConnection $db
	) {
	}

	public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options) {
		$schema = $schemaClosure();
		if (!$schema->hasTable(self::TABLE)) {
			$table = $schema->createTable(self::TABLE);
			$table->addColumn('id', Types::BIGINT, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 6,
			]);
			$table->addColumn('document_id', Types::STRING, [
				'notnull' => true
			]);
			$table->addColumn('provider_id', Types::STRING, [
				'notnull' => true
			]);
			$table->addColumn('modified', Types::DATETIME_IMMUTABLE, [
				'notnull' => true
			]);
			$table->addColumn('owner', Types::STRING, [
				'notnull' => true
			]);
			$table->addColumn('access_users', Types::JSON, [
				'notnull' => true,
				'customSchemaOptions' => [
					'jsonb' => true,
				]
			]);
			$table->addColumn('access_circles', Types::JSON, [
				'notnull' => true,
				'customSchemaOptions' => [
					'jsonb' => true,
				]
			]);
			$table->addColumn('access_groups', Types::JSON, [
				'notnull' => true,
				'customSchemaOptions' => [
					'jsonb' => true,
				]
			]);
			$table->addColumn('access_links', Types::JSON, [
				'notnull' => true,
				'customSchemaOptions' => [
					'jsonb' => true,
				]
			]);
			$table->addColumn('tags', Types::JSON, [
				'notnull' => true,
				'customSchemaOptions' => [
					'jsonb' => true,
				]
			]);
			$table->addColumn('metadata', Types::JSON, [
				'notnull' => true,
				'customSchemaOptions' => [
					'jsonb' => true,
				]
			]);
			$table->addColumn('subtags', Types::JSON, [
				'notnull' => true,
				'customSchemaOptions' => [
					'jsonb' => true,
				]
			]);
			$table->addColumn('parts', Types::JSON, [
				'notnull' => true,
				'customSchemaOptions' => [
					'jsonb' => true,
				]
			]);
			$table->addColumn('link', Types::STRING, [
				'notnull' => true
			]);
			$table->addColumn('title', Types::STRING, [
				'notnull' => true
			]);
			$table->addColumn('content', Types::TEXT, [
				'notnull' => true,
				'customSchemaOptions' => [
					'collation' => $this->collations[$this->db->getDatabaseProvider()],
				]
			]);
			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['provider_id', 'document_id'], self::TABLE . '_document_id');
		}
		return $schema;
	}
}