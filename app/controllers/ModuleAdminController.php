<?php
/*
 * CLASS ModuleAdminController
 * MODULE: Administración del sistema
 * 
 * Every class of this System MODULE must inherits from this class
 *
 * PRE-CONDITION:
 * 		IsUserLoggedIn was check by "Controller"
 
 */
class ModuleAdminController extends Controller 
{
    function beforeroute() {
    	parent::beforeroute();
		
		// The user must be ADMIN
		if ($this->_userAccessModuleAdmin()) {
			$this->_f3->set ("main_navigation", $this->_module["ADMIN"]["NAVBAR"]);
			// SESSION.relative_dir is used in messages.html
			$this->_f3->set ("SESSION.module_name", "ADMIN");
		}
		else
			trigger_error("Módulo de administración. Acceso prohibido al usuario.");
    }
} // End class
