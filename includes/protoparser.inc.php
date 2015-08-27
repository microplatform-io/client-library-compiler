<?PHP
	
class ProtoParser {
	private $variableManager = array();
	private $variableManagerLowercase = array();
	private $currentLineNumber = 0;
	private $stack = array();
	private $parseStack = array();
	
	/**
	*
	*/
	public function __construct() {
	}

	/**
	*
	*/
	public function parse($protobufs) {
		$this->currentLineNumber = 0;
		$this->parseStack = array();
		$this->stack = array();
		$this->variableManager = array();
		$this->variableManagerLowercase = array();
		$this->beginParsing($protobufs);
		usort($this->stack, array('ProtoParser','compareName'));
		foreach($this->stack as $obj) {
			usort($obj->fields, array('ProtoParser','compareNumber'));
		}
		$this->validateStack();
		return $this->stack;
	}
	
	/**
	*
	*/
	public function prettyPrint($protobufs, $indentation = "   ") {
		$parsed = $this->parse($protobufs);
		$output = "";
		foreach($parsed as $obj) {
			if ($obj instanceof ProtoMessage) {
				$output .= $this->prettyPrintMessage($obj, $indentation, 0);
			} else if ($obj instanceof ProtoENUM) {
				$output .= $this->prettyPrintENUM($obj, $indentation, 0);
			}
		} 
		return $output;
	}
	
	/**
	*
	*/
	private function validateStack() {
		foreach ($this->stack as $obj) {
			if ($obj instanceof ProtoMessage) {
				foreach($obj->fields as $fieldObj) {
					$this->validateField($obj->name, $fieldObj);
				}
			} else if ($obj instanceof ProtoOneOf) {
				foreach($obj->fields as $fieldObj) {
					$this->validateField($obj->name, $fieldObj);
				}
			}
		}
	}
	
	/**
	*
	*/
	private function validateField($nesting, $obj) {
		switch ($obj->type) {
			case "double":
			case "float":
			case "int32":
			case "int64":
			case "uint32":
			case "uint64":
			case "sint32":
			case "sint64":
			case "fixed32":
			case "fixed64":
			case "sfixed32":
			case "sfixed64":
			case "bool":
			case "string":
			case "bytes":
				return;
			
		}
		
		if (in_array($obj->type, $this->variableManager)) {
		} else if (in_array($nesting.".".$obj->type, $this->variableManager)) {
		} else {
			$this->currentLineNumber = $obj->line;
			$this->raiseException("unable to locate an object of type '".$obj->type."'.");
		}
	}
	
	
	/**
	*
	*/
	private function prettyPrintMessage($obj, $indentation, $level) {
		$padding = str_pad("", ($level * strlen($indentation)), $indentation);
		$output = $padding."message ".$obj->name." {\n";
		foreach ($obj->messages as $messageObj) {
			$output .= $this->prettyPrintMessage($messageObj, $indentation, ($level + 1));
		}
		foreach ($obj->enums as $enumObj) {
			$output .= $this->prettyPrintENUM($enumObj, $indentation, ($level + 1));
		}
		$longestField = 0;
		foreach ($obj->fields as $fieldObj) {
			$ruleTypeName = $fieldObj->rule." ".$fieldObj->type." ".$fieldObj->name;
			if (strlen($ruleTypeName) > $longestField) $longestField = strlen($ruleTypeName);
		}
		foreach ($obj->fields as $fieldObj) {
			$output .= $this->prettyPrintMessageField($fieldObj, $indentation, ($level + 1), $longestField);
		}
		
		foreach ($obj->oneofs as $oneofsObj) {
			$output .= $this->prettyPrintOneOfs($oneofsObj, $indentation, ($level + 1));
		}
		
		$output .= $padding."}\n\n";
		return $output;
	}
	
