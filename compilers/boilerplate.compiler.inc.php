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
		$file = new CompiledFile();
		return array($file);
	}
	
	/**
	*  Generate and return sample code.
	*  Return an array of SampleCode objects
	*/
	public function generateSampleCode() {
		$sampleCode = new SampleCode();
		return array($sampleCode);
	}
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