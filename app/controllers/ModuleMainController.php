<?php
/*
 * CLASS ModuleMainController
 * MODULE: Principal del Sistema
 * 
 * Every class of this System MODULE must inherits from this class
 * 
 * PRE-CONDITION:
 * 		IsUserLoggedIn was check by "Controller"
 */
class ModuleMainController extends Controller 
{
    function beforeroute() {
    	// Chek if the user is loged in
    	parent::beforeroute();
		
		// The user must have Access to Main
		if ($this->_userAccessModuleMain()) {
	   		$this->_f3->set ("main_navigation", $this->_module["MAIN"]["NAVBAR"]);
			// SESSION.relative_dir is used in messages.html
			$this->_f3->set ("SESSION.module_name", "MAIN");
		}
		else
			trigger_error("Módulo Principal. Acceso prohibido al usuario.");			
		
		// * * * CHECK Operations and Projects associated with the current User  * * * 
		$crud = $this->_f3->get('SESSION.'.Controller::USR_CRUD);
		$relURL = $this->_f3->get("PARAMS.0");
		// IsSuperUSer?
		$superUsuario = $this->_f3->get('SESSION.'.Controller::USR_TIPO) == Controller::TUSR_SUPER_MODIFICACION;
		
		$perms = true;
		
		/* CHECK Crud permissions for SUB MODULES: proyectos, lecciones, asociaciones
		 *  
		 * It's necessary to check only CREATE permisions because 
		 * the routes.ini file disable the UPDATE/DELETE/ASSOCIATE POST REQUESTS
		 *
		 */
		if (!preg_match ("/\/([a-zA-Z0-9_-]+)\/([a-zA-Z0-9_-]+)(\/)?$/",$relURL,$matches))
			return;
		$submodulo = $matches[1]; 
		$operacion = $matches[2];
		if (empty ($submodulo) || empty($operacion))
			return;
		
		if (!$superUsuario) {
			if ($submodulo == "proyecto" && $operacion == "create") {
				// if NOT a SuperUser (all projects) => No perms
				if (!$crud['C']) 
					$perms = false;
			} elseif ($submodulo == "leccion" && $operacion == "create") {
				$currentProy = -1;
				if ($this->_f3->exists('SESSION.idproy'))
					$currentProy = $this->_f3->get('SESSION.idproy');
				$usrProys = $this->_f3->get('SESSION.'.Controller::USR_PROJECTS);
				// The current user is given perms over current project?
			 	$permsXproy = in_array($currentProy, $usrProys);
				// Check if currrent user has CREATE perms for the current project
				if (!$permsXproy || !$crud['C']) 
					$perms = false;
			} elseif ($submodulo == "asociacion") 
				$perms = false;
		}  // Endif !superuser
		if (!$perms) {
			$e = $this->_f3->get ('SESSION.'.Controller::GEN_ERROR_MESSAGE_ARRAY.'["NOPERM"]');
			$this->_f3->set('SESSION.'.Controller::GEN_ERROR_MESSAGE,$e);
 			$this->_f3->reroute ("/error");
		}
    } // end beforeroute
    
} // End class
