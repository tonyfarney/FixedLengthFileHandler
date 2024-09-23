<?php
namespace FixedLengthFileHandler;

abstract class FixedLengthFileHandler {
	private $_indexedLinesDefinitions; // It's just for performance purposes
	private $_linesDefinitions;
	private $_fieldProcessorCallback;
	
	public function __construct() {
		$this->reset();
	}
	
	/**
	 * Resets and sets a new configuration
	 * @param array $lines The index is the line ID and value the fields configuration
         * @return static
	 * @throws FixedLengthFileException
	 */
	public function setLines(array $lines): self {
		$this->reset();
		foreach ($lines as $lineId => $fields) {
			$this->addLine($lineId, $fields);
		}
		return $this;
	}
	
	/**
	 * Adds the definition for a line type
	 * @param string $lineId ID for the line type
	 * @param CampoPosicional[] $fields
	 * @return static
	 * @throws FixedLengthFileException
	 */
	public function addLine($lineId, array $fields): self {
		if ($this->issetLine($lineId)) {
			$this->throwLineAlreadyExistsException($lineId);
		}
		$this->_linesDefinitions[$lineId] = [];
		$this->_indexedLinesDefinitions[$lineId] = [];
		foreach ($fields as $field) {
			$this->addField($lineId, $field);
		}
		return $this;
	}
	
	/**
	 * Removes a line configuration. None exception is thrown if the line doesn't exists
	 * @param string $lineId
	 * @return static
	 * @throws FixedLengthFileException
	 */
	public function removeLine(string $lineId): self {
		if (!$this->issetLine($lineId)) {
			$this->throwLineDoesntExistsException($lineId);
		}
		unset($this->_linesDefinitions[$lineId], $this->_indexedLinesDefinitions[$lineId]);
		return $this;
	}
	
	/**
	 * Removes a field
	 * @param string $lineId
	 * @param string $fieldId
	 * @return static
	 * @throws FixedLengthFileException
	 */
	public function removeField(string $lineId, string $fieldId): self {
		if (!$this->issetLine($lineId)) {
			$this->throwLineDoesntExistsException($lineId);
		}
		if (!isset($this->_indexedLinesDefinitions[$lineId][$fieldId])) {
			$this->throwFieldDoesntExistsException($lineId, $fieldId);
		}
		$newLineDefinition = [];
		foreach ($this->getLine($lineId) as $fieldConfig) {
			if ($fieldConfig['id'] === $fieldId) { // Ignores the removed field
				continue;
			}
			$newLineDefinition[] = $fieldConfig;
		}
		$this->_linesDefinitions[$lineId] = $newLineDefinition;
		unset($this->_indexedLinesDefinitions[$lineId][$fieldId]);
		return $this;
	}
	
	/**
	 * Returns the line configuration
	 * @param string $lineId
	 * @return array|NULL Returns null if the line was not found
	 */
	public function getLine(string $lineId): ?array {
		if ($this->issetLine($lineId)) {
			return $this->_linesDefinitions[$lineId];
		}
		return null;
	}
	
	/**
	 * Adds a field. If the field ID already exists, overwrite the previous configuration
	 * @param string $lineId
	 * @param array $newFieldConfig [
	 *   'id' => string (required),
	 *   'size' => int (required),
	 *   ... any other information you want
	 * ]
	 * @param string $insertBeforeFieldId If null or not provided, will insert at the end of the line
	 * @return static
	 * @throws FixedLengthFileException
	 */
	public function addField(string $lineId, array $newFieldConfig, string $insertBeforeFieldId = null): self {
		if (
			!isset($newFieldConfig['id']) || !isset($newFieldConfig['size'])
			|| !is_int($newFieldConfig['size']) || $newFieldConfig['size'] < 1
		) {
			$this->throwInvalidFieldConfigException($lineId);
		}
		if (!$this->issetLine($lineId)) {
			$this->throwLineDoesntExistsException($lineId);
		}
		if (isset($this->_indexedLinesDefinitions[$lineId][$newFieldConfig['id']])) {
			$this->throwFieldAlreadyExistsException($lineId, $newFieldConfig['id']);
		}
		
		// Adiciono o campo na linha
		if (!$insertBeforeFieldId) { // Insere no final da linha
			$this->_linesDefinitions[$lineId][] = $newFieldConfig;
		} else {
			if (!isset($this->_indexedLinesDefinitions[$lineId][$insertBeforeFieldId])) {
				$this->throwFieldDoesntExistsException($lineId, $insertBeforeFieldId);
			}
			$newLineDefinition = [];
			foreach ($this->getLine($lineId) as $fieldConfig) {
				if ($fieldConfig['id'] === $insertBeforeFieldId) {
					$newLineDefinition[$newFieldConfig['id']] = $newFieldConfig;
				}
				$newLineDefinition[] = $fieldConfig;
			}
			$this->_linesDefinitions[$lineId] = $newLineDefinition;
		}
		$this->_indexedLinesDefinitions[$lineId][$newFieldConfig['id']] = $newFieldConfig;
		return $this;
	}
	
