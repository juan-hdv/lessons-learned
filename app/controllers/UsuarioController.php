<?php
/*
 * UsersController
 * 	Se encarga de la Administración de la tabla de usuarios del sistema
 *  y su relación con la tabla usuarios_proyectos
 * 
 *  Dado el sentido crítico del manejo de esta tabla, requiere un controlador
 *  aparte del de las tablas básicas.  
 * 
 */
 class UsuarioController extends ModuleAdminController {
	
	private $_viewNavigation = "admin/usuarios_nav.html";
	private $_tableName = 'usuarios';  
	private $_tableKeys = 'idusuario';  
	private $_SQLtableKeys = 'idusuario=?';  
		
	private $_tUsuarios = array (
	Controller::TUSR_ADMIN => "ADMINISTRADOR",
	// Controller::TUSR_NORMAL_CONSULTA =>"USUARIO DE CONSULTA (Sólo proyectos asociados)",
	Controller::TUSR_NORMAL_MODIFICACION =>"USUARIO DE MODIFICACIÓN (Sólo proyectos asociados)",
	Controller::TUSR_SUPER_CONSULTA =>"SUPER USUARIO DE CONSULTA (Todos los proyectos)",
	Controller::TUSR_SUPER_MODIFICACION =>"SUPER USUARIO DE MODIFICACIÓN (Todos los proyectos)"
	);

		
	public function __construct () {
		parent::__construct();
		$this->_f3->set ('tUsuarios', $this->_tUsuarios);
	}
	
    // A continuación el CRUD
    public function read()
    {
    	// New Usuario
        $userMap = new TableMapper($this->_db,$this->_tableName,$this->_tableKeys);
        $this->_f3->set('usuarios',$userMap->dbRead('',array('order'=>'tusuario ASC')));

        $this->_f3->set('page_head','Listar usuarios');	// Title    
        $this->_f3->set('page_subhead','');	// Title    
        $this->_f3->set('navigation',$this->_viewNavigation);	
        $this->_f3->set('view','admin/usuarios_read.html');	
        $this->_f3->set('op','read');
	} // end function read
	
	public function create()
	{
	    if ($this->_f3->exists('POST.create'))   {
	        // New Usuario	
	        $userMap = new TableMapper ($this->_db,$this->_tableName,$this->_tableKeys);
	    	$usuario = $this->_f3->get('POST.usuario');
	    	if (!$this->_existUsuario ($userMap, $usuario)) {
	    		// Valida las condiciones de los datos
	    		if ($this->_isValidNewUser (true)) {
	                // crypt the user's password with PHP 5.5's password_hash() function, results in a 60 character
	                // hash string. the PASSWORD_DEFAULT constant is defined by the PHP 5.5, or if you are using
	                // PHP 5.3/5.4, by the password hashing compatibility library
	                $clave = password_hash($this->_f3->get('POST.clave'), PASSWORD_DEFAULT);
					$this->_f3->set('POST.clave', $clave);
			        $userMap->dbCreate ();
					
					$this->_info_message[] = "El usuario fue creado.";
					$this->_f3->set('SESSION.'.Controller::INFO_MESSAGE,$this->_info_message);
			        $this->_f3->reroute('/admin/usuarios/read/msg');
					return;
				}
			} else
				$this->_error_message[] = "El usuario '$name' ya existe en la tabla USUARIOS";
	    }
        // Get related table fields (from Foreign Keys) for corrent table
        $this->_getForeignKeyRows();	
    	
    	$this->_f3->set('usuarios','');
        $this->_f3->set('page_head','Crear usuario');
        $this->_f3->set('page_subhead','');	// SubTitle    
    	$this->_f3->set('navigation',$this->_viewNavigation);	
        $this->_f3->set('view','admin/usuarios_create.html');
        $this->_f3->set('op','create');
        $this->_f3->set('disableMainMenu',1);
		
		$this->_f3->set(Controller::ERROR_MESSAGE,$this->_error_message);
	} // End function create	
	
	public function update()
	{
		// New Usuario
	    $userMap = new TableMapper ($this->_db,$this->_tableName,$this->_tableKeys);
	 	$id = $this->_f3->get('POST.idusuario');
    	$usuario = $this->_f3->get('POST.usuario');
		
	    if($this->_f3->exists('POST.update')) {
	    	if (!$this->_existUsuario ($userMap, $usuario, $id)) {
	    		if ($this->_isValidNewUser (false)) {
			        $userMap->dbUpdate($id);

					$this->_info_message[] = "El usuario fue actualizado.";
					$this->_f3->set('SESSION.'.Controller::INFO_MESSAGE,$this->_info_message);
			        $this->_f3->reroute('/admin/usuarios/read/msg');
					return;
				}
			} else
				$this->_error_message[] = "El usuario '$usuario' ya existe en la tabla USUARIOS.";
	    } else  // POST: Editar/Modificar 
	        $userMap->dbGetById($id); // => Populate POST

        // Get related table fields (from Foreign Keys) for corrent table
        $this->_getForeignKeyRows();	
		
        // Header, subheader and view
        $this->_f3->set('page_head','Modificar Usuario');
        $this->_f3->set('page_subhead','');	// SubTitle    
    	$this->_f3->set('navigation',$this->_viewNavigation);	
        $this->_f3->set('view','admin/usuarios_update.html');
        $this->_f3->set('op','update');
        $this->_f3->set('disableMainMenu',1);
		
		$this->_f3->set(Controller::ERROR_MESSAGE,$this->_error_message);
	} // end function update
	
	/* changePassword
	 * 
	 * Update the password field for a user
	 * 
	 */
	public function changePassword()
	{
		// New Usuario
	    $userMap = new TableMapper ($this->_db,$this->_tableName,$this->_tableKeys);
	 	$id = $this->_f3->get('POST.idusuario');
	 	$usuario = $this->_f3->get('POST.usuario');
		
	    if($this->_f3->exists('POST.update')) {
    		if ($this->_isValidNewPassword ()) {
				$clave = password_hash($this->_f3->get('POST.clave'), PASSWORD_DEFAULT);
				$this->_f3->set('POST.clave', $clave);
		        $userMap->dbUpdate($id);

				$this->_info_message[] = "La clave fue cambiada.";
				$this->_f3->set('SESSION.'.Controller::INFO_MESSAGE,$this->_info_message);
		        $this->_f3->reroute('/admin/usuarios/read/msg');
				return;
			} 
	    } else  // POST: Editar/Modificar 
	        $userMap->dbGetById($id); // => Populate POST

        // Header, subheader and view
        $this->_f3->set('page_head','Cambiar clave');
        $this->_f3->set('page_subhead','Usuario: '.$usuario);    
    	$this->_f3->set('navigation',$this->_viewNavigation);	
        $this->_f3->set('view','admin/usuarios_changepwd.html');
        $this->_f3->set('op','update');
        $this->_f3->set('disableMainMenu',1);
		
		$this->_f3->set(Controller::ERROR_MESSAGE,$this->_error_message);		
	} // end function update
		
	private function _getForeignKeyRows () 
	{
		// Get related table fields (from Foreign Keys) for corrent table
		// Usuarios_proyectos
		/*
		$tableMap = new ViewMapper ($this->_db,'view_usuarios_proyectos','idusuario,idproyecto'); 
		$temp = objectsToArray ($tableMap->dbRead(), 'idusuario,idproyecto,codigo'); 
		$this->_f3->set('proyectos', $temp);
		*/
	} // end funtion _getForeignKeyRows
	
	public function delete()
	{
		$param = "";
	    if ($this->_f3->exists('POST.idusuario'))
	    {
	    	// New usuario
	        $usuario = new TableMapper ($this->_db,$this->_tableName,$this->_tableKeys);
	        $usuario->dbDelete($this->_f3->get('POST.idusuario'));

			$this->_info_message[] = "El usuario fue eliminado.";
			$this->_f3->set('SESSION.'.Controller::INFO_MESSAGE,$this->_info_message);
			$param = "/msg";
	    }
	    $this->_f3->reroute('/admin/usuarios/read'.$param);
		return;
	} // end function delete
	
	private function _existUsuario ($tableMap, $name, $id=null) {
		if (!is_null($id)) 
			$result = $tableMap->dbRead(array("idusuario !=? and usuario =?",$id, $name));
		else 
			$result = $tableMap->dbRead(array("usuario=?", $name));
		return !empty($result);  
	} // end function _existUsuario

	/*
	 * _isValidNewUser
	 * 
	 * Check if all the fields of a user have a valid format
	 * if checkPassword is TRU then password field is also checked
	 * otherwise password format is not checked 
	 */
	private function _isValidNewUser($checkPassword=false)
    {
    	$err_msg = array();
		$usuario = $this->_f3->get('POST.usuario');
		$nombre = $this->_f3->get('POST.nombre');
		$email = $this->_f3->get('POST.email');
        if (empty($usuario))
            $err_msg[] = "Falta el nombre de usuario.";
		$l = strlen($usuario);
        if ( $l < 2 || $l > 10)
            $err_msg[] = "El nombre del usuario debe tener entre 2 y 16 caracteres.";
        if (!preg_match('/^[A-Za-z0-9-_]+$/i', $usuario))
            $err_msg[] = "El nombre del usuario sólo debe tener caracteres alfanuméricos y guiones [a-Z][0-9][-_].";
		
		if ($checkPassword) 
			$this->_isValidNewPassword();
			
        if (empty($nombre))
            $err_msg[] = "Falta el nombre de la persona.";
        if (empty($email))
            $err_msg[] = "Falta el Email";
        if (strlen($email) > 64)
            $err_msg[] = "El Email no puede tener más de 64 caracteres.";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
            $err_msg[] = "El Mail no tiene un formato válido.";
		
		if (empty ($this->_error_message))
			$this->_error_message = $err_msg;
		else 
			$this->_error_message = array_merge ($this->_error_message, $err_msg);
		
		return empty($this->_error_message);
    }	// end function _valiNewUser

    /*
	 * isValidNewPassword
	 * Check if the password field of a user has a valid format
	 */
    static public function validateNewPassword ($clave, $clave2) {
    	$err_msg = array();
		if (empty($clave))
            $err_msg[] = "Falta la clave.";
		if (empty($clave2))
            $err_msg[] = "Falta repetir la clave.";
		$l = strlen($clave);
        if ($l < 2 || $l > 10)
            $err_msg[] = "La clave debe tener entre 8 y 10 caracteres";
        if (!preg_match('/^[A-Za-z0-9-_]+$/i', $clave))
            $err_msg[] = "La clave sólo debe tener caracteres alfanuméricos y guiones [a-Z][0-9][-_].";
		if ($clave != $clave2)
            $err_msg[] = "La claves no corresponden. Por favor ingrese de nuevo las claves.";
        return $err_msg;    	
    } // ValidateNewPassword
    
    
    /*
	 * _isValidNewPassword
	 * Check if the password field of a user has a valid format
	 */
	private function _isValidNewPassword()
    {
		$clave = $this->_f3->get('POST.clave');
		$clave2 = $this->_f3->get('POST.clave2');
		$err_msg = self::validateNewPassword ($clave, $clave2);
		if (empty ($this->_error_message))
			$this->_error_message = $err_msg;
		else 
			$this->_error_message = array_merge ($this->_error_message, $err_msg);
        return empty($this->_error_message);
    }	// end function _isValiNewPassword
    
} // End class UsuarioController
