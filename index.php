<?php

ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);

$_OUTPUT = array();

/*
$_INPUT = array();
$_INPUT["platform_proto"] = $_POST["platform_proto"];
$_INPUT["client_proto"]	= $_POST["client_proto"];
$_INPUT["platform_name"]	= $_POST["platform_name"];
$_INPUT["language"]	= $_POST["language"];
$_INPUT["version"] = $_POST["version"];
$_INPUT["copyright"] = $_POST["copyright"];
$_INPUT["microservices"] = $_POST["microservices"];
*/

$_INPUT = array();
$_INPUT["platform_proto"] = file_get_contents("testing/platform.proto");
$_INPUT["client_proto"]	= file_get_contents("testing/client.proto");
$_INPUT["platform_name"]	= "TelTech";
$_INPUT["language"]	= "js";
$_INPUT["version"] = "1";
$_INPUT["copyright"] = "(c) 2015 TelTech.co";
$_INPUT["microservices"] = file_get_contents("testing/microservices.json");

// Parse the proto files
include("includes/protoparser.inc.php");
$protoParser = new ProtoParser();
try {
	$platformProtobufs = $protoParser->parse($_INPUT["platform_proto"]);
} catch (Exception $e) {
	returnFailure("Failed to parse platform protobufs: ".$e->getMessage());
}
try {
	$clientProtobufs = $protoParser->parse($_INPUT["client_proto"]);
} catch (Exception $e) {
	returnFailure("Failed to parse client protobufs: ".$e->getMessage());
}

// Pretty print the protobufs
try {
	$_OUTPUT["platform_proto"] = $protoParser->prettyPrint($_INPUT["platform_proto"]);
} catch (Exception $e) {
	returnFailure("Failed to pretty print platform protobufs: ".$e->getMessage());
}
try {
	$_OUTPUT["client_proto"] = $protoParser->prettyPrint($_INPUT["client_proto"]);
} catch (Exception $e) {
	returnFailure("Failed to pretty print client protobufs: ".$e->getMessage());
}

// Parse the micro services
include("includes/microserviceparser.inc.php");
$microServiceParser = new MicroServiceParser();
try {
	$microServiceParser->setPlatformProtobufs($platformProtobufs);
	$microServiceParser->setClientProtobufs($clientProtobufs);
	$microServices = $microServiceParser->parse($_INPUT["microservices"]);
	$_OUTPUT["microservices"] = $microServices;
} catch (Exception $e) {
	returnFailure("Failed to parse the micro services: ".$e->getMessage());
}

// Load the appropriate compiler
switch (strtolower($_INPUT["language"])) {
	case "js":
		include("compilers/js/compiler.inc.php");
		break;
		
	default:
		returnFailure("No compiler found for language '".$_INPUT["language"]."'");
		break;
}

// Compile the library
try {
	$compiler = new Compiler();
	$compiler->setPlatformProtobufs($platformProtobufs, $_OUTPUT["platform_proto"]);
	$compiler->setClientProtobufs($clientProtobufs, $_OUTPUT["client_proto"]);
	$compiler->setMicroServices($microServices);
	$compiler->setPlatformName($_INPUT["platform_name"]);
	$compiler->setVersion($_INPUT["version"]);
	$compiler->setCopyright($_INPUT["copyright"]);
	$_OUTPUT["compiled_files"] = $compiler->compile();
	$_OUTPUT["sample_code"] = $compiler->generateSampleCode();
} catch (Exception $e) {
	returnFailure("Failed to compile library: ".$e->getMessage());
}

returnSuccess($_OUTPUT);

/**
 *
 */
function returnSuccess($output) {
	$output["status"] = "success";
	print json_encode($output);
} 

/**
 *  
 */
function returnFailure($message) {
	print json_encode(array("status"=>"failure", "message"=>$message));
	exit;
}	

?>