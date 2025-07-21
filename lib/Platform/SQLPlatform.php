<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Jan-Philipp Litza
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch_SQL\Platform;

use OC\FullTextSearch\Model\IndexDocument;
use OCP\IDBConnection;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\FullTextSearch\IFullTextSearchPlatform;
use OCP\FullTextSearch\Model\IDocumentAccess;
use OCP\FullTextSearch\Model\IIndex;
use OCP\FullTextSearch\Model\IIndexDocument;
use OCP\FullTextSearch\Model\IRunner;
use OCP\FullTextSearch\Model\ISearchResult;
use OCA\FullTextSearch_SQL\Db\IndexDocumentEntity;
use OCA\FullTextSearch_SQL\Db\IndexDocumentMapper;

class SQLPlatform implements IFullTextSearchPlatform {
	private ?IRunner $runner = null;
	private IDBConnection $db;
	private IndexDocumentMapper $indexDocumentMapper;

	public function __construct(IDBConnection $db, IndexDocumentMapper $indexDocumentMapper) {
		$this->db = $db;
		$this->indexDocumentMapper = $indexDocumentMapper;
	}
	
	/**
	 * Must returns a unique Id used to identify the Search Platform.
	 * Id must contains only alphanumeric chars, with no space.
	 *
	 * @since 15.0.0
	 *
	 * @return string
	 */
	public function getId(): string {
		return 'sql';
	}


	/**
	 * Must returns a descriptive name of the Search Platform.
	 * This is used mainly in the admin settings page to display the list of
	 * available Search Platform
	 *
	 * @since 15.0.0
	 *
	 * @return string
	 */
	public function getName(): string {
		return 'Nextcloud database';
	}


	/**
	 * should returns the current configuration of the Search Platform.
	 * This is used to display the configuration when using the
	 * ./occ fulltextsearch:check command line.
	 *
	 * @since 15.0.0
	 *
	 * @return array
	 */
	public function getConfiguration(): array {
		return [];
	}


	/**
	 * Set the wrapper of the currently executed process.
	 * Because the index process can be long and heavy, and because errors can
	 * be encountered during the process, the IRunner is a wrapper that allow the
	 * Search Platform to communicate with the process initiated by
	 * FullTextSearch.
	 *
	 * The IRunner is coming with some methods so the Search Platform can
	 * returns important information and errors to be displayed to the admin.
	 *
	 * @since 15.0.0
	 *
	 * @param IRunner $runner
	 */
	public function setRunner(IRunner $runner) {
		$this->runner = $runner;
	}


	/**
	 * Called when FullTextSearch is loading your Search Platform.
	 *
	 * @since 15.0.0
	 */
	public function loadPlatform() {
	}


	/**
	 * Called to check that your Search Platform is correctly configured and that
	 * This is also the right place to check that the Search Service is available.
	 *
	 * @since 15.0.0
	 *
	 * @return bool
	 */
	public function testPlatform(): bool {
		return true;
	}


	/**
	 * Called before an index is initiated.
	 * Best place to initiate some stuff on the Search Server (mapping, ...)
	 *
	 * @since 15.0.0
	 */
	public function initializeIndex() {
	}


	/**
	 * Reset the indexes for a specific providerId.
	 * $providerId can be 'all' if it is a global reset.
	 *
	 * @since 15.0.0
	 *
	 * @param string $providerId
	 */
	public function resetIndex(string $providerId) {
	}


	/**
	 * Deleting some IIndex, sent in an array
	 *
	 * @see IIndex
	 *
	 * @since 15.0.0
	 *
	 * @param IIndex[] $indexes
	 */
	public function deleteIndexes(array $indexes) {
	}