	/**
	*
	*/
	private function prettyPrintENUM($obj, $indentation, $level) {
		$padding = str_pad("", ($level * strlen($indentation)), $indentation);
		$output = $padding."enum ".$obj->name." {\n";
		
		$longestField = 0;
		foreach ($obj->fields as $fieldObj) {
			if (strlen($fieldObj->name) > $longestField) $longestField = strlen($fieldObj->name);
		}
		foreach ($obj->fields as $fieldObj) {
			$output .= $this->prettyPrintENUMField($fieldObj, $indentation, ($level + 1), $longestField);
		}
		
		$output .= $padding."}\n\n";
		return $output;
	}
	
	/**
	*
	*/
	private function prettyPrintOneOfs($obj, $indentation, $level) {
		$padding = str_pad("", ($level * strlen($indentation)), $indentation);
		$output = $padding."oneof ".$obj->name." {\n";
		$longestField = 0;
		foreach ($obj->fields as $fieldObj) {
			$typeName = $fieldObj->type." ".$fieldObj->name;
			if (strlen($typeName) > $longestField) $longestField = strlen($typeName);
		}
		foreach($obj->fields as $fieldObj) {
			$output .= $this->prettyPrintOneOfField($fieldObj, $indentation, ($level + 1), $longestField);
		}
		$output .= $padding."}\n";
		return $output;
	}
	
	/**
	*
	*/
	private function prettyPrintOneOfField($obj, $indentation, $level, $longestField) {
		$padding = str_pad("", ($level * strlen($indentation)), $indentation);
		$typeName = $obj->type." ".$obj->name;
		$spacing = str_pad("", $longestField - strlen($typeName)+1, " ");
		$output = $padding.$typeName.$spacing."= ".$obj->number.";\n";
		return $output;
	}
	
	/**
	*
	*/
	private function prettyPrintMessageField($obj, $indentation, $level, $longestField) {
		$padding = str_pad("", ($level * strlen($indentation)), $indentation);
		$ruleTypeName = $obj->rule." ".$obj->type." ".$obj->name;
		$spacing = str_pad("", $longestField - strlen($ruleTypeName)+1, " ");
		$output = $padding.$ruleTypeName.$spacing."= ".$obj->number.";\n";
		return $output;
	}
	
	/**
	*
	*/
	private function prettyPrintENUMField($obj, $indentation, $level, $longestField) {
		$padding = str_pad("", ($level * strlen($indentation)), $indentation);
		$spacing = str_pad("", $longestField - strlen($obj->name)+1, " ");
		$output = $padding.$obj->name.$spacing."= ".$obj->number.";\n";
		return $output;
	}
	
	/**
	*
	*/
	private function beginParsing($protobufs) {
		// Loop through the protobufs line by line
		$this->currentLineNumber = 0;
		$lines = explode("\n", $protobufs);
		for ($x = 0; $x < sizeof($lines); $x++) {
			$this->currentLineNumber++;
			$this->currentLine = $lines[$x];
			$this->processLine($this->currentLine);
		}
	}
	
	/**
	*
	*/
	private function processLine($line) {
		// Skip the line if its empty
		$line = trim($line);
		if (strlen($line) === 0) return;
		
		// Parse the line
		if (substr($line, 0, 2) == "//") { // Comment
			return;
		} else if (substr($line, 0, 1) == "}") {
			$this->processEndOfObject();
		} else if (substr(strtolower($line), 0, 6) == "import") {
			return;
		} else if (substr(strtolower($line), 0, 7) == "package") {
			return;
		} else if (substr(strtolower($line), 0, 7) == "message") {
			$this->parseMessage($line);
		} else if (substr(strtolower($line), 0, 4) == "enum") {
			$this->parseENUM($line);
		} else if (substr(strtolower($line), 0, 5) == "oneof") {
			$this->parseOneOf($line);
		} else {
			$this->parseField($line);
		}
	}
	
