<?PHP
	
class Compiler {
	private $platformProtobufs = array();
	private $platformProtoFile = "";
	private $clientProtobufs = array();
	private $clientProtoFile = "";
	private $microServices = array();
	private $platformName = "";
	private $version = "";
	private $copyright = "";
	
	/**
	*  $protobufs is an array of ProtoMessage and ProtoENUM objects which you
	*  can find at the bottom includes/protoparser.inc.php
	*  $protofile is the raw protobuf file
	*/
	public function setPlatformProtobufs($protobufs, $protofile) {
		$this->platformProtobufs = $protobufs;
		$this->platformProtoFile = $protofile;
	}
	
	/**
	*  $protobufs is an array of ProtoMessage and ProtoENUM objects which you
	*  can find at the bottom includes/protoparser.inc.php
	*  $protofile is the raw protobuf file
	*/
	public function setClientProtobufs($protobufs, $protofile) {
		$this->clientProtobufs = $protobufs;
		$this->clientProtoFile = $protofile;
	}
	
	/**
	*  $microServices is an array of MicroService objects which you can find
	*  at the bottom of includes/microservicesparser.inc.php
	*/
	public function setMicroServices($microServices) {
		$this->microServices = $microServices;
	}
	
	/**
	*  The name of the micro platform
	*/
	public function setPlatformName($platformName) {
		$this->platformName = $platformName;
	}
	
	/**
	*  The version the user wants to use for this compiled library
	*/
	public function setVersion($version) {
		$this->version = $version;
	}
	
	/**
	*  The copyright the user wants for this compiled library
	*/
	public function setCopyright($copyright) {
		$this->copyright = $copyright;
	}
	
	/**
	*  Compile the library
	*  Return an array of CompiledFile objects
	*/
	public function compile() {
		$files = array_merge($this->staticFiles(), $this->createGeneratedFiles());
		return $files;
	}
	
	/**
	*  Generate and return sample code.
	*  Return an array of SampleCode objects
	*/
	public function generateSampleCode() {
		$sampleCode = new SampleCode();
		return array($sampleCode);
	}
	
	/**
	*
	*/
	private function createGeneratedFiles() {
		$directory = dirname(__FILE__)."/generated_files";
		$files = $this->getFilesInDirectory($directory);
		foreach ($files as $file) {
			$file->path = substr($file->path, strlen($directory)+1);
			$file->contents = str_replace("{{@_COPYRIGHT_YEAR_@}}", date("Y"), $file->contents);
			$file->contents = str_replace("{{@_COPYRIGHT_NAME_@}}", "MicroPlatform.io", $file->contents);
			$file->contents = str_replace("{{@_PLATFORM_NAME_@}}", $this->platformName, $file->contents);
			if (strpos($file->contents, "{{@_PLATFORM_PROTO_JSON_@}}") !== false) {
				$file->contents = str_replace("{{@_PLATFORM_PROTO_JSON_@}}", $this->escapeJSString($this->platformProtoFile), $file->contents);
			}
			if (strpos($file->contents, "{{@_CLIENT_PROTO_JSON_@}}") !== false) {
				$file->contents = str_replace("{{@_CLIENT_PROTO_JSON_@}}", $this->escapeJSString($this->clientProtoFile), $file->contents);
			}
			if (strpos($file->contents, "{{@_GENERATED_API_@}}") !== false) {
				$file->contents = str_replace("{{@_GENERATED_API_@}}", $this->generateAPI(), $file->contents);
			}
			$file->filename = $this->platformName.$file->filename;
			
			if ($file->filename == "TelTechAPI.js") {
				print_r($file);
				exit;
			}
		}
		return $files;
	}
	
	/**
	*  Get all the static files
	*  Returns an array of CompiledFile objects
	*/
	private function staticFiles() {
		$directory = dirname(__FILE__)."/static_files";
		$files = $this->getFilesInDirectory($directory);
		foreach ($files as $file) {
			$file->path = substr($file->path, strlen($directory)+1);
		}
		return $files;
	}
	
	/**
	*  Get all the files in a directory
	*  Returns an array of CompiledFile objects
	*/
	private function getFilesInDirectory($directory) {
		$files = array();
		foreach (scandir($directory) as $file) {
			if ($file == "." || $file == "..") {
				continue;
			} else if (is_dir($directory."/".$file)) {
				$files = array_merge($files, $this->getFilesInDirectory($directory."/".$file));
			} else {
				$compiledFile = new CompiledFile();
				$compiledFile->path = $directory;
				$compiledFile->filename = $file;
				$compiledFile->contents = file_get_contents($directory."/".$file);
				array_push($files, $compiledFile);
			}
		}
		return $files;
	}
	
