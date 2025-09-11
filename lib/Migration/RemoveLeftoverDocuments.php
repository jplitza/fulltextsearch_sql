<?php
namespace OCA\FullTextSearch_SQL\Migration;

use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use OCA\FullTextSearch_SQL\Db\IndexDocumentMapper;


/**
 * Early versions didn't correctly delete documents from the DB when they should have been removed.
 * 
 * This repair step thus deletes all documents which don't have a matching index record in the DB.
 */

class RemoveLeftoverDocuments implements IRepairStep {

	protected IDBConnection $db;

	public function __construct(IDBConnection $db) {
		$this->db = $db;
	}

	/**
	 * Returns the step's name
	 */
	public function getName() {
		return 'Remove left-over fulltextsearch documents';
	}

	/**
	 * @param IOutput $output
	 */
	public function run(IOutput $output) {
		$sub_qb = $this->db->getQueryBuilder();
		$sub_qb->select('doc.id')->from(IndexDocumentMapper::TABLE, 'doc')
			->leftJoin(
				'doc', 'fulltextsearch_index', 'index',
				$sub_qb->expr()->andX(
					$sub_qb->expr()->eq('doc.provider_id', 'index.provider_id'),
					$sub_qb->expr()->eq('doc.document_id', 'index.document_id'),
				)
			)
			->where(
				$sub_qb->expr()->isNull('index.status')
			);
		$qb = $this->db->getQueryBuilder();
		$qb->delete(IndexDocumentMapper::TABLE)
			->where(
				$qb->expr()->in('id', $qb->createFunction($sub_qb->getSQL()))
			);

		$deleted = $qb->executeStatement();
		$output->info("$deleted left-over fulltext documents removed.");
	}
}