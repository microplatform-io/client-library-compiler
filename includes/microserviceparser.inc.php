<?PHP
	
class MicroServiceParser {
	private $platformProtobufs = array();
	private $clientProtobufs = array();
	private $microServices = array();
	
	/**
	*  $microServices is JSON that describes all the micro services
	*/
	public function parse($microServices) {
		$microServices = json_decode($microServices, true);
		foreach($microServices as $microService) {
			$microServiceObj = new MicroService();
			$microServiceObj->description = $microService["description"];
			$requestObj = new MicroServiceRequest();
			$requestObj->method = $microService["request"]["method"];
			$requestObj->resource = $microService["request"]["resource"];
			$requestObj->payload = $microService["request"]["payload"];
			if ($requestObj->payload && strlen($requestObj->payload) === 0) $requestObj->payload = null;
			$microServiceObj->request = $requestObj;
			foreach ($microService["responses"] as $response) {
				$responseObj = new MicroServiceRequest();
				$responseObj->method = $response["method"];
				$responseObj->resource = $response["resource"];
				$responseObj->payload = $response["payload"];
				array_push($microServiceObj->responses, $responseObj);
			}
			usort($microServiceObj->responses, array('MicroServiceParser','compareRequest'));
			$this->validate($microServiceObj);
			array_push($this->microServices, $microServiceObj);
		}
		usort($this->microServices, array('MicroServiceParser','compareDescription'));
		return $this->microServices;
	}
	
	/**
	*  $protobufs is an array of ProtoMessage and ProtoENUM objects which you
	*  can find at the bottom includes/protoparser.inc.php
	*/
	public function setPlatformProtobufs($protobufs) {
		$this->platformProtobufs = $protobufs;
	}
	
	/**
	*  $protobufs is an array of ProtoMessage and ProtoENUM objects which you
	*  can find at the bottom includes/protoparser.inc.php
	*/
	public function setClientProtobufs($protobufs) {
		$this->clientProtobufs = $protobufs;
	}
	
	/**
	*
	*/
	private function compareDescription($a, $b) {
    	return strcmp($a->description, $b->description);
	}
	
	/**
	*
	*/
	private function compareRequest($a, $b) {
		if ($a->method == $b->method) {
			return strcmp($a->resource, $b->resource);
		} else {
			return strcmp($a->method, $b->method);	
		}
	}
	
	/**
	*
	*/
	private function raiseException($message) {
		throw new Exception($message);
	}
	
	/**
	*
	*/
	private function parseVariableName($variable) {
		$values = explode(".", $variable);
		return end($values);
	}
	
	/**
	*  Make sure the microservice has valid properties
	*/
	private function validate($microService) {
		// Verify we have a description
		if (!$microService->description || strlen($microService->description) === 0) {
			$this->raiseException("Micro service ".$this->parseVariableName($microService->request->method)." ".$this->parseVariableName($microService->request->resource)." is missing a description.");
		}
		
		// Verify the micro service has a method
		if (!$microService->request->method || strlen($microService->request->method) === 0) {
			$this->raiseException("Micro service '".$microService->description."' is missing a method.");
		} else {
			$this->validateProtobufEnum($microService->request->method);
		}
		
		// Verify the micro service has a resource
		if (!$microService->request->resource || strlen($microService->request->resource) === 0) {
			$this->raiseException("Micro service '".$microService->description."' is missing a resource.");
		} else {
			$this->validateProtobufEnum($microService->request->resource);
		}
		
		// Validate the optional payload
		if ($microService->request->payload && strlen($microService->request->payload) > 0) {
			$this->validateProtobufObject($microService->request->payload);
		}
		
		// Verify the micro service responses
		foreach ($microService->responses as $response) {
			// Verify the response has a method
			if (!$response->method || strlen($response->method) === 0) {
				$this->raiseException("Micro service '".$microService->description."' is missing a method for one of its responses.");
			} else {
				$this->validateProtobufEnum($response->method);
			}
			
			// Verify the response has a resource
			if (!$response->resource || strlen($response->resource) === 0) {
				$this->raiseException("Micro service '".$microService->description."' is missing a resource for one of its responses.");
			} else {
				$this->validateProtobufEnum($response->resource);
			}
			
			// Validate the optional payload
			if ($response->payload && strlen($response->payload) > 0) {
				$this->validateProtobufObject($response->payload);
			}
		}
	}
	