	/**
	*
	*/
	private function parseMessage($line) {
		$parts = preg_split('/\s+/', $line);
		$name = $parts[1];
		if (sizeof($parts) < 3) {
			$this->raiseException("line is improperly formatted.");
		} else if (substr($parts[2], 0, 1) != "{") {
			$this->raiseException("expecting a '{' after the message name.");
		} else {
			$cleanName = preg_replace("/[^A-Za-z]/", '', $name);
			if ($cleanName != $name) {
				$this->raiseException("message name '".$name."' contains illegal characters.");
			} else if (strlen($name) == 0) {
				$this->raiseException("message name could not be found.");
			}
			$chr = mb_substr ($name, 0, 1, "UTF-8");
			if (mb_strtolower($chr, "UTF-8") == $chr) {
				$this->raiseException("message name '".$name."' should begin with an uppercase letter.");
			}
		}
		$this->processMessage($name);
	}
	
	/**
	*
	*/
	private function parseENUM($line) {
		$parts = preg_split('/\s+/', $line);
		$name = $parts[1];
		if (sizeof($parts) < 3) {
			$this->raiseException("line is improperly formatted.");
		} else if (substr($parts[2], 0, 1) != "{") {
			$this->raiseException("expecting a '{' after the message name.");
		} else {
			$cleanName = preg_replace("/[^A-Za-z]/", '', $name);
			if ($cleanName != $name) {
				$this->raiseException("message name '".$name."' contains illegal characters. Acceptable characters are A-Z and a-z.");
			} else if (strlen($name) == 0) {
				$this->raiseException("message name could not be found.");
			}
			$chr = mb_substr ($name, 0, 1, "UTF-8");
			if (mb_strtolower($chr, "UTF-8") == $chr) {
				$this->raiseException("message name '".$name."' should begin with an uppercase letter.");
			}
		}
		$this->processENUM($name);
	}
	
	/**
	*
	*/
	private function parseOneOf($line) {
		$parts = preg_split('/\s+/', $line);
		$name = $parts[1];
		if (sizeof($parts) < 3) {
			$this->raiseException("line is improperly formatted.");
		} else if (substr($parts[2], 0, 1) != "{") {
			$this->raiseException("expecting a '{' after the oneof name.");
		} else {
			$cleanName = preg_replace("/[^A-Za-z_]/", '', $name);
			if ($cleanName != $name) {
				$this->raiseException("oneof name '".$name."' contains illegal characters. Acceptable characters are A-Z, a-z, and underscore.");
			} else if (strlen($name) == 0) {
				$this->raiseException("oneof name could not be found.");
			}
		}
		$this->processOneOf($name);
	}
	
	/**
	*
	*/
	private function parseField($line) {
		if (sizeof($this->parseStack) === 0) {
			$this->raiseException("unable to parse field because field has no parent.");
		}
		
		$parentObj = end($this->parseStack);
		if ($parentObj instanceof ProtoMessage) {
			$this->parseMessageField($line);
		} else if ($parentObj instanceof ProtoENUM) {
			$this->parseENUMField($line);
		} else if ($parentObj instanceof ProtoOneOf) {
			$this->parseOneOfField($line);
		} else {
			$this->raiseException("internal error, failed to parse field because parent object type is unknown");
		}
	}
	
	/**
	*
	*/
	private function parseMessageField($line) {
		$parts = preg_split('/\s+/', $line);
		
		$obj = new ProtoField($this->currentLineNumber);
		$rule = strtolower($parts[0]);
		switch ($rule) {
			case "optional":
			case "repeated":
			case "required":
			$obj->rule = $rule;
				break;
				
			default:
				$this->raiseException("field has an unrecognized rule. Valid rules are 'optional', 'repeated', or 'required'");
		}
		
		$type = $parts[1];
		if (!$type || strlen($type) == 0) {
			$this->raiseException("field is missing a type");
		} else {
			$cleanType = preg_replace("/[^A-Za-z0-9.]/", '', $type);
			if ($type != $cleanType) {
				$this->raiseException("field type '".$type."' contains illegal characters. Acceptable characters are A-Z, a-z, 0-9, and period.");
			}
			$obj->type = $type;
		}
		
		$name = $parts[2];
		if (!$name || strlen($name) == 0) {
			$this->raiseException("field is missing a name");
		} else if ($name != strtolower($name)) {
			$this->raiseException("field name '".$name."' should be all lowercase.");
		} else {
			$cleanName = preg_replace("/[^A-Za-z0-9_]/", '', $name);
			if ($name != $cleanName) {
				$this->raiseException("field name '".$name."' contains illegal characters. Acceptable characters are A-Z, a-z, 0-9, and underscore.");
			}
			$obj->name = $name;
		}
		
		if ($parts[3] != "=") {
			$this->raiseException("expecting an '=' but encountered '".$parts[3]."' instead");
		}
		
		$number = $parts[4];
		$number = rtrim($number, ";");
		$cleanNumber = preg_replace("/[^0-9_]/", '', $number);
		if ($number != $cleanNumber) {
			$this->raiseException("expecting a field number but encountered '".$number."' instead");
		}
		$obj->number = intval($number);
		
		$parentObj = end($this->parseStack);
		foreach ($parentObj->fields as $field) {
			if ($field->number == $obj->number) {
				$this->raiseException("field number '".$obj->number."' is already being used by the '".$field->name."' field.");
			}
		}
		
		array_push($parentObj->fields, $obj);
	}
	
