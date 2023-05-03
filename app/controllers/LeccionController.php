<?php
class LeccionController extends ModuleMainController {

	private $_viewNavigation = "leccion/nav.html";// Template to navigate
	private $_tableName = 'lecciones';  
	private $_tableKeys = 'idleccion';  
	private $_SQLtableKeys = 'idleccion=?';
	  
    // A continuación el CRUD
    
    /* @read
	 * 	Read and export leccion list
	 */
    public function read()
    {
		// Recreate lecciones Globales in tables: lglobales and lecciones
		AsociacionController::recreateLeccionesGlobales($this);
		
		/* Check POST variables and set SESSION variables for the actual proyect
		*  This is the first&only place where SESSION.idproy is set
		 * all the methods that depends on the idproyecto must get the id from the SESSION var. 
		*/
		if ($this->_f3->exists('POST.idproyecto')) {
			$proy = $this->_f3->get('POST.idproyecto');
			$codigoproy = $this->_db->exec ('SELECT codigo FROM proyectos WHERE idproyecto = "'.$proy.'"');
			if (empty($codigoproy) || empty($codigoproy[0]) || empty ($codigoproy[0]['codigo'])) {
				$this->_error_message[] = "Error: LeccionControler->read. El código del proyecto es vacío.";
				$this->_f3->set('SESSION.'.Controller::GEN_ERROR_MESSAGE, $this->_error_message);
				$this->_f3->reroute("/leccion/read/error");
				return;
			}
			$codigoproy = $codigoproy[0]['codigo'];
			$this->_f3->set('SESSION.idproy',$proy);
			$this->_f3->set('SESSION.codigoproy',$codigoproy);
		} elseif ($this->_f3->exists('SESSION.idproy')) {
			// In the case when a lection is created or updated 
			$proy = $this->_f3->get('SESSION.idproy');
			$codigoproy = $this->_f3->get('SESSION.codigoproy');
		} else {
			$this->_error_message[] = "Error: LeccionControler->read. No existe SESSION.idproy.";
			$this->_f3->set('SESSION.'.Controller::GEN_ERROR_MESSAGE,$this->_error_message);
			$this->_f3->reroute("/leccion/read/error");
			return;
		}

		// New ViewLeccion
        $viewLeccion = new ViewMapper($this->_db,'view_lecciones','idleccion');
		
		// Load Lecciones Positivas and then Negativas
		$options = array('order'=>'areac ASC');
		$filter = array('idproyecto=? and valoracion=?',$proy,'P');
		$leccionesPos = $viewLeccion->dbRead($filter, $options);
		$this->_f3->set('leccionesPos', empty($leccionesPos)?false:$leccionesPos);  // Lecciones list
		
		$filter = array('idproyecto=? and valoracion=?',$proy,'N');
		$leccionesNeg = $viewLeccion->dbRead($filter, $options);
        $this->_f3->set('leccionesNeg', empty($leccionesNeg)?false:$leccionesNeg);  // Lecciones list

    	$op = ($this->_f3->exists('PARAMS.msg') && $this->_f3->get('PARAMS.msg')=='export')?'export':'read';
        switch ($op) {
			case 'read':
		        $this->_f3->set('op','read');
		        $this->_f3->set('page_head','Lecciones - ');			// Title    
		        $this->_f3->set('page_subhead','Proyecto: ' . $codigoproy);	// SubTitle    
		        $this->_f3->set('navigation',$this->_viewNavigation);
		        $this->_f3->set('view','leccion/read.html');			// Template to list Proyects
				// To select which buttons should be allowed to the User
				$this->_setButtonsVars ($proy);
		        break;	
			case 'export':
				$this->export($codigoproy,$leccionesPos,$leccionesNeg);
				exit(); // To stop routing
			default:
				break;
		} // Swicth
    } // end read

