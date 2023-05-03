<p>&nbsp;</p>
<p>&nbsp;</p>
<?php
// checking for minimum PHP version
if (version_compare(PHP_VERSION, '5.3.7', '<'))
    exit("Atención, se necesita una versión de PHP superior a la  5.3.7 para el manejo de claves de usuarios.");
if (version_compare(PHP_VERSION, '5.5.0', '<'))
	// Includes the Compatibility library with PHP 5.5's simplified password hashing API.
	require_once ("_phplib/password.php");

require_once ("_phplib/general_lib.php");

Index::execute();

abstract class Index 
{
	static private $_logfile = "debbug.log"; 
	static private $_gen_error_messahes_file = "config/error_codes.xml"; 
	static private $_f3;
	
	public static function execute () {
		Index::$_f3=require_once('_f3/lib/base.php');
		
		Index::$_f3->config('config/config.ini');
		Index::$_f3->config('config/routes.ini');
		
		// * * * * * * *  Global variables
		// Current year
		Index::$_f3->set ('year',date ('Y'));
		// Pattern matching of routes against incoming URIs is case-insensitive by default. Set to FALSE to make it case-sensitive.
		Index::$_f3->set ('CASELESS',false); 
		
		// Error captcha through AppController
		Index::$_f3->set('ONERRORaaaaaaaaaaaaa', 
			function ($ff) {
				$e = array ("code"=>$ff->get ('ERROR.code'), 
							"title"=>$ff->get ('ERROR.title'),
							"text"=>$ff->get ('ERROR.text'));
				$ff->set ('SESSION.gen_error_message', $e);
				$ff->reroute ("/error");
			}
		);
		
		/*
		 * Se quita por cuestions de Performance -- se evaluará despúes
		 * 
		// Load the general error messages
		if (!file_exists (Controller::GEN_ERROR_MESSAGE_FILENAME))
			die('No encontrado el Archivo de códigos de error.');

		$xmlcont = new SimpleXMLElement(file_get_contents(Controller::GEN_ERROR_MESSAGE_FILENAME));
		$gem = array();
		foreach($xmlcont as $err) $gem["$err->code"] = array (
			'code'=>"{$err->code}",
			'title'=>"{$err->title}",
			'text'=>"{$err->text}"
			);
		Index::$_f3->set ('SESSION.'.Controller::GEN_ERROR_MESSAGE_ARRAY,$gem);
		*/
		$gem = array(
		"NOPERM"=>array (
			"code"=>"NOPERM", 
			"title"=>"Operación no permitida al usuario.", 
			"text"=>"El usuario está intentando acceder a una página u operación a la que no tiene permisos.")
		);
		Index::$_f3->set ('SESSION.'.Controller::GEN_ERROR_MESSAGE_ARRAY,$gem);
		
		
		// IsSuperUSer? (All proyects and modify permissions)
		$superUsuario = Index::$_f3->get('SESSION.'.Controller::USR_TIPO) == Controller::TUSR_SUPER_MODIFICACION;
		Index::$_f3->set ('superUser',$superUsuario);
		
		// Start application		
		Index::$_f3->run();
	}
	
	/* 
	 * LOGGER
	 * USE:  Index::logger ("mensaje");
	 */
	public static function logger ($msg) {
		$msg = "[" . date('r') . "]" . $msg;
		file_put_contents(Index::$_logfile, $msg . PHP_EOL, FILE_APPEND);
	}
} // End class Index
