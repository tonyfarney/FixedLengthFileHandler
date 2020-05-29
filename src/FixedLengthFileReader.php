<?php
namespace FixedLengthFileHandler;

require_once dirname(__FILE__).'/FixedLengthFileHandler.php';

class FixedLengthFileReader extends FixedLengthFileHandler {
	private $_amounLinesLoaded;
	private $_currentLine;
	private $_loadedLines;
	private $_lineDetectionCallback;
	
	public function __construct() {
		$this->reset();
	}

	/**
	 * {@inheritDoc}
	 * @see FixedLengthFileHandler::reset()
	 */
	public function reset(): parent {
		$this->_currentLine = $this->_amounLinesLoaded = 0;
		$this->_loadedLines = [];
		$this->_lineDetectionCallback = null;
		return parent::reset();
	}
	
	/**
	 * return array Associative array containing the lines loaded and it's fields.
	 * @see self::loadLine[]
	 */
	public function getLoadedLines() {
		return $this->_loadedLines;
	}

	/**
	 * Loads a line
	 * @param string $lineId
	 * @param string $line The raw line
	 * @return array The loaded line. [
	 *   'lineId' => string
	 *   'rawLine' => string
	 *   'fields' => [
	 *     'fieldId' => mixed (the content read),
	 *     ...
	 *   ] 
	 * ]
	 * @throws FixedLengthFileException
	 */
	public function loadLine(string $lineId, string $line): array {
		if (!$this->issetLine($lineId)) {
			$this->throwLineDoesntExistsException($lineId);
		}
		$this->_amounLinesLoaded++;
		$loadedLine = [
			'lineId' => $lineId,
			'rawLine' => $line,
			'fields' => [],
		];
		$i = 0;
		foreach ($this->getLine($lineId) as $fieldConfig) {
			$loadedLine['fields'][$fieldConfig['id']] = $this->getProcessedFieldValue(
				$fieldConfig, substr($line, $i, $fieldConfig['size'])
			);
			$i += $fieldConfig['size'];
		}
		$this->_loadedLines[] = $loadedLine;
		return $loadedLine;
	}

	/**
	 * Loads all lines. Obs: requires the line detection callback to be set
	 * @param string $fileContent The file content
	 * @return @see self::getLoadedLines
	 * @throws FixedLengthFileException
	 */
	public function loadFile(string $fileContent): array {
		if (!$this->_lineDetectionCallback) {
			$this->throwLineDetectionCallbackNotSet();
		}
		$lines = explode("\n", rtrim(str_replace("\r\n", "\n", $fileContent), "\n")); // Removes blank lines at the end of the file
		$this->_currentLine = 0;
		foreach ($lines as $line) {
			$this->_currentLine++;
			$this->loadLine($this->detectLineId($line), $line);
		}
		return $this->getLoadedLines();
	}
	
	/**
	 * Sets a callback function that must return the line ID ou null in case
	 * of not capable to detect the line. The function signature must be as follow:
	 *   function (string $line, FixedLenghtFileReader $this, array $extraInfo): ?string;
	 * @param callable $callback
	 * @return self
	 */
	public function setLineDetectionCallback(callable $callback): self {
		$this->_lineDetectionCallback = $callback;
		return $this;
	}

	/**
	 * Detects the line ID by its raw content
	 * @param string $line
	 * @param array $extraInfo Extra informations that can be sent to the callback function
	 * @return string|NULL
	 * @throws FixedLengthFileException
	 */
	public function detectLineId(string $line, array $extraInfo = []): ?string {
		if (!$this->_lineDetectionCallback) {
			$this->throwLineDetectionCallbackNotSet();
		}
		$lineId = call_user_func_array($this->_lineDetectionCallback, [$line, $this, $extraInfo]);
		if (empty($lineId) && !($lineId === '0' || $lineId === 0)) {
			$this->throwLineNotDetectedException();
		}
		if (!$this->issetLine($lineId)) {
			$this->throwLineDoesntExistsException($lineId);
		}
		return $lineId;
	}
	
	/**
	 * Returns the current line number processing. Useful in case of any error
	 * while loading a file
	 * @return int
	 */
	public function getCurrentLineNumber(): int {
		return $this->_currentLine;
	}
	
	/**
	 * @throws FixedLengthFileException
	 */
	protected function throwLineDetectionCallbackNotSet() {
		throw new FixedLengthFileException(
			'Line detection callback not set',
			FixedLengthFileException::REQUIRED_CALLBACK_NOT_SET
		);
	}
	
	/**
	 * @throws FixedLengthFileException
	 */
	protected function throwLineNotDetectedException() {
		throw new FixedLengthFileException(
			'Line detection failed in line number '.$this->_currentLine,
			FixedLengthFileException::LINE_NOT_DETECTED_EXCEPTION
		);
	}
}