	/**
	 * Indexing a document.
	 *
	 * @see IndexDocument
	 *
	 * @since 15.0.0
	 *
	 * @param IIndexDocument $document
	 *
	 * @return IIndex
	 */
	public function indexDocument(IIndexDocument $document): IIndex {
		$result = [];
		$index = $document->getIndex();

		try {
			if ($index->isStatus(IIndex::INDEX_REMOVE)) {
				$this->indexDocumentMapper->delete(
					$this->indexDocumentMapper->find(
						$document->getProviderId(),
						$document->getId(),
					)
				);
			} else {
				try {
					$indexDocument = $this->indexDocumentMapper->find(
						$document->getProviderId(),
						$document->getId(),
					);
				} catch(DoesNotExistException) {
					$indexDocument = new IndexDocumentEntity();
					$indexDocument->setDocumentId($document->getId());
					$indexDocument->setProviderId($document->getProviderId());
				}
				$indexDocument->setModified((new \DateTimeImmutable())->setTimestamp($document->getModifiedTime()));
				$indexDocument->setOwner($document->getAccess()->getOwnerId());
				$indexDocument->setLink($document->getLink());
				$indexDocument->setTitle($document->getTitle());

				$content = $document->getContent();
				if ($document->isContentEncoded() === IIndexDocument::ENCODED_BASE64) {
					$content = base64_decode($content);
				}
				if (str_starts_with($content, '%PDF-')) {
					$config = new \Smalot\PdfParser\Config();
					$config->setRetainImageContent(false);
					$parser = new \Smalot\PdfParser\Parser([], $config); 
					$pdf = $parser->parseContent($content);
					$content = $pdf->getText();
				}
				$indexDocument->setContent($content);

				if ($indexDocument->getId()) {
					$this->indexDocumentMapper->update($indexDocument);
				} else {
					$this->indexDocumentMapper->insert($indexDocument);
				}
			}

			$index->setLastIndex();
			$index->setStatus(IIndex::INDEX_DONE);
			$this->updateNewIndexResult(
				$index, json_encode(true), 'ok',
				IRunner::RESULT_TYPE_SUCCESS
			);
		} catch (\Exception $e) {
			$result = $e->getMessage();
			$index->setStatus(IIndex::INDEX_FAILED);
			$index->addError(
				$result,
				'',
				IIndex::ERROR_SEV_3
			);
			$this->updateNewIndexResult(
				$index, json_encode($result), 'fail',
				IRunner::RESULT_TYPE_FAIL
			);
		}
		return $index;
	}


	/**
	 * Searching documents, ISearchResult should be updated with the result of
	 * the search.
	 *
	 * @since 15.0.0
	 *
	 * @param ISearchResult $result
	 * @param IDocumentAccess $access
	 */
	public function searchRequest(ISearchResult $result, IDocumentAccess $access) {
		$result->setDocuments(array_map(
			[$this, 'resultToIndexDocument'],
			$this->indexDocumentMapper->search($result->getRequest())
		));

		// TODO: make configurable
		$contextlen = 30;
		foreach ($result->getDocuments() as $document) {
			$content = $document->getContent();
			if (preg_match_all('/\w+/', $result->getRequest()->getSearch(), $matches, PREG_PATTERN_ORDER)) {
				foreach ($matches[0] as $term) {
					$matchpos = mb_stripos($content, $term);
					if ($matchpos === false) {
						continue;
					}
					$startpos = $this->findSpace($content, max(0, $matchpos - $contextlen), $contextlen, true);
					$endpos = $this->findSpace($content, $matchpos + strlen($term) + $contextlen, $contextlen);
					$document->addExcerpt(
						$term,
						mb_substr(
							$content,
							$startpos,
							$endpos - $startpos,
						)
					);
				}
			}
		}
	}


	/**
	 * Return a document based on its Id and the Provider.
	 * This is used when an admin execute ./occ fulltextsearch:document:platform
	 *
	 * @since 15.0.0
	 *
	 * @param string $providerId
	 * @param string $documentId
	 *
	 * @return IIndexDocument
	 */
	public function getDocument(string $providerId, string $documentId): IIndexDocument {
		return $this->indexDocumentMapper->find($providerId, $documentId);
	}


	/**
	 * @param IIndex $index
	 * @param string $message
	 * @param string $status
	 * @param int $type
	 */
	private function updateNewIndexResult(IIndex $index, string $message, string $status, int $type) {
		if ($this->runner === null) {
			return;
		}

		$this->runner->newIndexResult($index, $message, $status, $type);
	}

	/**
	 * @param IndexDocumentEntity $result
	 * @return IIndexDocument
	 */
	private function resultToIndexDocument(IndexDocumentEntity $result) {
		$index = new IndexDocument($result->getProviderId(), $result->getDocumentId());
		//$index->setAccess();
		//$index->setMetaTags();
		//$index->setSubTags($result['_source']['subtags']);
		//$index->setTags($result['_source']['tags']);
		//$index->setHash($result['_source']['hash']);
		//$index->setSource($result['_source']['source']);
		$index->setContent($result->getContent());
		$index->setTitle($result->getTitle());
		$index->setScore(strval($result->getScore()));
		//$index->setParts($result['_source']['parts']);
		return $index;
	}

	private function findSpace(string $haystack, int $offset, int $tolerance, bool $reverse = false) {
		if ($reverse) {
			$pos = mb_strrpos(mb_substr($haystack, 0, $offset), ' ');
		} else {
			$pos = mb_strpos($haystack, ' ', $offset);
		}

		if ($pos !== false && abs($offset - $pos) < $tolerance) {
			return $pos;
		} else {
			return $offset;
		}
	}
}