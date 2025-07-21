<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Jan-Philipp Litza
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch_SQL\Migration;

use OCP\IDBConnection;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version10000Date20250720000000 extends SimpleMigrationStep {
	public const TABLE = 'fts_documents';

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
				'notnull' => true
			]);
			$table->addColumn('access_circles', Types::JSON, [
				'notnull' => true
			]);
			$table->addColumn('access_groups', Types::JSON, [
				'notnull' => true
			]);
			$table->addColumn('access_links', Types::JSON, [
				'notnull' => true
			]);
			$table->addColumn('tags', Types::JSON, [
				'notnull' => true
			]);
			$table->addColumn('metadata', Types::JSON, [
				'notnull' => true
			]);
			$table->addColumn('subtags', Types::JSON, [
				'notnull' => true
			]);
			$table->addColumn('parts', Types::JSON, [
				'notnull' => true
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
					'collation' => 'utf8mb4_unicode_ci'
				]
			]);
			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['provider_id', 'document_id'], self::TABLE . '_document_id');
		}
		return $schema;
	}

	public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options) {
		$tablename = $this->db->getQueryBuilder()->prefixTableName(self::TABLE);
		switch ($this->db->getDatabaseProvider()) {
			case IDBConnection::PLATFORM_MYSQL:
				$this->db->executeQuery("CREATE FULLTEXT INDEX " . self::TABLE . "_content ON " . $tablename . "(content)");

				// Apparently if we don't set this, Nextcloud decides to set it during the next repair, while *also* overwriting our collation!
				$this->db->executeQuery("ALTER TABLE " . $tablename . " ROW_FORMAT = DYNAMIC;");
				break;
			case IDBConnection::PLATFORM_POSTGRES:
				// TODO: Make language configurable
				$this->db->executeQuery("CREATE INDEX " . self::TABLE . "_content ON " . $tablename . " USING GIN (to_tsvector('english', content)");
				break;
			case IDBConnection::PLATFORM_SQLITE:
				// TODO
				throw new Exception("SQLite not implemented yet!");
				break;
		}
	}
}