	/**
	 * Returns the field configuration
	 * @param string $lineId
	 * @param string $fieldId
	 * @return array|NULL Returns null if the field was not found
	 */
	public function getField(string $lineId, string $fieldId): ?array {
		if ($this->issetLine($lineId) && isset($this->_indexedLinesDefinitions[$lineId][$fieldId])) {
			return $this->_indexedLinesDefinitions[$lineId][$fieldId];
		}
		return null;
	}
	
	/**
	 * Checks if exists configuration for the line
	 * @param string $lineId
	 * @return bool
	 */
	public function issetLine(string $lineId) {
		return isset($this->_linesDefinitions[$lineId]);
	}

	/**
	 * Cleans all configurations and informations loaded
	 * @return self
	 */
	public function reset(): self {
		$this->_linesDefinitions = $this->_indexedLinesDefinitions = [];
		$this->_fieldProcessorCallback = null;
		return $this;
	}
	
	/**
	 * Sets a callback function that must return the processed value given the
	 * field configuration and the raw value. The function signature must be as follow:
	 *   function (array $fieldConfig, $rawValue, FixedLengthFile(Reader|Writer) $this, array $extraInfo): mixed;
	 * @param callable $callback
	 * @return static
	 */
	public function setFieldProcessorCallback(callable $callback): self {
		$this->_fieldProcessorCallback = $callback;
		return $this;
	}
	
	/**
	 * Returns the field value after processing it (if configured to)
	 * @param array $fieldConfig
	 * @param string $rawValue
	 * @param array $extraInfo Extra informations that can be sent to the callback function
	 * @return mixed
	 */
	protected function getProcessedFieldValue(array $fieldConfig, string $rawValue, array $extraInfo = []) {
		if ($this->_fieldProcessorCallback) {
			return call_user_func_array($this->_fieldProcessorCallback, [$fieldConfig, $rawValue, $this, $extraInfo]);
		}
		return $rawValue;
	}
	
	/**
	 * @param string $lineId
	 * @throws FixedLengthFileException
	 */
	protected function throwLineDoesntExistsException(string $lineId): void {
		throw new FixedLengthFileException(
			'Line "'.$lineId.'" does not exists',
			FixedLengthFileException::LINE_DOESNT_EXISTS
		);
	}
	
	/**
	 * @param string $lineId
	 * @throws FixedLengthFileException
	 */
	protected function throwLineAlreadyExistsException(string $lineId): void {
		throw new FixedLengthFileException(
			'Line "'.$lineId.'" already exists',
			FixedLengthFileException::LINE_ALREADY_EXISTS
		);
	}
	
	/**
	 * @param string $lineId
	 * @param string $fieldId
	 * @throws FixedLengthFileException
	 */
	protected function throwFieldDoesntExistsException(string $lineId, string $fieldId): void {
		throw new FixedLengthFileException(
			'Field "'.$fieldId.'" does not exists in line "'.$lineId.'"',
			FixedLengthFileException::FIELD_DOESNT_EXISTS
		);
	}
	
	/**
	 * @param string $lineId
	 * @throws FixedLengthFileException
	 */
	protected function throwInvalidFieldConfigException(string $lineId): void {
		throw new FixedLengthFileException(
			'Invalid field configuration in line "'.$lineId.'". Check the required information.',
			FixedLengthFileException::INVALID_FIELD_CONFIGURATION
		);
	}
	
	/**
	 * @param string $lineId
	 * @param string $fieldId
	 * @throws FixedLengthFileException
	 */
	protected function throwFieldAlreadyExistsException(string $lineId, string $fieldId): void {
		throw new FixedLengthFileException(
			'Field "'.$fieldId.'" already exists in line "'.$lineId.'"',
			FixedLengthFileException::FIELD_ALREADY_EXISTS
		);
	}
}
