<?php

/**
 * @author Artyom Suchkov <fanasew@gmail.com>
 */
class FileIO {
	/** @var string */
	private $path;

	/**
	 * FileIO constructor
	 *
	 * @param string $path
	 */
	public function __construct($path) {
		$this->path = $path;
	}

	/**
	 * @return string
	 */
	public function get() {
		return file_get_contents($this->path);
	}

	/**
	 * @param string $data
	 */
	public function put($data) {
		file_put_contents($this->path, $data, LOCK_EX);
	}
}
