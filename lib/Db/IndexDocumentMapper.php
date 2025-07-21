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
use OCP\FullTextSearch\Model\IDocumentAccess;
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

	public function search(ISearchRequest $request, string $providerId, IDocumentAccess $access) {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from(self::TABLE);

		if ($providerId != "all") {
			$qb->andWhere(
				$qb->expr()->eq('provider_id', $qb->createNamedParameter($providerId, IQueryBuilder::PARAM_STR))
			);
		}

		$qb->andWhere(
			$qb->expr()->orX(
				$qb->expr()->eq('owner', $qb->createNamedParameter($access->getViewerId(), IQueryBuilder::PARAM_STR)),
				'JSON_CONTAINS(access_users, ' . $qb->createNamedParameter(json_encode($access->getViewerId())) . ')',
				'JSON_OVERLAPS(access_groups, ' . $qb->createNamedParameter(json_encode($access->getGroups())) . ')',
				'JSON_OVERLAPS(access_circles, ' . $qb->createNamedParameter(json_encode($access->getCircles())) . ')',
			)
		);
		// TODO: Match tags, subtags, whatnot...

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