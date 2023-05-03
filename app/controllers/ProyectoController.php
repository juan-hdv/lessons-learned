<?php
class ProyectoController extends ModuleMainController {

	private $_viewNavigation = "proyecto/nav.html";
	private $_tableName = 'proyectos';  
	private $_tableKeys = 'idproyecto';  
	private $_SQLtableKeys = 'idproyecto=?';
		
    // A continuación el CRUD
    
    public function read ()
    {
    	// New ViewProyecto
        $viewProyecto = new ViewMapper($this->_db,'view_proyectos_nlec','idproyecto');
		$proyectos = $viewProyecto->dbRead();
        $this->_f3->set('proyectos',$proyectos);  		// Proyect list

    	$op = ($this->_f3->exists('PARAMS.msg') && $this->_f3->get('PARAMS.msg')=='export')?'export':'read';
        switch ($op) {
			case 'read':
		  		$this->_f3->set('op','read');
		  	    $this->_f3->set('navigation',$this->_viewNavigation);	
		        $this->_f3->set('page_head','Listar Proyectos');	// Title    
		        $this->_f3->set('page_subhead','');	// Title    
		        $this->_f3->set('view','proyecto/read.html');
				// To select which buttons should be allowed to the User
				$this->_setButtonsVars ();
				break;	
			case 'export':
				$this->export($proyectos);
				exit(); // To stop routing
			default:
				break;
		} // Swicth
	} // read

	public function export($proyectos)
    {
    	$delimiter = $this->_f3->get('export_delimiter');
    	$company =  $this->_f3->get('company_name');
    	$system =  $this->_f3->get('system_name');
		$dbname = $this->_f3->get('db_name');
		$list = array();
		$list[] = array("$company - $system");
		$list[] = array("Listado de proyectos");
		$list[] = array("Código","Nombre","Estado","Tipo de servicio","Tipo de proyecto","Mercado","Cliente","Arquitectura","Tecnologia","Base de datos","Ubicación","País","Num. lecciones");
		// Get project list
		foreach ($proyectos as $elem) {
	        $list[] = array($elem->codigo,$elem->nombre,($elem->estado=='1'?"ABIERTO":"CERRADO"),$elem->tservicio,$elem->tproyecto,$elem->mercado,$elem->cliente,$elem->arquitectura,$elem->tecnologia,$elem->basedatos,$elem->ubicacion,$elem->pais,$elem->numlec);  
		}
		array2csvDownload ($list, 'proyectos.csv', $delimiter);
	} // End Export
	
	public function create()
	{
        $proy = new TableMapper ($this->_db,$this->_tableName,$this->_tableKeys);
	    if ($this->_f3->exists('POST.create')) {
	        // New Proyecto	
	    	$code = $this->_f3->get('POST.codigo');
	    	if (!$this->_existProyectCode ($proy, $code)) {
		        $proy->dbCreate(); // Create new from POST (hive array)

		        // Populate related tables (tecnologias_proyectos)
        		$this->_setRelationshipTables($proy);		        

		        $this->_info_message[] = "El proyecto fue creado.";
				$this->_f3->set('SESSION.'.Controller::INFO_MESSAGE,$this->_info_message);
		        $this->_f3->reroute('/proyecto/read/msg');
				return;
			} else {
				$this->_error_message[] = "El valor '$code' ya existe en la tabla PROYECTOS";
				$this->_f3->set(Controller::ERROR_MESSAGE,$this->_error_message);
			}
	    }
        // Get related table fields (from Foreign Keys) for current table
        $this->_getForeignKeyRows($proy);	
    	
    	$this->_f3->set('proyectos','');
        $this->_f3->set('page_head','Crear Proyecto');
        $this->_f3->set('page_subhead','');	// SubTitle    
    	$this->_f3->set('navigation',$this->_viewNavigation);	
        $this->_f3->set('view','proyecto/create.html');
        $this->_f3->set('op','create');
        $this->_f3->set('disableMainMenu',1);
		
		// To select which buttons should be allowed to the User
		$this->_setButtonsVars ();
	} // end create	
	
	public function update()
	{
		// New Proyecto
	    $proy = new TableMapper ($this->_db,$this->_tableName,$this->_tableKeys);
	 	$id = $this->_f3->get('POST.idproyecto');
    	$code = $this->_f3->get('POST.codigo');
		
	    if($this->_f3->exists('POST.update')) {
	    	if (!$this->_existProyectCode ($proy, $code, $id)) {
		        $proy->dbUpdate($id); // Update de proyect
		        // Populate related tables (tecnologias_proyectos)
        		$this->_setRelationshipTables($proy);		        
		        		        
		        $this->_info_message[] = "El proyecto fue actualizado.";
				$this->_f3->set('SESSION.'.Controller::INFO_MESSAGE,$this->_info_message);
		        $this->_f3->reroute('/proyecto/read/msg');
				return;
			} else {
				$this->_error_message[] = "El valor '$code' ya existe en la tabla PROYECTOS";
				$this->_f3->set(Controller::ERROR_MESSAGE,$this->_error_message);
			}
	    } else  // POST: Editar/Modificar proyeto
	        $proy->dbGetById($id); // => Populate POST

        // Get related table fields (from Foreign Keys) for corrent table
        $this->_getForeignKeyRows($proy);	
		
        // Header, subheader and view
        $this->_f3->set('page_head','Modificar Proyecto');
        $this->_f3->set('page_subhead','');	// SubTitle    
    	$this->_f3->set('navigation',$this->_viewNavigation);	
        $this->_f3->set('view','proyecto/update.html');
        $this->_f3->set('op','update');
        $this->_f3->set('disableMainMenu',1);

		// To select which buttons should be allowed to the User
		$this->_setButtonsVars ();
	} // End update

