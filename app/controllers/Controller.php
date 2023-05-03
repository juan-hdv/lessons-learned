<?php
class Controller 
{
	// Error and info messages	
	const ERROR_MESSAGE = "error_message";
	const INFO_MESSAGE = "info_message";
	const GEN_ERROR_MESSAGE = "gen_error_message";
	const GEN_ERROR_MESSAGE_ARRAY = "gen_error_message_array";
	const GEN_ERROR_MESSAGE_FILENAME = "config/error_codes.xml";
	
	// Name of the SESSION variables for the actual logged user
	const USR_ID = "userID";	
	const USR_STATUS = "userLoginStatus";	
	const USR_NOMBRE = "userName";	
	const USR_TIPO = "userUserType";	
	const USR_PERSONA = "userPersonName";	
	const USR_EMAIL = "userEmail";	
	const USR_CRUD = "userCRUD";	
	const USR_PROJECTS = "userProjects";	
	 
	/*
	 * To check user type:
	 * Example
	 * if ($this->_f3->get ("SESSION.".self::USR_TIPO") == p::TUSER_ADMIN)
	 */
	// User Types
	const TUSR_ADMIN = "AD";	
	// const TUSR_NORMAL_CONSULTA = "UC"; // *** AUN NO IMPLEMENTADO	
	const TUSR_NORMAL_MODIFICACION = "UM";	
	const TUSR_SUPER_CONSULTA = "SC";	
	const TUSR_SUPER_MODIFICACION = "SM";

	protected $_error_message = array();  
	protected $_info_message = array();  
	
	/*
	 * *** USER MODULES PERMISSIONS AND PARAMETERS ****
	 */
	protected $_module = array 
		(
			"MAIN"  =>	array // MAIN MODULE
			(
				"NAVBAR" => "navigation.html",
				"TUSERS" => array 
				(
					// self::TUSR_NORMAL_CONSULTA,
					self::TUSR_NORMAL_MODIFICACION,
					self::TUSR_SUPER_CONSULTA, 
					self::TUSR_SUPER_MODIFICACION
				)
			),
			"ADMIN" => array  // ADMIN MODULE
			(
			 	"NAVBAR" => "navigation_admin.html",
				"TUSERS" => array
				(
					self::TUSR_ADMIN
				)
			),
			"LOGIN" => array  // LOGIN MODULE OR STATE
			(
			 	"NAVBAR" => "navigation_login.html",
				"TUSERS" => array ()
			)  
		);
	
	protected $_f3;
    protected $_db;
 
    function beforeroute() {
		// The user must login first
		if (!$this->isUserLoggedIn())
			$this->_f3->reroute ("/login");
    } // End beforeroute
 
    function afterroute() {
		echo Template::instance()->render('layout.html');
		$this->_f3->set ('SESSION.'.self::ERROR_MESSAGE,array());
		$this->_f3->set ('SESSION.'.self::INFO_MESSAGE,array());
		$this->_f3->set ('SESSION.'.self::GEN_ERROR_MESSAGE,array());
    } // End afterroute
 
    function __construct() {
        $f3=Base::instance();
 
        $db=new DB\SQL(
            $f3->get('db_dns') . $f3->get('db_name'),
            $f3->get('db_user'),
            $f3->get('db_pass')
        );
		
	    $this->_f3=$f3;
	    $this->_db=$db;
    } // end construct

    /**
     * TRUE if user is logged in and FALSE if not 
     */
    public function isUserLoggedIn() {
    	return 	($this->_f3->exists("SESSION.".self::USR_STATUS) &&
				$this->_f3->get("SESSION.".self::USR_STATUS)==1); 
	}	

	/*
	 * userAccessModuleAdmin
	 * userAccessModuleMain
	 * 
	 * If there is a logged in User, these two functions return true if the user has access to
	 * the respective system module.
	 */
    protected function _userAccessModuleAdmin() {
		return in_array($this->_f3->get("SESSION.".self::USR_TIPO), $this->_module["ADMIN"]["TUSERS"]);
	}	

    protected function _userAccessModuleMain() {
		return in_array($this->_f3->get("SESSION.".self::USR_TIPO), $this->_module["MAIN"]["TUSERS"]);
	}
	
	/*
	 * _userCRUDArray
	 *  @Return ARRAY where to mark the allowed options for the current User
	 * 
	 *  For each user, every operation (CRUD) is marked 1 or 0 depending if it is allowed or not
	 *  By default the user do not have CRUD permissions
	 */ 
	protected function _userCRUDArray ($tusuario) {
		switch ($tusuario) {
			case self::TUSR_NORMAL_MODIFICACION:
				return Array ("C"=>0,"R"=>1,"U"=>1,"D"=>1, "A"=>1); // A=Associate
				break;
			case self::TUSR_SUPER_MODIFICACION:
				return Array ("C"=>1,"R"=>1,"U"=>1,"D"=>1, "A"=>1); // A=Associate
				break;
			// case TUSR_NORMAL_CONSULTA:		
			case self::TUSR_SUPER_CONSULTA:	
				return Array ("C"=>0,"R"=>1,"U"=>0,"D"=>0, "A"=>1);
		} // end switch
	} // End _userCRUDArray

	/*
	 * _userAssociatedProjectsArray
	 * 
	 *  @Return Array of all the proyects associated with the User
	 * 
	 *  Every user have one or many projects associated for which he has permissions for CRUD 
	 *  By default the user do not have any project associated
	 */ 
	protected function _userAssociatedProjectsArray ($idusuario) {
		$proyMap = new ViewMapper($this->_db,'view_usuarios_proyectos','idusuario,idproyecto,');
		$proysInfo = $proyMap->dbRead(array('idusuario=?',$idusuario));
		// Create a list of idproyecto associated with user
		$associatedProjects = array();
		if (!empty ($proysInfo))
			foreach ($proysInfo as $pry) $associatedProjects[] = "$pry->idproyecto";
		return $associatedProjects;
	}

	/*
	 * error
	 * Handels General Error Messages 
	 */
    public function error () {
    	$this->_f3->set ('page_head',''); // No cambiar la notación a $err.<attrib>!!!
    	$this->_f3->set ('page_subhead','');
    	// main_navigation is set at ModuleAdminController and ModuleMainController
    	// $this->_f3->set ('main_navigation','navigation.html');
    	$this->_f3->set ('navigation','');
    	$this->_f3->set ('view','');
    }
} // End class controller

