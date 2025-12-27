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
	public const TABLE = 'fts_documents';

	public function __construct(IDBConnection $db) {
		parent::__construct($db, self::TABLE, IndexDocumentEntity::class);
	}

	private function addQueryWhere(IQueryBuilder $qb, string $providerId, ?string $documentId) {
		if ($providerId != "all") {
			$qb->andWhere(
				$qb->expr()->eq('provider_id', $qb->createNamedParameter($providerId, IQueryBuilder::PARAM_STR))
			);
		}

		if ($documentId != NULL) {
			$qb->andWhere(
				$qb->expr()->eq('document_id', $qb->createNamedParameter($documentId, IQueryBuilder::PARAM_STR))
			);
		}
	}

	public function find(string $providerId, string $documentId) {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from(self::TABLE);
		
		$this->addQueryWhere($qb, $providerId, $documentId);

		return $this->findEntity($qb);
	}

	public function deleteDocument(string $providerId, ?string $documentId = NULL) {
		$qb = $this->db->getQueryBuilder();

		$qb->delete(self::TABLE);

		$this->addQueryWhere($qb, $providerId, $documentId);

		$qb->executeStatement();
	}

	public function deleteAll() {
		if (method_exists($this->db, 'truncateTable')) {
			$this->db->truncateTable(self::TABLE, false);
		} else {
			$qb = $this->db->getQueryBuilder();
			$qb->delete(self::TABLE);
			$qb->executeStatement();
		}
	}

	public function search(ISearchRequest $request, string $providerId, IDocumentAccess $access) {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from(self::TABLE)
			->setFirstResult(($request->getPage() - 1) * $request->getSize())
			->setMaxResults($request->getSize());

		if ($providerId != "all") {
			$qb->andWhere(
				$qb->expr()->eq('provider_id', $qb->createNamedParameter($providerId, IQueryBuilder::PARAM_STR))
			);
		}

		$search = $qb->createNamedParameter($request->getSearch(), IQueryBuilder::PARAM_STR);
		$viewerId = $qb->createNamedParameter($access->getViewerId(), IQueryBuilder::PARAM_STR);
		$jsonViewerId = $qb->createNamedParameter(json_encode($access->getViewerId()), IQueryBuilder::PARAM_STR);
		$jsonGroups = $qb->createNamedParameter(json_encode($access->getGroups()), IQueryBuilder::PARAM_STR);
		$jsonCircles = $qb->createNamedParameter(json_encode($access->getCircles()), IQueryBuilder::PARAM_STR);

		// TODO: Match tags, subtags, whatnot...

		switch ($this->db->getDatabaseProvider()) {
			case IDBConnection::PLATFORM_MYSQL:
				$qb->andWhere(
					$qb->expr()->orX(
						$qb->expr()->eq('owner', $viewerId),
						"JSON_CONTAINS(access_users, $jsonViewerId)",
						"JSON_OVERLAPS(access_groups, $jsonGroups)",
						"JSON_OVERLAPS(access_circles, $jsonCircles)",
					)
				);

				$q = "MATCH (content) AGAINST ($search IN BOOLEAN MODE)";
				$qb->andWhere($q)
					->selectAlias($qb->createFunction($q), 'score');
				break;

			case IDBConnection::PLATFORM_POSTGRES:
				$qb
					->andWhere(
						$qb->expr()->orX(
							$qb->expr()->eq('owner', $viewerId),
							"access_users @> $jsonViewerId::jsonb",
							"jsonb_exists_any(access_groups, JSON_QUERY($jsonGroups, '$' RETURNING text[]))",
							"jsonb_exists_any(access_circles, JSON_QUERY($jsonCircles, '$' RETURNING text[]))",
						)
					)
					->andWhere("to_tsvector('english', content) @@ websearch_to_tsquery($search)");
				break;
		}

		return $this->findEntities($qb);
	}
}
