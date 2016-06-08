<?php

/**
 * @author Artyom Suchkov <fanasew@gmail.com>
 */
class Vivami {
	/** @var string */
	private $dbHash;
	/** @var FileIO */
	private $file;
	/** @var array[array] */
	private $items = [];
	/** @var int */
	private $itemsCounter = 0;

	/**
	 * Database constructor
	 *
	 * @param string $basePath
	 * @param string $database
	 */
	public function __construct($basePath, $database) {
		// fallback without autoload
		if (!class_exists('FileIO')) {
			require('FileIO.php');
		}

		$this->init($basePath, $database);
	}

	/**
	 * @param string $basePath
	 * @param string $database
	 * @throws Exception
	 */
	private function init($basePath, $database) {
		$this->dbHash = $this->getDbHash($database);
		$this->file = new FileIO(sprintf('%s%s.json', $basePath, $this->dbHash));

		$content = $this->file->get();

		if ($content === false) {
			$this->save();
			$content = $this->file->get();
		}

		$values = json_decode($content, true);

		if ($this->dbHash !== $values['dbHash']) {
			throw new Exception('Wrong db!');
		}

		$this->itemsCounter = $values['itemsCounter'];

		$this->items = $values['items'];
	}

	/**
	 * Saves to file
	 */
	public function save() {
		$this->file->put(json_encode(
			[
				'dbHash' => $this->dbHash,
				'items' => $this->items,
				'itemsCounter' => $this->itemsCounter
			]
		));
	}

	/**
	 * @param string $database
	 * @return string
	 */
	private function getDbHash($database) {
		return md5($database);
	}

	private function getNewId() {
		return ++$this->itemsCounter;
	}

	/**
	 * @param string $key
	 * @return bool
	 */
	public function exists($key) {
		return array_key_exists($key, $this->items);
	}

	/**
	 * @param string $key
	 * @return mixed
	 */
	public function get($key) {
		return $this->items[$key];
	}

	/**
	 * @param string $key
	 * @return void
	 */
	public function delete($key) {
		unset($this->items[$key]);
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 * @return bool
	 */
	public function update($key, $value) {
		if (array_key_exists($key, $this->items)) {
			$this->items[$key] = $value;
			return true;
		}

		return false;
	}

	/**
	 * @param mixed $value
	 * @return bool
	 */
	public function insert($value) {
		$value = (array) $value;

		if (isset($value['__ID__'])) {
			unset($value['__ID__']);
		}

		$key = $this->getNewId();
		$value['__ID__'] = $key;

		if (array_key_exists($key, $this->items)) {
			return false;
		}

		$this->items[$key] = $value;

		return true;
	}

	/**
	 * @param array $fields
	 * @param int $limit
	 * @return array[array]
	 */
	public function find($fields, $limit = 0) {
		$results = [];

		foreach ($this->items as $item) {
			$i = 0;
			foreach ($fields as $fieldKey => $fieldValue) {
				if (!array_key_exists($fieldKey, $item)) continue;
				if ($item[$fieldKey] === $fieldValue) {
					$i++;
					continue;
				}

				$sub = explode('.', $fieldKey);
				if (count($sub) > 1) {
					$finalValue = $item;

					foreach ($sub as $innerKey) {
						if (!isset($finalValue[$innerKey])) break;
						$finalValue = $finalValue[$innerKey];
					}

					if ($finalValue === $fieldValue) {
						$i++;
					}
				}
			}

			if ($i === count($fields)) {
				$results[] = $item;

				if ($limit > 0 && count($results) >= $limit) return $results;
			}
		}

		return $results;
	}

	/**
	 * @param array $fields
	 * @return mixed
	 */
	public function findOne($fields) {
		$results = $this->find($fields, 1);

		if (count($results) === 0) return false;

		return $results[0];
	}
}