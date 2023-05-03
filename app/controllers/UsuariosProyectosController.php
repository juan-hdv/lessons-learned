<?php
class UsuariosProyectosController extends ModuleAdminController {

	private $_viewNavigation = "admin/usu_proy_nav.html";// Template to navigate
	private $_tableName = 'usuarios_proyectos';  
	private $_tableKeys = 'idusuario,idproyecto';  
	private $_SQLtableKeys = 'idusuario=? and idproyecto=?';  
	
    // A continuación el CRUD
    public function read()
    {
		// Check SESSION or POST variables
		if($this->_f3->exists('POST.idusuario')) {
			$idusuario = $this->_f3->get('POST.idusuario');
			$usuario = $this->_f3->get('POST.usuario');
			// Update SESSION var
			$this->_f3->set('SESSION.idusuario',$idusuario);
			$this->_f3->set('SESSION.usuario',$usuario);
		} elseif ($this->_f3->exists('SESSION.idusuario')) {
			$idusuario = $this->_f3->get('SESSION.idusuario');
			$usuario = $this->_f3->get('SESSION.usuario');
		} else {
			$this->_error_message[] = "Error UsuariosProyectosController->read: No existe la variable de SESION idusuario.";
			$this->_f3->set('SESSION.'.Controller::ERROR_MESSAGE, $this->_error_message);
			$this->_f3->reroute("/admin/usuarios/proyectos/read/error");
			return;
		}

		// Load proyectos asociados al usuario actual
		// New Usuarios_proyectos
        $viewUsuProy = new ViewMapper($this->_db,'view_usuarios_proyectos','idusuario');
		$filter = array('idusuario =?',$idusuario);
		$usu_proy = $viewUsuProy->dbRead($filter);
		$this->_f3->set('usu_proy', empty($usu_proy)?false:$usu_proy);  // Lecciones list
        
        $this->_f3->set('page_head','Proyectos asociados al usuario - ');	
        $this->_f3->set('page_subhead','Usuario: ' . $usuario);    
        $this->_f3->set('navigation',$this->_viewNavigation);
        $this->_f3->set('view','admin/usu_proy_read.html');			// Template to list Proyects
        $this->_f3->set('op','read');
    }
	
	public function associate()
	{
		// Check SESSION variables
		if ($this->_f3->exists('SESSION.idusuario')) {
			$idusuario = $this->_f3->get('SESSION.idusuario');
			$usuario = $this->_f3->get('SESSION.usuario');
		} else {
			$this->_error_message[] = "Error UsuariosProyectosController->associate: No existe la variable de SESION idusuario.";
			$this->_f3->set('SESSION.'.Controller::ERROR_MESSAGE, $this->_error_message);
			$this->_f3->reroute("/admin/usuarios/proyectos/read/error");
			return;
		}
		
	    if ($this->_f3->exists('POST.associate'))  {
	    	// New Usu_proy
	        $usu_proy = new TableMapper ($this->_db,$this->_tableName, $this->_tableKeys);
	        $usu_proy->dbCreate();
			
			$this->_info_message[] = "El proyecto fue asociado al usuario.";
			$this->_f3->set('SESSION.'.Controller::INFO_MESSAGE, $this->_info_message);
			$this->_f3->reroute('/admin/usuarios/proyectos/read/msg');
			return;
	    } else {
	        try {
		        $this->_getForeignKeyRows();
			} catch (Exception $e) {
				$this->_error_message[] = $e->getMessage();
				$this->_f3->set('SESSION.'.Controller::ERROR_MESSAGE, $this->_error_message);
		 		$this->_f3->reroute('/admin/usuarios/proyectos/read/error');
				return;
			} 	
	    	
	        $this->_f3->set('page_head','Crear asociación - ');
	        $this->_f3->set('page_subhead','Usuario: ' . $usuario);    
	        $this->_f3->set('navigation',$this->_viewNavigation);
	        $this->_f3->set('view',"admin/usu_proy_associate.html");
	        $this->_f3->set('op','associate');
	    }
	} // end function associate	
	
	public function update()
	{
		// New Usu_proy
	    $usu_proy = new TableMapper ($this->_db,$this->_tableName, $this->_tableKeys);
    	$idproy = $this->_f3->get('POST.idproyecto');
		$idusuario = $this->_f3->get('POST.idusuario');
		
	    if($this->_f3->exists('POST.update')) {
	        $usu_proy->dbUpdate(array($idusuario, $idproy));

			$this->_info_message[] = "La asociación del proyecto con el usuario fue actualizada.";
			$this->_f3->set('SESSION.'.Controller::INFO_MESSAGE, $this->_info_message);
			$this->_f3->reroute('/admin/usuarios/proyectos/read/msg');
			return;
	    } else // POST : Editar/Modificar
	    {
	        $usu_proy->dbGetById(array($idusuario, $idproy)); // => Populate POST
	        
	        // Get related table fields (from Foreign Keys) for corrent table
	        try {
		        $this->_getForeignKeyRows();
			} catch (Exception $e) {
				$this->_error_message[] = $e->getMessage();
				$this->_f3->set('SESSION.'.Controller::ERROR_MESSAGE, $this->_error_message);
		 		$this->_f3->reroute('/admin/usuarios/proyectos/read/error');
				return;
			} 	
			
	        // Header, subheader and view
	        $proy = $this->_f3->get('SESSION.idproy');
	        $this->_f3->set('page_head','Modificar asociación - ');
	        $this->_f3->set('page_subhead','Usuario: ' . $idusuario);    
	        $this->_f3->set('navigation',$this->_viewNavigation);
	        $this->_f3->set('view','admin/usu_proy_update.html');
	        $this->_f3->set('op','update');
	    }
	} // End function update
	
	private function _getForeignKeyRows () 
	{
		// Check SESSION variable
		if ($this->_f3->exists('SESSION.idusuario'))
		   $idusuario = $this->_f3->get('SESSION.idusuario');
		else
			trigger_error ("_getForeignKeyRows Error : No existe la variable SESION.idusuario"); 

		// Todos los proyectos no asociados al usuario
		$proys = $this->_db->exec (
		'SELECT idproyecto, codigo FROM proyectos WHERE idproyecto NOT IN '.
		'(SELECT idproyecto FROM usuarios_proyectos WHERE idusuario = "'.$idusuario.'")'
		);		
		if (!empty($proys))					
			$this->_f3->set('proyectos', $proys);
		else
			trigger_error ("No hay más proyectos para asociar."); 
	}
	
	public function unassociate()
	{
		$param = "";
	    if($this->_f3->exists('POST.idproyecto') && 
	       $this->_f3->exists('POST.idusuario'))
	    {
			$idusuario = $this->_f3->get('POST.idusuario');
	    	$idproy = $this->_f3->get('POST.idproyecto');

	        $usu_proy = new TableMapper ($this->_db,$this->_tableName, $this->_tableKeys);
			$usu_proy->dbDelete(array($idusuario, $idproy));

			$this->_info_message[] = "La asociación del proyecto con el usuario fue eliminada.";
			$this->_f3->set('SESSION.'.Controller::INFO_MESSAGE, $this->_info_message);
			$param = "/msg";
	    }
		$this->_f3->reroute('/admin/usuarios/proyectos/read'.$param);
		return;
	}
} // End class