	/**
	*
	*/
	private function parseENUMField($line) {
		$parts = preg_split('/\s+/', $line);
		
		$obj = new ProtoField($this->currentLineNumber);
		$name = $parts[0];
		if (!$name || strlen($name) == 0) {
			$this->raiseException("field is missing a name");
		} else if ($name != strtoupper($name)) {
			$this->raiseException("field name '".$name."' should be all uppercase.");
		} else {
			$cleanName = preg_replace("/[^A-Za-z0-9_]/", '', $name);
			if ($name != $cleanName) {
				$this->raiseException("field name '".$name."' contains illegal characters. Acceptable characters are A-Z, a-z, 0-9, and underscore.");
			}
			$obj->name = $name;
		}
		
		if ($parts[1] != "=") {
			$this->raiseException("expecting an '=' but encountered '".$parts[1]."' instead");
		}
		
		$number = $parts[2];
		$number = rtrim($number, ";");
		$cleanNumber = preg_replace("/[^0-9_]/", '', $number);
		if ($number != $cleanNumber) {
			$this->raiseException("expecting a field number but encountered '".$number."' instead");
		}
		$obj->number = intval($number);
		
		$parentObj = end($this->parseStack);
		foreach ($parentObj->fields as $field) {
			if ($field->number == $obj->number) {
				$this->raiseException("field number '".$obj->number."' is already being used by the '".$field->name."' field.");
			}
		}
	
		array_push($parentObj->fields, $obj);
	}
	
	/**
	*
	*/
	private function parseOneOfField($line) {
		$parts = preg_split('/\s+/', $line);
		
		$obj = new ProtoField($this->currentLineNumber);
		
		$type = $parts[0];
		if (!$type || strlen($type) == 0) {
			$this->raiseException("field is missing a type");
		} else {
			$cleanType = preg_replace("/[^A-Za-z0-9.]/", '', $type);
			if ($type != $cleanType) {
				$this->raiseException("field type '".$type."' contains illegal characters. Acceptable characters are A-Z, a-z, 0-9, and period.");
			}
			$obj->type = $type;
		}
		
		$name = $parts[1];
		if (!$name || strlen($name) == 0) {
			$this->raiseException("field is missing a name");
		} else if ($name != strtolower($name)) {
			$this->raiseException("field name '".$name."' should be all lowercase.");
		} else {
			$cleanName = preg_replace("/[^A-Za-z0-9_]/", '', $name);
			if ($name != $cleanName) {
				$this->raiseException("field name '".$name."' contains illegal characters. Acceptable characters are A-Z, a-z, 0-9, and underscore.");
			}
			$obj->name = $name;
		}
		
		if ($parts[2] != "=") {
			$this->raiseException("expecting an '=' but encountered '".$parts[1]."' instead");
		}
		
		$number = $parts[3];
		$number = rtrim($number, ";");
		$cleanNumber = preg_replace("/[^0-9_]/", '', $number);
		if ($number != $cleanNumber) {
			$this->raiseException("expecting a field number but encountered '".$number."' instead");
		}
		$obj->number = intval($number);
		
		$parentObj = end($this->parseStack);
		foreach ($parentObj->fields as $field) {
			if ($field->number == $obj->number) {
				$this->raiseException("field number '".$obj->number."' is already being used by the '".$field->name."' field.");
			}
		}
	
		array_push($parentObj->fields, $obj);
	}
	
