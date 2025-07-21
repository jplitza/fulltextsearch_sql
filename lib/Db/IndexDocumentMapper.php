<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Jan-Philipp Litza
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch_SQL\Db;

use OCP\IDBConnection;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\FullTextSearch\Model\ISearchRequest;
use OCA\FullTextSearch_SQL\Db\IndexDocumentEntity;

class IndexDocumentMapper extends QBMapper {
	private const TABLE = 'fts_documents';

	public function __construct(IDBConnection $db) {
		parent::__construct($db, self::TABLE, IndexDocumentEntity::class);
	}

	public function find(string $providerId, string $documentId) {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from(self::TABLE)
			->where(
				$qb->expr()->eq('document_id', $qb->createNamedParameter($documentId, IQueryBuilder::PARAM_STR))
			);
		
		if ($providerId != "all") {
			$qb->andWhere(
				$qb->expr()->eq('provider_id', $qb->createNamedParameter($providerId, IQueryBuilder::PARAM_STR))
			);
		}

		return $this->findEntity($qb);
	}

	public function search(ISearchRequest $request) {
		$qb = $this->db->getQueryBuilder();

		$qb->select('id', 'document_id', 'provider_id', 'modified', 'owner', 'link', 'title')
			->from(self::TABLE);
		
		if (!in_array("all", $request->getProviders())) {
			$qb->andWhere(
				$qb->expr->in('provider_id', $request->getProviders(), IQueryBuilder::PARAM_STR)
			);
		}

		// TODO: Match access, tags, whatnot...

		switch ($this->db->getDatabaseProvider()) {
			case IDBConnection::PLATFORM_MYSQL:
                $q = 'MATCH (content) AGAINST (:search IN BOOLEAN MODE)';
				$qb->andWhere($q)
                    ->selectAlias($qb->createFunction($q), 'score');
				break;
			case IDBConnection::PLATFORM_POSTGRES:
				$qb->andWhere('to_tsvector(content) @@ to_tsquery(:search)');
				break;
			case IDBConnection::PLATFORM_SQLITE:
				break;
			case IDBConnection::PLATFORM_ORACLE:
				break;
		}
		$qb->setParameter('search', $request->getSearch());

		return $this->findEntities($qb);
	}

	public function deleteAll() {
		$qb = $this->qb->getQueryBuilder();
		$qb->truncate();
	}
}