	/**
	*  Escape a string so i can be placed inside a javascript variable
	*/
	private function escapeJSString($string) {
		$string = str_replace("\\", "\\\\", $string);
		$string = str_replace("\"", "\\\"", $string);
		$string = str_replace("\'", "\\\'", $string);
		$string = str_replace("\n", "\\n", $string);
		return $string;
	}
	
	/**
	*  Generate and return the API methods
	*/
	private function generateAPI() {
		$code = "";
		
		foreach ($this->microServices as $microService) {
			// Add the comments
			$code .= $this->generateAPIComments($microService)."\n";
			
			if ($microService->request->payload && strlen($microService->request->payload) > 0) {
				$payloadName = $this->formatPayloadVariable($microService->request->payload);
				$parameters = $payloadName.", handler";
			} else {
				$parameters = "handler";
			}
			
			$methodName = $this->formatMethodName($microService->request->method);
			$resourceName = $this->formatResourceName($microService->request->resource);
			$method = $this->camelCase($methodName." ".$resourceName);
			$code .= <<<CODE
	this.{$method} = function({$parameters}) {
		// Create the protobuf objects
		var PlatformResource = {$this->platformName}PlatformProtoBuilder.build("Resource");
		var PlatformMethod = {$this->platformName}PlatformProtoBuilder.build("Method");
		
		// Setup the socket request
		var self = this;
		var request = new {$this->platformName}SocketRequest();
		request.method = Method.CREATE;
		request.resource = Resource.PUSH_TOKEN;
		request.payload = generateCloudRequestObject({$this->platformName}Request.toArrayBuffer()).toArrayBuffer();
		request.setTimeout({$this->platformName}Constants.requestTimeout);
		request.onResponse = function(response) {

	}


CODE;
		}
		

		return $code;
	}
	
	/**
	*  Generate and return the API comments
	*/
	private function generateAPIComments($microService) {
		$parameters = "";

		$longestParameter = strlen("handler");
		$payloadName = $this->formatPayloadVariable($microService->request->payload);
		if (strlen($payloadName) > $longestParameter) {
			$longestParameter = strlen($payloadName);
		}
		if ($payloadName && strlen($payloadName) > 0) {
			$padding = str_pad("", ($longestParameter - strlen($payloadName) + 1), " ");
			$values = explode(".", $microService->request->payload);
			$payloadComment = implode(".", array_slice($values, 1))." ".$values[0]." protobuf object";
			$parameters = "	 *  @param ".$payloadName.$padding.$payloadComment."\n";
		}
		
		$padding = str_pad("", ($longestParameter - strlen("handler") + 1), " ");
		$parameters .= "	 *  @param handler".$padding."Response handler";
		

		$comments = <<<COMMENTS
	/**
	 *  {$microService->description}
	 *
{$parameters}
	 *
	 *  @return {$this->platformName}SocketRequest()
	 */
COMMENTS;
		return $comments;
	}

	/**
	*  Format a method variable name
	*/
	private function formatMethodName($variable) {
		$values = explode(".", $variable);
		$variable = end($values);
		return strtolower($variable);
	}
		
	/**
	*  Format a method resource name
	*/
	private function formatResourceName($variable) {
		$values = explode(".", $variable);
		$variable = end($values);
		return strtolower($variable);
	}
	
	/**
	*  Format a payload variable name
	*/
	private function formatPayloadVariable($variable) {
		$values = explode(".", $variable);
		$variable = end($values);
		return $this->camelCase($variable);
	}
	
	/**
	*  Format a string to camelCase and return it
	*/
	private function camelCase($str, $noStrip = array()) {
		// non-alpha and non-numeric characters become spaces
		$str = preg_replace('/[^a-z0-9' . implode("", $noStrip) . ']+/i', ' ', $str);
		$str = trim($str);
		// uppercase the first character of each word
		$str = ucwords($str);
		$str = str_replace(" ", "", $str);
		$str = lcfirst($str);
		
		return $str;
	}
		
	/**
	 *  Add a push token to an account
	 *
	 *  @param pushToken       PushToken protobuf object
	 *  @param _accountSession A valid AccountSession protobuf object
	 *  @param handler         Response handler
	 *
	 *  @return {{@_PLATFORM_NAME_@}}SocketRequest()
	 */
/*
	this.createPushToken = function(pushToken, _accountSession, handler) {
		// Create the protobuf objects
		var CloudResource = {{@_PLATFORM_NAME_@}}CloudProtoBuilder.build("Resource");
		var CloudMethod = {{@_PLATFORM_NAME_@}}CloudProtoBuilder.build("Method");
		var Resource = {{@_PLATFORM_NAME_@}}ProtoBuilder.build("Resource");
		var Method = {{@_PLATFORM_NAME_@}}ProtoBuilder.build("Method");
		var {{@_PLATFORM_NAME_@}}Request = {{@_PLATFORM_NAME_@}}ProtoBuilder.build("Request");

		// Setup the request
		try {
			var {{@_PLATFORM_NAME_@}}Request = new {{@_PLATFORM_NAME_@}}Request();
			{{@_PLATFORM_NAME_@}}Request.set_application(generateApplicationObject(this));
			{{@_PLATFORM_NAME_@}}Request.set_account_session(_accountSession);
			{{@_PLATFORM_NAME_@}}Request.set_body(pushToken.toArrayBuffer());
		} catch(err) {
			if (handler.onFailure) {
				var error = new Error("Unable to create the request. Did you pass all the required parameters? Error: " + err.message);
				handler.onFailure(error, false, false);
			} else {
				console.log("Unable to fire the 'onFailure' event because you did not implement it!");
			}
			return;
		}
		
		// Setup the socket request
		var self = this;
		var request = new {{@_PLATFORM_NAME_@}}SocketRequest();
		request.method = Method.CREATE;
		request.resource = Resource.PUSH_TOKEN;
		request.payload = generateCloudRequestObject({{@_PLATFORM_NAME_@}}Request.toArrayBuffer()).toArrayBuffer();
		request.setTimeout({{@_PLATFORM_NAME_@}}Constants.requestTimeout);
		request.onResponse = function(response) {
			switch (response.method) {
				case Method.REPLY:
					try {
						switch (response.resource) {
							case Resource.PUSH_TOKEN:
								if (handler.onReplyPushToken) {
									var pushToken = {{@_PLATFORM_NAME_@}}ProtoBuilder.build("PushToken").decode(response.payload, 'hex');
									handler.onReplyPushToken(pushToken);
								} else {
									console.log("Unable to fire the 'onReplyPushToken' event because you did not implement it!");
								}
								break;

							case Resource.ERROR_LIST:
								if (handler.onReplyErrorList) {
									var errorList = {{@_PLATFORM_NAME_@}}ProtoBuilder.build("ErrorList").decode(response.payload, 'hex');
									handler.onReplyErrorList(errorList);
								} else {
									console.log("Unable to fire the 'onReplyErrorList' event because you did not implement it!");
								}
								break;

							default:
								if (handler.onFailure) {
									var error = new Error("Received unexpected resource '" + response.resource + "'");
									handler.onFailure(error, false, false);
								} else {
									console.log("Unable to fire the 'onFailure' event because you did not implement it!");
								}
						}
					} catch(err) {
						if (handler.onFailure) {
							var error = new Error("Failed to process response: " + err.message);
							handler.onFailure(error, false, false);
						} else {
							console.log("Unable to fire the 'onFailure' event because you did not implement it!");
						}
					}
					break;
				default:
					if (handler.onFailure) {
						if (response.resource == CloudResource.ERROR) {
							try {
								var cloudError = {{@_PLATFORM_NAME_@}}CloudProtoBuilder.build("Error").decode(response.payload, 'hex');
								var error = new Error("Cloud Error: " + cloudError.message);
							} catch(err) {
								var error = new Error("Failed to process cloud error: " + err.message);
							}
						} else {
							var error = new Error("Received unexpected method '" + response.method + "'");
						}
						handler.onFailure(error, false, false);
					} else {
						console.log("Unable to fire the 'onFailure' event because you did not implement it!");
					}
			}
		}
		request.onTimeout = function() {
			self.connection.cancelRequest(request);
			if (handler.onFailure) {
				var error = new Error("Request timed out");
				handler.onFailure(error, true, false);
			} else {
				console.log("Unable to fire the 'onFailure' event because you did not implement it!");
			}
		}
		request.onCancelled = function(error) {
			self.connection.cancelRequest(request);
			if (handler.onFailure) {
				var error = new Error("Request cancelled");
				handler.onFailure(error, false, true);
			} else {
				console.log("Unable to fire the 'onFailure' event because you did not implement it!");
			}
		}

		// Send the request
		this.connection.sendRequest(request);
		return request;
	}
*/
	
}

class SampleCode {
	public $microService; // MicroService object (found at the bottom of includes/microservicesparser.inc.php)
	public $code; // String of sample code
}

class CompiledFile {
	public $path;
	public $filename;
	public $contents;
}

?>