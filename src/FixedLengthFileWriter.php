<?php
namespace FixedLengthFileHandler;

require_once dirname(__FILE__).'/FixedLengthFileHandler.php';

class FixedLengthFileWriter extends FixedLengthFileHandler {
	private $_lineDelimiter;
	private $_generatedLines = [];
	
	public function __construct() {
		$this->reset();
	}

	/**
	 * Cleans all configurations and informations loaded
	 * @return self
	 */
	public function reset(): parent {
		$this->_lineDelimiter = "\n";
		$this->_generatedLines = [];
		return parent::reset();
	}
	
	/**
	 * Save the loaded lines to a file
	 * @param string $file File to save to
	 * @return boolean
	 */
	public function saveToFile(string $file): bool {
		return file_put_contents($file, $this->getGeneratedFileContent()) !== false;
	}

	/**
	 * @return array Lines generated
	 */
	public function getGeneratedLines(): array {
		return $this->_generatedLines;
	}
	
	/**
	 * @return string The generated file content
	 */
	public function getGeneratedFileContent(): array {
		return implode($this->getLineDelimiter(), $this->getGeneratedLines());
	}

	/**
	 * return array Associative array containing the lines loaded and it's fields.
	 * @see self::loadLine[]
	 */
	public function getLoadedLines() {
		return $this->_loadedLines;
	}

	/**
	 * Sets the line delimiter
	 * @param string $delimiter
	 * @return self
	 */
	public function setLineDelimiter(string $delimiter): self {
		$this->_lineDelimiter = $delimiter;
		return $this;
	}
	
	/**
	 * Returns the line delimiter
	 * @return string The line delimiter
	 */
	public function getLineDelimiter(): string {
		return $this->_lineDelimiter;
	}

	/**
	 * Generates a new line with the data provided
	 * @param string $lineId
	 * @param array $data The array must have the fields ID in its indexes
	 * @return string The line generated
	 * @throws FixedLengthFileException
	 */
	public function generateLine(string $lineId, array $data): string {
		if (!$this->issetLine($lineId)) {
			$this->throwLineDoesntExistsException($lineId);
		}
		$line = '';
		foreach ($this->getLine($lineId) as $fieldConfig) {
			$line .= $this->getProcessedFieldValue(
				$fieldConfig, $data[$fieldConfig['id']] ?? null, ['_rawLineData' => $data]
			) ;
		}
		$this->_generatedLines[] = $line;
		return $line;
	}
}