	/**
	*
	*/
	private function validateProtobufEnum($variable) {
		if (substr($variable, 0, 7) == "client.") {
			$this->validateClientProtobufEnum(substr($variable, 7), $this->clientProtobufs, $variable);
		} else if (substr($variable, 0, 9) == "platform.") {
			$this->validatePlatformProtobufEnum(substr($variable, 9), $this->platformProtobufs, $variable);
		} else {
			$this->raiseException("'".$variable."' is not a valid protobuf enum. Enums must begin with 'client.' or 'platform.'");
		}
	}
	
	/**
	*
	*/
	private function validateProtobufObject($variable) {
		if (substr($variable, 0, 7) == "client.") {
			$this->validateClientProtobufObject(substr($variable, 7), $this->clientProtobufs, $variable);
		} else if (substr($variable, 0, 9) == "platform.") {
			$this->validatePlatformProtobufObject(substr($variable, 9), $this->platformProtobufs, $variable);
		} else {
			$this->raiseException("'".$variable."' is not a valid protobuf object. Objects must begin with 'client.' or 'platform.'");
		}
	}
	
	/**
	*
	*/
	private function validatePlatformProtobufEnum($variable, $objects, $fullVariable) {
		$values = explode(".", $variable);
		for ($x = 0; $x < sizeof($values); $x++) {
			$value = $values[$x];
			$found = false;
			if ($x == sizeof($values) - 2) {
				foreach($objects as $object) {
					if ($object instanceof ProtoENUM) {
						if ($object->name == $value) {
							$found = true;
							$objects = $object->fields;
							break;
						}
					}
				}
			} else if ($x == sizeof($values) - 1) {
				foreach($objects as $object) {
					if ($object instanceof ProtoField) {
						if ($object->name == $value) {
							$found = true;
							break;
						}
					}
				}
			} else {
				foreach($objects as $object) {
					if ($object instanceof ProtoMessage) {
						if ($object->name == $value) {
							$found = true;
							if ($x < sizeof($values) - 3) {
								$objects = $object->messages;
							} else {
								$objects = $object->enums;
							}
							break;
						}
					}
				}
			}
				
			if (!$found) {
				$this->raiseException("Unable to locate the'".$fullVariable."' enum in the client protobufs");
			}
		}
	}
	
	/**
	*
	*/
	private function validateClientProtobufEnum($variable, $objects, $fullVariable) {
		$values = explode(".", $variable);
		for ($x = 0; $x < sizeof($values); $x++) {
			$value = $values[$x];
			$found = false;
			if ($x == sizeof($values) - 2) {
				foreach($objects as $object) {
					if ($object instanceof ProtoENUM) {
						if ($object->name == $value) {
							$found = true;
							$objects = $object->fields;
							break;
						}
					}
				}
			} else if ($x == sizeof($values) - 1) {
				foreach($objects as $object) {
					if ($object instanceof ProtoField) {
						if ($object->name == $value) {
							$found = true;
							break;
						}
					}
				}
			} else {
				foreach($objects as $object) {
					if ($object instanceof ProtoMessage) {
						if ($object->name == $value) {
							$found = true;
							if ($x < sizeof($values) - 3) {
								$objects = $object->messages;
							} else {
								$objects = $object->enums;
							}
							break;
						}
					}
				}
			}
				
			if (!$found) {
				$this->raiseException("Unable to locate the '".$fullVariable."' enum in the client protobufs");
			}
		}
	}
	
	/**
	*
	*/
	private function validatePlatformProtobufObject($variable, $objects, $fullVariable) {
		$values = explode(".", $variable);
		for ($x = 0; $x < sizeof($values); $x++) {
			$value = $values[$x];
			$found = false;
			
			foreach($objects as $object) {
				if ($object instanceof ProtoMessage) {
					if ($object->name == $value) {
						$found = true;
						$objects = $object->messages;
						break;
					}
				}
			}
				
			if (!$found) {
				$this->raiseException("Unable to locate the '".$fullVariable."' object in the platform protobufs");
			}
		}
	}
	
	/**
	*
	*/
	private function validateClientProtobufObject($variable, $objects, $fullVariable) {
		$values = explode(".", $variable);
		for ($x = 0; $x < sizeof($values); $x++) {
			$value = $values[$x];
			$found = false;
			
			foreach($objects as $object) {
				if ($object instanceof ProtoMessage) {
					if ($object->name == $value) {
						$found = true;
						$objects = $object->messages;
						break;
					}
				}
			}
				
			if (!$found) {
				$this->raiseException("Unable to locate the '".$fullVariable."' object in the client protobufs");
			}
		}
	}

	
	
}

class MicroService {
	public $description;
	public $request; // MicroServiceRequest object
	public $responses = array(); // Array of MicroServiceRequest objects
}

class MicroServiceRequest {
	public $method;
	public $resource;
	public $payload;
}
?>