   	public function export($codigoproy,$leccionesPos,$leccionesNeg)
    {
    	$delimiter = $this->_f3->get('export_delimiter');
    	$company =  $this->_f3->get('company_name');
    	$system =  $this->_f3->get('system_name');
		$dbname = $this->_f3->get('db_name');
		$list = array();
		$list[] = array("$company - $system");
		$list[] = array("Listado de Lecciones del proyecto: $codigoproy");
		$heading = array("ID","Año","Area de Conocimiento","Descripción","Lección aprendida","Otros proyectos asociados");
		// Get project list
		foreach (array(1,2) as $index) {
			if ($index == 1) {
				$list[] = array("Lecciones Positivas");
				$list[] = $heading;
			 	$lecciones = $leccionesPos;
			} elseif ($index == 2) {
				$list[] = array("Lecciones Negativas");
				$list[] = $heading;
			 	$lecciones = $leccionesNeg;
			}
			foreach ($lecciones as $elem) {
        		$list[] = array($elem->idleccion,$elem->ano,$elem->areac,$elem->descripcion,$elem->leccion,$elem->proyectos);
			}  
		} // end foreach
		
		array2csvDownload ($list, 'lecciones.csv', $delimiter);
	} // End Export
		
	public function create()
	{
		// Check SESSION variable
		if ($this->_f3->exists('SESSION.idproy')) {
		   	$proy = $this->_f3->get('SESSION.idproy');
			$codigoproy = $this->_f3->get('SESSION.codigoproy');
		} else {
			$this->_error_message[] = "Error: LeccionControler->create. No existe la variable de SESION idproyecto.";
			$this->_f3->set('SESSION.'.Controller::GEN_ERROR_MESSAGE, $this->_error_message);
			$this->_f3->reroute("/leccion/read/error");
			return;
		}
		
	    if ($this->_f3->exists('POST.create')) {
	    	// New leccion
	        $lecc = new TableMapper ($this->_db, $this->_tableName, $this->_tableKeys);
	        $lecc->dbCreate(); // Create new from POST (hive array)

			$this->_info_message[] = "La lección fue creada.";
			$this->_f3->set('SESSION.'.Controller::INFO_MESSAGE,$this->_info_message);			
	 		$this->_f3->reroute('/leccion/read/msg');
			return;
	    } else {
	        // Get related table fields (from Foreign Keys) for corrent table
	        $this->_getForeignKeyRows();	
	    	
			$this->_f3->set('idproyecto',$proy);
	        $this->_f3->set('page_head','Crear Lección - ');
	        $this->_f3->set('page_subhead','Proyecto: ' . $codigoproy);	// SubTitle    
	        $this->_f3->set('navigation',$this->_viewNavigation);
	        $this->_f3->set('view',"leccion/create.html");
	        $this->_f3->set('op','create');
	        $this->_f3->set('disableMainMenu',1);

			// To select which buttons should be allowed to the User
			$this->_setButtonsVars ($proy);
	    }
	} // End create 
	
	public function update()
	{
		$idleccion = $this->_f3->get('POST.idleccion');
		// New Leccion
	    $lec = new TableMapper ($this->_db, $this->_tableName, $this->_tableKeys);
	    if($this->_f3->exists('POST.update'))
	    {
	        $lec->dbUpdate($idleccion); // Update de proyect

	        $this->_info_message[] = "La lección fue actualizada.";
			$this->_f3->set('SESSION.'.Controller::INFO_MESSAGE,$this->_info_message);
			$this->_f3->reroute('/leccion/read/msg');
			return;
	    } else // POST : Editar/Modificar proyeto
	    {
	        $lec->dbGetById($idleccion); // => Populate POST
	        
	        // Get related table fields (from Foreign Keys) for corrent table
	        $this->_getForeignKeyRows();	
			
	        // Header, subheader and view
	        $proy = $this->_f3->get('SESSION.idproy');
	        $codigoproy = $this->_f3->get('SESSION.codigoproy');
			
	        $this->_f3->set('page_head','Modificar Lección - ');
	        $idfmt = $this->_f3->get('leccion_id_format');
			$this->_f3->set('page_subhead','Lección: ' . sprintf($idfmt,$idleccion) . " (Proyecto $codigoproy)");	// SubTitle    
	        $this->_f3->set('navigation',$this->_viewNavigation);
	        $this->_f3->set('view','leccion/update.html');
	        $this->_f3->set('op','update');
	        $this->_f3->set('disableMainMenu',1);

			// To select which buttons should be allowed to the User
			$this->_setButtonsVars ($proy);
	    }
	} // end update