	/**
	*
	*/
	private function compareName($a, $b) {
    	return strcmp($a->name, $b->name);
	}
	
	/**
	*
	*/
	private function compareNumber($a, $b) {
    	return $a->number > $b->number;
	}
	
	/**
	*
	*/
	private function implodeStack() {
		$value = "";
		foreach ($this->parseStack as $obj) {
			$value .= $obj->name.".";
		}
		return trim($value, ".");
	}
	
	/**
	*
	*/
	private function processEndOfObject() {
		if (sizeof($this->parseStack) == 0) {
			$this->raiseException("unexpected '}'.");
		}
		
		$value = $this->implodeStack();
		if (in_array(strtolower($value), $this->variableManagerLowercase)) {
			$this->raiseException("Object '".$value."' declared twice.");
		}
		array_push($this->variableManagerLowercase, strtolower($value));
		array_push($this->variableManager, $value);
		$obj = array_pop($this->parseStack);
		if (sizeof($this->parseStack) == 0) {
			array_push($this->stack, $obj);
		} else {
			$parentObj = end($this->parseStack);
			if ($obj instanceof ProtoMessage) {
				array_push($parentObj->messages, $obj);
				usort($parentObj->messages, array('ProtoParser','compareName'));
				foreach($parentObj->messages as $messageObj) {
					usort($messageObj->fields, array('ProtoParser','compareNumber'));
				}
			} else if ($obj instanceof ProtoENUM) {
				array_push($parentObj->enums, $obj);
				usort($parentObj->enums, array('ProtoParser','compareName'));
				foreach($parentObj->enums as $enumObj) {
					usort($enumObj->fields, array('ProtoParser','compareNumber'));
				}
			} else if ($obj instanceof ProtoOneOf) {
				array_push($parentObj->oneofs, $obj);
				usort($parentObj->oneofs, array('ProtoParser','compareName'));
				foreach($parentObj->oneofs as $oneofObj) {
					usort($oneofObj->fields, array('ProtoParser','compareNumber'));
				}
			} else {
				$this->raiseException("internal error, failed to update stack because object type is unknown");
			}
		}
	}
	
	/**
	*
	*/
	private function processMessage($name) {
		$message = new ProtoMessage($this->currentLineNumber);
		$message->name = $name;
		array_push($this->parseStack, $message);
	}
	
	/**
	*
	*/
	private function processENUM($name) {
		$enum = new ProtoENUM($this->currentLineNumber);
		$enum->name = $name;
		array_push($this->parseStack, $enum);
	}
	
	/**
	*
	*/
	private function processOneOf($name) {
		$oneOf = new ProtoOneOf($this->currentLineNumber);
		$oneOf->name = $name;
		array_push($this->parseStack, $oneOf);
	}
	
	/**
	*
	*/
	private function raiseException($message) {
		throw new Exception("Line ".$this->currentLineNumber.", ".$message);
	}
	
}

class ProtoMessage {
	public $line;
	public $name;
	public $messages = array();
	public $enums = array();
	public $oneofs = array();
	public $fields = array();
	
	public function __construct($line) {
		$this->line = $line;
	}
}

class ProtoENUM {
	public $line;
	public $name;
	public $fields = array();
	
	public function __construct($line) {
		$this->line = $line;
	}
}

class ProtoOneOf {
	public $line;
	public $name;
	public $fields = array();
	
	public function __construct($line) {
		$this->line = $line;
	}
}

class ProtoField {
	public $line;
	public $rule;
	public $type;
	public $name;
	public $number;
	
	public function __construct($line) {
		$this->line = $line;
	}
}

?>