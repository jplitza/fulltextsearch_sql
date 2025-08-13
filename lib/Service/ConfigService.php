<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FullTextSearch_SQL\Service;


use OCA\FullTextSearch_SQL\Exceptions\ConfigurationException;
use OCP\AppFramework\Services\IAppConfig;

/**
 * Class ConfigService
 *
 * @package OCA\FullTextSearch_SQL\Service
 */
class ConfigService {

	const ENCODINGS = 'encodings';
	const EXCERPT_CONTEXT = 'excerpt_context';
	
	public static array $defaults = [
		self::ENCODINGS => 'UTF-8, ISO-8859-1',
		self::EXCERPT_CONTEXT => 30
	];

	public function __construct(
		private IAppConfig $appConfig
	) {
	}


	/**
	 * @return array
	 */
	public function getConfig(): array {
		$keys = array_keys(self::$defaults);
		$data = [];

		foreach ($keys as $k) {
			if (is_int(self::$defaults[$k])) {
				$data[$k] = $this->getAppValueInt($k);
			} else {
				$data[$k] = $this->getAppValueString($k);
			}
		}

		return $data;
	}


	/**
	 * @param array $save
	 */
	public function setConfig(array $save) {
		$keys = array_keys(self::$defaults);

		foreach ($keys as $k) {
			if (array_key_exists($k, $save)) {
				if (is_int(self::$defaults[$k])) {
					$this->setAppValueInt($k, intval($save[$k]));
				} else {
					$this->setAppValueString($k, $save[$k]);
				}
			}
		}
	}

	/**
	 * Get a value by key
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	public function getAppValueString(string $key): string {
		$defaultValue = null;
		if (array_key_exists($key, self::$defaults)) {
			$defaultValue = self::$defaults[$key];
		}

		return $this->appConfig->getAppValueString($key, $defaultValue);
	}

	/**
	 * Get a value by key
	 *
	 * @param string $key
	 *
	 * @return int
	 */
	public function getAppValueInt(string $key): int {
		$defaultValue = null;
		if (array_key_exists($key, self::$defaults)) {
			$defaultValue = self::$defaults[$key];
		}

		return $this->appConfig->getAppValueInt($key, $defaultValue);
	}

	/**
	 * Set a value by key
	 *
	 * @param string $key
	 * @param string $value
	 */
	public function setAppValueString(string $key, string $value) {
		$this->appConfig->setAppValueString($key, $value);
	}

	/**
	 * Set a value by key
	 *
	 * @param string $key
	 * @param int $value
	 */
	public function setAppValueInt(string $key, int $value) {
		$this->appConfig->setAppValueInt($key, $value);
	}

	/**
	 * remove a key
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	public function deleteAppValue(string $key): string {
		return $this->appConfig->deleteAppValue($key);
	}

	/**
	 * check json sent by admin front-end are valid.
	 *
	 * @param array $data
	 *
	 * @return bool
	 */
	public function checkConfig(array $data): bool {
		return array_intersect_key($data, self::$defaults) == $data;
	}
}