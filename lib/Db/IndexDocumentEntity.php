<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Jan-Philipp Litza
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch_SQL\Db;

use OCP\DB\Types;
use OCP\AppFramework\Db\Entity;

class IndexDocumentEntity extends Entity {
	protected $documentId;
	protected $providerId;
	protected $modified;
	protected $owner;
	protected $accessUsers;
	protected $accessCircles;
	protected $accessGroups;
	protected $accessLinks;
	protected $tags;
	protected $metadata;
	protected $subtags;
	protected $parts;
	protected $link;
	protected $title;
	protected $content;
	protected $score;

	public function __construct() {
		$this->addType('documentId', Types::STRING);
		$this->addType('providerId', Types::STRING);
		$this->addType('modified', Types::DATETIME_IMMUTABLE);
		$this->addType('owner', Types::STRING);
		$this->addType('accessUsers', Types::JSON);
		$this->addType('accessCircles', Types::JSON);
		$this->addType('accessGroups', Types::JSON);
		$this->addType('accessLinks', Types::JSON);
		$this->addType('tags', Types::JSON);
		$this->addType('metadata', Types::JSON);
		$this->addType('subtags', Types::JSON);
		$this->addType('parts', Types::JSON);
		$this->addType('link', Types::STRING);
		$this->addType('title', Types::STRING);
		$this->addType('content', Types::BLOB);
		$this->addType('score', Types::FLOAT);
	}
}