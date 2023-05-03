<?php
/*
 * CLASS ModuleLoginController
 * MODULE: Login del sistema
 * 
 */
class ModuleLoginController extends Controller 
{
    function beforeroute() {
    	// Do not call parent::beforeroute(); User Logged In must not be checked again here (as the parent checked it before)
		// The user could or could not be logged
   		$this->_f3->set ("main_navigation", $this->_module["LOGIN"]["NAVBAR"]);
	}

    public function doLogin()
    {
    	$this->_error_message = array();
		
		// If usr/pwd is being sent by the login form, then dont' check if is logged 
		if ($this->_f3->exists ("POST.login")) {
	    	$usuario = $this->_f3->get ("POST.usuario");
			$clave = $this->_f3->get ("POST.clave");
	        if (!empty($usuario) && !empty($clave)) {
	        	
				$loginOk = $this->_validUserPassword ($usuario, $clave, $userInfo);
				if ($loginOk) {
					// Create de User Session
	                $this->_f3->set ("SESSION.".self::USR_ID, $userInfo[0]->idusuario);
					$this->_f3->set ("SESSION.".self::USR_NOMBRE, $userInfo[0]->usuario);
	                $this->_f3->set ("SESSION.".self::USR_PERSONA, $userInfo[0]->nombre);
	                $this->_f3->set ("SESSION.".self::USR_EMAIL, $userInfo[0]->email);
	                $this->_f3->set ("SESSION.".self::USR_TIPO, $userInfo[0]->tusuario);
	                $this->_f3->set ("SESSION.".self::USR_CRUD, $this->_userCRUDArray($userInfo[0]->tusuario));
	                $this->_f3->set ("SESSION.".self::USR_PROJECTS, $this->_userAssociatedProjectsArray($userInfo[0]->idusuario));
	                $this->_f3->set ("SESSION.".self::USR_STATUS,1);
						                
					if ($userInfo[0]->tusuario == self::TUSR_ADMIN)
						$this->_f3->reroute ("/admin");
					else
						$this->_f3->reroute ("/");
	            }
			} else {
		        if (empty($usuario)) $this->_error_message[] = "Falta nombre de usuario.";
		        if (empty($clave))   $this->_error_message[] = "Falta contraseña.";
			}
			$this->_f3->set (Controller::ERROR_MESSAGE, $this->_error_message);
		} // endif exists
		
        $this->_f3->set('page_head','Entrada al sistema');    
        $this->_f3->set('page_subhead',''); 
        $this->_f3->set('navigation','');	
        $this->_f3->set('view','login/login.html');
	} // end function doLogin

	public function doLogout()
    {
    	// destroy session
		$this->_f3->clear('SESSION');
		$this->_f3->reroute ('/login');
    }

    /*
	 *	 _validUserPassword
	 * 
	 * 	@Return:	BOOLEAN
	 * 	@usr: 		STRING username
	 * 	@pwd:		STRING user password
	 * 	@element 	ARRAY OF OBJECT (BY REFERENCE).  Object of type "Usuarios tableMapp" 
	 */
    private function _validUserPassword ($usr, $pwd, &$userInfo) {	
		// Mapp the users table
		$userMap = new TableMapper($this->_db,'usuarios','idusuario');
		$userInfo = $userMap->dbRead(array('usuario=?',$usr));
		if (!empty ($userInfo)) {
    	    // PHP 5.5's password_verify()
   	        if (!password_verify ($pwd, $userInfo[0]->clave)) {
				$this->_error_message[] = "Clave inválida.";
	        	return false;
			}
		} else {
			$this->_error_message[] = "Usuario inválido.";
			return false;
		}
		return true;
	} // End
    
    // For debugging porpuse only
	private function _getLoggedUserInfo ($glue) {
		$m = array();
	    $m[] = "User ID:".$this->_f3->get ("SESSION.".self::USR_ID);
	    $m[] = "User status:".$this->_f3->get ("SESSION.".self::USR_STATUS);
		$m[] = "Username:".$this->_f3->get ("SESSION.".self::USR_NOMBRE);
	    $m[] = "User type:".$this->_f3->get ("SESSION.".self::USR_TIPO);
	    $m[] = "User CRUD:".$this->_f3->get ("SESSION.".self::USR_CRUD);
	    $m[] = "User projects:".$this->_f3->get ("SESSION.".self::USR_PROJECTS);
		$m[] = "Person Name:".$this->_f3->get ("SESSION.".self::USR_PERSONA);
	    $m[] = "Person Email:".$this->_f3->get ("SESSION.".self::USR_EMAIL);
		return join ($glue,$m);
	} // end _getLoggedUserInfo

	public function changePassword()
	{
		
		$id = $this->_f3->get ("SESSION.".self::USR_ID);
	    $userMap = new TableMapper ($this->_db,'usuarios','idusuario');
		$userMap->dbRead(array("idusuario =?",$id));
		
	    if($this->_f3->exists('POST.update')) {
			$clave = $this->_f3->get('POST.clave');
			$clave2 = $this->_f3->get('POST.clave2');
			$err_msg = UsuarioController::validateNewPassword ($clave, $clave2);	
    		if (empty($err_msg)) {
				$clave = password_hash($clave, PASSWORD_DEFAULT);
				$this->_f3->set('POST.idusuario', $id);
				$this->_f3->set('POST.clave', $clave);
		        $userMap->dbUpdate($id); // Id works with POST array

				$this->_info_message[] = "La clave fue cambiada.";
				$this->_f3->set('SESSION.'.Controller::INFO_MESSAGE,$this->_info_message);
		        $this->_f3->reroute('/');
		        return;
			} else 
				$this->_error_message = $err_msg;
	    }

        // Header, subheader and view
        $this->_f3->set('page_head','Cambiar clave');
        $this->_f3->set('page_subhead','Usuario: '.$userMap->nombre);    
    	$this->_f3->set('navigation',$this->_f3->get ("main_navigation"));	
        $this->_f3->set('view','login/changePassword.html');
        $this->_f3->set('op','update');
		
		$this->_f3->set(Controller::ERROR_MESSAGE,$this->_error_message);		
	} // end function update
} // End class
