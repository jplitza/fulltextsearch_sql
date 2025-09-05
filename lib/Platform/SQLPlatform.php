<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Jan-Philipp Litza
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch_SQL\Platform;

use OC\FullTextSearch\Model\IndexDocument;
use OC\FullTextSearch\Model\DocumentAccess;
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
use OCA\FullTextSearch_SQL\Service\ConfigService;

class SQLPlatform implements IFullTextSearchPlatform {
	private ?IRunner $runner = null;

	public function __construct(
		private ConfigService $configService,
		private IDBConnection $db,
		private IndexDocumentMapper $indexDocumentMapper
	) {
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
		return 'Nextcloud database (SQL)';
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
		return $this->configService->getConfig();
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
		// These are actually schema changes and should go into a migration.
		// But sadly, changeSchema() doesn't have access to the table if the current run should create it, and postSchemaChange() isn't executed when first enabling the app.

		$tablename = $this->db->getQueryBuilder()->prefixTableName(IndexDocumentMapper::TABLE);
		switch ($this->db->getDatabaseProvider()) {
			case IDBConnection::PLATFORM_MYSQL:
				$this->db->executeQuery("ALTER TABLE " . $tablename . " ADD FULLTEXT INDEX IF NOT EXISTS " . IndexDocumentMapper::TABLE . "_content (content)");
				// Apparently if we don't set this, Nextcloud decides to set it during the next repair, while *also* overwriting our collation!
				$this->db->executeQuery("ALTER TABLE " . $tablename . " ROW_FORMAT = DYNAMIC;");
				break;
			case IDBConnection::PLATFORM_POSTGRES:
				// TODO: Make language configurable
				$this->db->executeQuery("CREATE INDEX IF NOT EXISTS " . IndexDocumentMapper::TABLE . "_content ON " . $tablename . " USING GIN (to_tsvector('english', content))");
				break;
		}
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
				$access = $document->getAccess();
				$indexDocument->setAccessUsers($access->getUsers());
				$indexDocument->setAccessCircles($access->getCircles());
				$indexDocument->setAccessGroups($access->getGroups());
				$indexDocument->setAccessLinks($access->getLinks());
				$indexDocument->setTags($document->getTags());
				$indexDocument->setMetadata($document->getMetaTags());
				$indexDocument->setSubtags($document->getSubTags());
				$indexDocument->setParts($document->getParts());
				$indexDocument->setLink($document->getLink());
				$indexDocument->setTitle($document->getTitle());

				$content = $document->getContent();
				if ($document->isContentEncoded() === IIndexDocument::ENCODED_BASE64) {
					$content = base64_decode($content);
				}
				if (str_starts_with($content, '%PDF-')) {
					$config = new \Smalot\PdfParser\Config();
					$config->setRetainImageContent(false);
					$config->setIgnoreEncryption(true);
					$parser = new \Smalot\PdfParser\Parser([], $config); 
					$pdf = $parser->parseContent($content);
					$content = $pdf->getText();
				} elseif (
					str_starts_with($content, "<mxfile ") // draw.io file
					|| !mb_detect_encoding($content, "UTF-8", true) // binary file
				) {
					$content = '';
				}
				
				$content = str_replace("\0", "", $content);
				$encodings = $this->configService->getAppValueString(ConfigService::ENCODINGS);
				$content = mb_convert_encoding($content, "UTF-8", $encodings);
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
		$starttime = hrtime(true);
		$rawResults = $this->indexDocumentMapper->search(
			$result->getRequest(),
			$result->getProvider()->getId(),
			$access
		);
		$endtime = hrtime(true);
		$result->setDocuments(array_map(
			[$this, 'resultToIndexDocument'],
			$rawResults
		));
		$result->setTotal(count($rawResults));
		$result->setTime(intval(($endtime - $starttime) / 1e6));

		$contextlen = $this->configService->getAppValueInt("excerpt_context");
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
		return $this->resultToIndexDocument(
			$this->indexDocumentMapper->find($providerId, $documentId)
		);
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
		$access = new DocumentAccess($result->getOwner());
		$access->setUsers($result->getAccessUsers());
		$access->getCircles($result->getAccessCircles());
		$access->getGroups($result->getAccessGroups());
		$access->getLinks($result->getAccessLinks());

		$index = new IndexDocument($result->getProviderId(), $result->getDocumentId());
		$index->setAccess($access);
		$index->setMetaTags($result->getMetadata());
		$index->setSubTags($result->getSubtags());
		$index->setTags($result->getTags());
		//$index->setHash($result['_source']['hash']);
		//$index->setSource($result['_source']['source']);
		$index->setModifiedTime($result->getModified()->getTimestamp());
		$index->setContent($result->getContent());
		$index->setTitle($result->getTitle());
		$index->setScore(strval($result->getScore()));
		$index->setParts($result->getParts());
		return $index;
	}

	private function findSpace(string $haystack, int $offset, int $tolerance, bool $reverse = false) {
		if ($offset > mb_strlen($haystack)) {
			return $offset;
		}

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