	public function delete()
	{
		$param = "";	
	    if ($this->_f3->exists('POST.idproyecto'))
	    {
	    	// New proyecto
	        $proy = new TableMapper ($this->_db,$this->_tableName,$this->_tableKeys);
	        $proy->dbDelete($this->_f3->get('POST.idproyecto'));
	        $this->_info_message[] = "El proyecto fue eliminado.";
			$this->_f3->set('SESSION.'.Controller::INFO_MESSAGE,$this->_info_message);
			$param = "/msg";
	    }
	    $this->_f3->reroute('/proyecto/read'.$param);
		return;
	}
		
	private function _getForeignKeyRows ($proy=null) 
	{
		// Get related table fields (from Foreign Keys) for corrent table
		// TServicio
		$tableMap = new TableMapper ($this->_db,'tservicios','idtservicio');
		$temp = objectsToArray ($tableMap->dbRead(), 'idtservicio,nombre'); 
		$this->_f3->set('tservicios', $temp); 
		// TProyectos
		$tableMap = new TableMapper ($this->_db,'tproyectos','idtproyecto'); 
		$temp = objectsToArray ($tableMap->dbRead(), 'idtproyecto,nombre'); 
		$this->_f3->set('tproyectos', $temp); 
	    // Cliente
		$tableMap = new TableMapper ($this->_db,'clientes','idcliente');
		$temp = objectsToArray ($tableMap->dbRead(), 'idcliente,nombre'); 
		$this->_f3->set('clientes', $temp); 
		// Mercado
		$tableMap = new TableMapper ($this->_db, 'mercados','idmercado');
		$temp = objectsToArray ($tableMap->dbRead(), 'idmercado,nombre'); 
		$this->_f3->set('mercados', $temp); 
		// Arquitectura
		$tableMap = new TableMapper ($this->_db, 'arquitecturas','idarquitectura');
		$temp = objectsToArray ($tableMap->dbRead(), 'idarquitectura,nombre'); 
		$this->_f3->set('arquitecturas', $temp); 
		// Tecnologías
		$tableMap = new TableMapper ($this->_db, 'tecnologias','idtecnologia');
		$temp = objectsToArray ($tableMap->dbRead(), 'idtecnologia,nombre'); 
		$this->_f3->set('tecnologias', $temp);
		// Tecnología2: Tecnologias secundarias asociadas al proyecto
		if (!is_null($proy) && !is_null ($proy->idproyecto)) {
			$tableMap = new TableMapper ($this->_db, 'tecnologias_proyectos','idtecnologia,idproyecto');
			$temp = objectsToArray ($tableMap->dbRead(array("idproyecto = ?", $proy->idproyecto)), 'idtecnologia');
			// Pasa a un arreglo simple los valores de las tecnologías asociadas del proyecto
			$temp = array_map(function ($reg) { return $reg['idtecnologia']; }, $temp);
			
			$this->_f3->set('tecnologias2', $temp);
		}
		// Base de datos
		$tableMap = new TableMapper ($this->_db, 'basesdatos','idbasedatos');
		$temp = objectsToArray ($tableMap->dbRead(), 'idbasedatos,nombre'); 
		$this->_f3->set('basesdatos', $temp); 
		// Ubicación
		$tableMap = new TableMapper ($this->_db,'ubicaciones','idubicacion');
		$temp = objectsToArray ($tableMap->dbRead(), 'idubicacion,nombre'); 
		$this->_f3->set('ubicaciones', $temp); 
	}

	private function _setRelationshipTables ($proy=null) 
	{
		// Tecnologia Principal
		$tecnologia = $this->_f3->get('POST.tecnologia');
		// Tecnologias2  (Tecnologias secundarias)
		$tecnologias2 = $this->_f3->get('POST.tecnologias2');
		
		$tableMap = new TableMapper ($this->_db, 'tecnologias_proyectos','idproyecto,idtecnologia');
		// Elimina todas las tecnologias secundarias
	    $result = $tableMap->erase(array("idproyecto =?", $proy->idproyecto));

		// Inserta las tecnologias secundarias en la tabla		
		if (!empty($tecnologias2)) {
			foreach ($tecnologias2 as $idtecnologia) {
				// No debe insertar una tecnologia si es la principal
				if ($tecnologia == $idtecnologia) continue;
				$tableMap->reset();
				$tableMap->idproyecto = $proy->idproyecto;
				$tableMap->idtecnologia = $idtecnologia;
				$tableMap->save();
			} // End forech
		}
	} // End _setRelationshipTables
	
	private function _existProyectCode ($tableMap, $code, $id=null) {
		if (!is_null($id)) 
			$result = $tableMap->dbRead(array("idproyecto !=? and codigo =?",$id, $code));
		else 
			$result = $tableMap->dbRead(array("codigo=?", $code));
		return !empty($result);  
	}
	
	private function _setButtonsVars () {
		$crud = $this->_f3->get('SESSION.'.Controller::USR_CRUD);
		$this->_f3->set ('crud', $crud);
		$usrP = $this->_f3->get('SESSION.'.Controller::USR_PROJECTS);
		$this->_f3->set ('userProjects', $usrP);
		// IsSuperUSer?
		$superUsuario = $this->_f3->get('SESSION.'.Controller::USR_TIPO) == Controller::TUSR_SUPER_MODIFICACION;
		$this->_f3->set ('superUser', $superUsuario);
	} 
	
} // End class
