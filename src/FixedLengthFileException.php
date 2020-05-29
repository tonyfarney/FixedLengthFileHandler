<?php
namespace FixedLengthFileHandler;

class FixedLengthFileException extends \ErrorException {
	// Constants for every possible error to be thrown by library
	public const LINE_ALREADY_EXISTS = 1;
	public const LINE_DOESNT_EXISTS = 2;
	public const FIELD_ALREADY_EXISTS = 3;
	public const FIELD_DOESNT_EXISTS = 4;
	public const INVALID_FIELD_CONFIGURATION = 5;
	public const REQUIRED_CALLBACK_NOT_SET = 6;
	public const LINE_NOT_DETECTED_EXCEPTION = 7;
	
	/**
	 * @var array
	 */
	private $_details;
	
	public function __construct(string $message, int $code = null, array $details = null) {
		parent::__construct($message, $code);
		$this->_details = $details;
	}
	
	/**
	 * Details about the error
	 * @return array|null
	 */
	public function getDetails(): ?array {
		return $this->_details;
	}
}