# FixedLengthFileHandler
Ease to use fixed length (positional) file Reader and Writer for PHP 7.1+

## Instalation
In your project root, run the following composer command:

    $ composer require tonyfarney/fixed-length-file-handler

## Reading Fixed Length File

    <?php
    require __DIR__.'/vendor/autoload.php';
        
    use \FixedLengthFileHandler\FixedLengthFileReader;
        
    $fileContent = <<<FILE
    1Developer      
    2TONY Farney    030
    2Someone Else   026
    3002
    1Intern         
    2The Coffe Guy  018
    3001
    FILE;
        
    // Lines configuration. The index is the line ID and the value an array with the fields info
    // Obs: The "id" and "size" are mandatory informations but you can put any information you want
    $linesConfig = [
        '1' => [['id' => 'recordType', 'size' => 1], ['id' => 'role', 'size' => 15]],
        '2' => [['id' => 'recordType', 'size' => 1], ['id' => 'name', 'size' => 15], ['id' => 'age', 'size' => 3, 'int' => true]],
        '3' => [['id' => 'recordType', 'size' => 1], ['id' => 'totalRole', 'size' => 3, 'int' => true]],
     ];
         
    // Responsible for line ID detection
    function lineDetectCallback(
        string $line, // The whole line contents read
        FixedLenghtFileReader $reader, // The reader itself
        array $extraInfo // Any extra information the reader pass to the callback
    ): ?string { // If not detected, it should return null. An error will be thrown
        return substr($line, 0, 1); // The line ID comes is at the first line char
    }
        
    // Responsible for process each raw field value read and return the processed value
    function processFieldCallback(
        array $fieldConfig, // The field configuration
        $rawValue, // The raw value read from file
        FixedLengthFileReader $reader, // The reader itself,
        array $extraInfo // Any extra information the reader pass to the callback
     ) {
      // Is a numeric field
      if ($fieldConfig['int'] ?? false) {
          return intval($rawValue);
      }
      return trim($rawValue);
    }
        
    $reader = new FixedLengthFileReader();
    $lines = $reader->setLines($linesConfig)
         ->setFieldProcessorCallback('processFieldCallback')
         ->setLineDetectionCallback('lineDetectCallback')
         ->loadFile($fileContent)
    ;
    var_dump($lines);
    

var_dump($lines); output:

    "name","role","age"
    "Tony Farney","Developer","30"
    "The Coffe Guy","Intern","18"
    "Someone Else","Developer","26"

## Tips for both Reader and Writer

It's possible add/get/remove/replace/check line e field configurations individualy:

    // Adds the line '1' configuration
    $rw->addLine('1', [['id' => 'recordType', 'size' => 1], ['id' => 'role', 'size' => 15]]);
        
    // Removes the line '1' configuration
    $rw->removeLine('1');
        
    // Adds the field 'role' configuration to line '1' and inserts it right after the last configured field
    $rw->addField('1', ['id' => 'role', 'size' => 15]);
    
    // Adds the field 'recordType' configuration to line '1' and inserts it right before the field 'role'
    $rw->addField('1', ['id' => 'recordType', 'size' => 1], 'role');
        
    // Removes the field 'role' configuration from line '1'
    $rw->removeField('1', 'role');
        
    // Gets the line '1' configuration
    $lineConfig = $rw->getLine('1');
        
    // Gets the field 'role' configuration from line '1'
    $lineConfig = $rw->getField('1', 'role');
    // Clears the buffer 
    $rw->clearBuffer();
        
    // Checks if the line with id '1' is configured
    $rw->issetLine('1');

It's possible reset all the configurations and lines read/generated:

    $rw->reset();

## Contributions/Support
You are welcome to contribute with improvement, bug fixes, new ideas, etc. Any doubt/problem, please contact me by email: tonyfarney@gmail.com. I'll be glad to help you ;)