	public function delete()
	{
		$param = "";
	    if($this->_f3->exists('POST.idleccion'))
	    {
	    	// New Leccion
	        $lec = new TableMapper ($this->_db, $this->_tableName, $this->_tableKeys);
	        $lec->dbDelete($this->_f3->get('POST.idleccion'));

			$this->_info_message[] = "La lección fue eliminada.";
			$this->_f3->set('SESSION.'.Controller::INFO_MESSAGE,$this->_info_message);
			$param = "/msg";
	    }
		$this->_f3->reroute('/leccion/read'.$param);
		return;
	}

	private function _setButtonsVars ($idproyecto) {
		$crud = $this->_f3->get('SESSION.'.Controller::USR_CRUD);
		$this->_f3->set ('crud', $crud);
		$usrP = $this->_f3->get('SESSION.'.Controller::USR_PROJECTS);
		$this->_f3->set ('userProjects', $usrP);
		// IsSuperUSer?
		$superUsuario = $this->_f3->get('SESSION.'.Controller::USR_TIPO) == Controller::TUSR_SUPER_MODIFICACION;
	    $buttonsArea = 0; // If the table must display CRUD buttons
		if ($superUsuario || in_array($idproyecto,$usrP)) 
			$buttonsArea = ($crud['U']+$crud['D']+$crud['A'])>0?1:0;
		$this->_f3->set ('buttonsArea', $buttonsArea);
		$this->_f3->set ('superUser', $superUsuario);
	}
		
	private function _getForeignKeyRows () 
	{
		// Get related table fields (from Foreign Keys) for corrent table
		// AreasC (Areas de conocimiento)
		// New Areac
		$tableMap = new TableMapper ($this->_db, 'areasc', 'idareac');
		$temp = objectsToArray ($tableMap->dbRead(), 'idareac,nombre'); 
		$this->_f3->set('areasc', $temp);
		 
		// Create an array of 10 years with @year as the center value
		$year = $this->_f3->get('year');
		$anos = array();
		for ($k=-5; $k<=5; $k++) $anos[] = (string) $year+$k; 
		$this->_f3->set('anos', $anos);
	}

	/*
	 * @infoLeccion
	 * Despliega la información asociada con una lección.
	 * 
	 * Entradas: PARAMS.id (Method Get)
	 * Usa la vista: view_R2 
	 */
	public function infoLeccion () {
		if (!$this->_f3->exists('PARAMS.id')) {
			$this->_error_message[] = "Error: LeccionControler->infoLeccion. No existe GET.param.";
			$this->_f3->set('SESSION.'.Controller::GEN_ERROR_MESSAGE,$this->_error_message);
			$this->_f3->reroute("/asociacion/readGlobales/error");
			return;
		}
		$idlec = (integer) trim ($this->_f3->get('PARAMS.id'));
		// New ViewLeccion
        $viewLeccion = new ViewMapper($this->_db,'view_R2','idleccion');
		// Load and save leccion info
		$leccion = $viewLeccion->dbRead(array('idleccion=?',$idlec));
		if (empty($leccion[0])) {
			$this->_error_message[] = "Error: LeccionControler->infoLeccion. leccion[0] = empty";
			$this->_f3->set('SESSION.'.Controller::GEN_ERROR_MESSAGE,$this->_error_message);
			$this->_f3->reroute("/asociacion/readGlobales/error");
			return;
		}
        $this->_f3->set('leccion',$leccion[0]);
		unset ($leccion);
		
		$this->_viewNavigation = '';// Template to navigate
	    $this->_f3->set('main_navigation','');
	    $this->_f3->set('page_head','Información sobre la lección');
        $this->_f3->set('page_subhead',"id: $idlec");
    	$this->_f3->set ('navigation','');
    	$this->_f3->set ('view','leccion/info.html');
		$this->_f3->set ('op','info');
	} // End infoLeccion
} // End class
