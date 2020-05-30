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
    2Tony Farney    030
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
        FixedLengthFileReader $reader, // The reader itself
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

    array(7) {
      [0]=>
      array(3) {
        ["lineId"]=>
        string(1) "1"
        ["rawLine"]=>
        string(16) "1Developer      "
        ["fields"]=>
        array(2) {
          ["recordType"]=>
          string(1) "1"
          ["role"]=>
          string(9) "Developer"
        }
      }
      [1]=>
      array(3) {
        ["lineId"]=>
        string(1) "2"
        ["rawLine"]=>
        string(20) "2Tonya Farney    030"
        ["fields"]=>
        array(3) {
          ["recordType"]=>
          string(1) "2"
          ["name"]=>
          string(12) "Tonya Farney"
          ["age"]=>
          int(3)
        }
      }
      [2]=>
      array(3) {
        ["lineId"]=>
        string(1) "2"
        ["rawLine"]=>
        string(19) "2Someone Else   026"
        ["fields"]=>
        array(3) {
          ["recordType"]=>
          string(1) "2"
          ["name"]=>
          string(12) "Someone Else"
          ["age"]=>
          int(26)
        }
      }
      [3]=>
      array(3) {
        ["lineId"]=>
        string(1) "3"
        ["rawLine"]=>
        string(4) "3002"
        ["fields"]=>
        array(2) {
          ["recordType"]=>
          string(1) "3"
          ["totalRole"]=>
          int(2)
        }
      }
      [4]=>
      array(3) {
        ["lineId"]=>
        string(1) "1"
        ["rawLine"]=>
        string(16) "1Intern         "
        ["fields"]=>
        array(2) {
          ["recordType"]=>
          string(1) "1"
          ["role"]=>
          string(6) "Intern"
        }
      }
      [5]=>
      array(3) {
        ["lineId"]=>
        string(1) "2"
        ["rawLine"]=>
        string(19) "2The Coffe Guy  018"
        ["fields"]=>
        array(3) {
          ["recordType"]=>
          string(1) "2"
          ["name"]=>
          string(13) "The Coffe Guy"
          ["age"]=>
          int(18)
        }
      }
      [6]=>
      array(3) {
        ["lineId"]=>
        string(1) "3"
        ["rawLine"]=>
        string(4) "3001"
        ["fields"]=>
        array(2) {
          ["recordType"]=>
          string(1) "3"
          ["totalRole"]=>
          int(1)
        }
      }
    }

## Writing Fixed Length File

    <?php
    require __DIR__.'/vendor/autoload.php';
        
    use \FixedLengthFileHandler\FixedLengthFileWriter;
        
    $roles = [
        'Developer' => [
            ['name' => 'Tony Farney', 'age' => 30],
            ['name' => 'Someone Else', 'age' => 26],
        ],
        'Intern' => [
            ['name' => 'The Coffe Guy', 'age' => 18],
        ]
    ];
    
    // Lines configuration. The index is the line ID and the value an array with the fields info
    // Obs: The "id" and "size" are mandatory informations but you can put any information you want
    $linesConfig = [
        '1' => [['id' => 'recordType', 'size' => 1], ['id' => 'role', 'size' => 15]],
        '2' => [['id' => 'recordType', 'size' => 1], ['id' => 'name', 'size' => 15], ['id' => 'age', 'size' => 3, 'int' => true]],
        '3' => [['id' => 'recordType', 'size' => 1], ['id' => 'totalRole', 'size' => 3, 'int' => true]],
    ];
        
    // Responsible for process each field value provided and return the processed value
    function processFieldCallback(
        array $fieldConfig, // The field configuration
        $rawValue, // The field value provided
        FixedLengthFileWriter $writer, // The writer itself,
        array $extraInfo // Any extra information the writer pass to the callback
    ) {
        // Is a numeric field
        $isNumeric = $fieldConfig['int'] ?? false;
        $newValue = str_pad(
            $rawValue,
            $fieldConfig['size'],
            $isNumeric ? '0' : ' ',
            $isNumeric ? STR_PAD_LEFT : STR_PAD_RIGHT
        );
        return substr($newValue, 0, $fieldConfig['size']);
    }
        
    $writer = new FixedLengthFileWriter();
    $writer->setLines($linesConfig)
        ->setLineDelimiter("\n")
        ->setFieldProcessorCallback('processFieldCallback')
    ;
    
    // Generate lines
    foreach ($roles as $role => $employees) {
        $writer->generateLine('1', ['recordType' => '1', 'role' => $role]);
        foreach ($employees as $employee) {
            $writer->generateLine(
                '2',
                [
                    'recordType' => '2',
                    'name' => $employee['name'],
                    'age' => $employee['age']
                ]
            );
        }
        $writer->generateLine('3', ['recordType' => '3', 'totalRole' => count($employees)]);
    }
    echo $writer->getGeneratedFileContent();
    
echo $writer->getGeneratedFileContent(); output:

    1Developer      
    2Tony Farney    030
    2Someone Else   026
    3002
    1Intern         
    2The Coffe Guy  018
    3001

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

