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
	// TODO: Access
	// TODO: Tags
	// TODO: Metadata Tags
	// TODO: Subtags
	// TODO: Parts
	protected $link;
	protected $title;
	protected $content;

	public function __construct() {
		$this->addType('documentId', Types::STRING);
		$this->addType('providerId', Types::STRING);
		$this->addType('modified', Types::DATETIME_IMMUTABLE);
		$this->addType('owner', Types::STRING);
		$this->addType('link', Types::STRING);
		$this->addType('title', Types::STRING);
		$this->addType('content', Types::BLOB);
	}
}