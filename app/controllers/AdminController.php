<?php
/*
 * AdminController
 * 	Se encarga de la Administración de todas las tablas básicas del sistema
 *  con excepción de la de usuarios.
 * 
 */
class AdminController extends ModuleAdminController {
	
	private $_viewNavigation = "admin/nav.html";
	protected $_view = "admin/tablasbasicas.html";
	private $_tableKeys = array (
		"areasc"=>"idareac",
		"arquitecturas"=>"idarquitectura",
		"tecnologias"=>"idtecnologia",
		"basesdatos"=>"idbasedatos",
		"clientes"=>"idcliente",
		"mercados"=>"idmercado",
		"tproyectos"=>"idtproyecto",
		"tservicios"=>"idtservicio",
		"paises" => "idpais",
		"ubicaciones"=>"idubicacion",   // Necesita datos de Foreign Table
		"criterios"=>"idcriterio"		// Tiene un campo más: Color.
	);
	
    public function menu()
    {
        $this->_f3->set('page_head','Administración del sistema');	// Title    
        $this->_f3->set('page_subhead','Menú principal');	// Title    
        $this->_f3->set('navigation',$this->_viewNavigation);	
        $this->_f3->set('view','admin/menu.html');	
        $this->_f3->set('op','menu');
	}

    public function tableCRUD () {
    	// Get the routing parameters (@table, @op): from GET|POST /admin/@table/@op)
    	if ($this->_f3->exists ('PARAMS.table')) {
    		$tableName = $this->_f3->get ('PARAMS.table');
		} else {
			$this->_f3->reroute('/admin');
			return;
		}
    	if ($this->_f3->exists ('PARAMS.op')) {
    		$operation = $this->_f3->get ('PARAMS.op');
		} else 
			$operation = 'read';

		// Set general Template variables
		$this->_f3->set('tableName', $tableName);    
        $this->_f3->set('tableKeys', $this->_tableKeys[$tableName]);    
        $this->_f3->set('page_head',"Administración de la tabla: [$tableName]");    
        $this->_f3->set('page_subhead','');
        $this->_f3->set('navigation',$this->_viewNavigation);	
        $this->_f3->set('view',$this->_view);
        
        $tmap = new TableMapper ($this->_db, $tableName, $this->_tableKeys[$tableName]);
		switch ($operation) {
			case "create":
			    if ($this->_f3->exists('POST.create')) {
			    	$name = $this->_f3->get('POST.nombre');
			    	if (!$this->_existNameInTable ($tmap, $this->_tableKeys[$tableName], $name)) {
				        $tmap->dbCreate();  // Create new from POST (hive array)
				        
				        $this->_info_message[] = "El registro fue creado.";
						$this->_f3->set('SESSION.'.Controller::INFO_MESSAGE, $this->_info_message);
			  	        $this->_f3->reroute("/admin/$tableName/read/msg");
						return;
					} else
						$this->_error_message[] = "El valor '$name' ya existe en la tabla ". strtoupper($tableName).". El registro no fue creado.";
			    } else { // Get related table fields (from Foreign Keys) for corrent table 
			        if ($tableName == "ubicaciones") 
			        	$this->_getForeignKeyRows ();
				}	
		        $this->_f3->set('disableMainMenu',1);
				break;
			case "update":
				if (!$this->_f3->exists("POST.tableid")) {
					$this->_error_message[] = "En AdminController::tableCRUD. No existe POST.tableid";
					$this->_f3->set('SESSION.'.Controller::ERROR_MESSAGE, $this->_error_message);
					$this->_f3->reroute("/admin/$tableName/read/error");
					return;
				}
				$id = $this->_f3->get("POST.tableid");
			    if ($this->_f3->exists('POST.update')) {
			    	$name = $this->_f3->get('POST.nombre');
			    	if (!$this->_existNameInTable ($tmap, $this->_tableKeys[$tableName], $name, $id)) {
				        $tmap->dbUpdate($id); // Update de proyect

				        $this->_info_message[] = "El registro fue actualizado.";
						$this->_f3->set('SESSION.'.Controller::INFO_MESSAGE, $this->_info_message);
						$this->_f3->reroute("/admin/$tableName/read/msg");
						return;
					} else
						$this->_error_message[] = "El valor '$name' ya existe en la tabla ". strtoupper($tableName).". El registro no fue actualizado.";
			    } else { // POST: Editar/Modificar proyeto
			        $tmap->dbGetById($id); // => Populate POST
			        if ($tableName == "ubicaciones") 
			        	$this->_getForeignKeyRows ();				
				}
		        $this->_f3->set('disableMainMenu',1);
				break;
			case "delete":
			    if ($this->_f3->exists("POST.tableid")) {
					$id = $this->_f3->get("POST.tableid");
					// Check if is possible to delete $id
					try {
				        $tmap->dbDelete($id);
				        $this->_info_message[] = "El registro fue eliminado.";
						$this->_f3->set('SESSION.'.Controller::INFO_MESSAGE, $this->_info_message);
						$param = 'msg';
					} catch (Exception $e) {
						$msg = strtolower($e->getMessage());
						if (strpos($msg,"foreign key constraint") === false) 
							throw $e;
				        $this->_error_message[] = "No fue posible eliminar el registro, porque está siendo referenciado en otra tabla (Restricción de llave foránea).";
						$this->_f3->set('SESSION.'.Controller::ERROR_MESSAGE, $this->_error_message);
						$param = 'error';
					}
					$this->_f3->reroute("/admin/$tableName/read/$param");
					return;
			    }
				break;
		} // end switch
		$this->_f3->set('op',$operation);
		// READ
		if ($tableName == "ubicaciones") {
			$tmap = new ViewMapper ($this->_db, "view_ubicaciones", $this->_tableKeys[$tableName]);
			$this->_f3->set("lista",$tmap->dbRead());
		} else {
			if ($tableName == "criterios")
			   $order = array('order'=>"orden ASC");
			else
			   $order = array('order'=>"nombre ASC");
			$this->_f3->set("lista",$tmap->dbRead('',$order));
		}

		$this->_f3->set(Controller::ERROR_MESSAGE, $this->_error_message);
		$this->_f3->set(Controller::INFO_MESSAGE, $this->_info_message);
	} //end function tableCRUD 

	protected function _getForeignKeyRows () 
	{
		// Get related table fields (from Foreign Keys) for corrent table
		// Paises 
		$tableMap = new TableMapper ($this->_db,'paises','idpais');
		$temp = objectsToArray ($tableMap->dbRead(), 'idpais,nombre'); 
		$this->_f3->set('paises', $temp); 
	}
	
	/*
	 * _existNameInTable
	 * Check if a value ($name) for the column "name" is duplicated in the table ($tableMap)
	 * @tableMap	OBJECT 
	 * @tableKey 	STRING	table Key
	 * @name		STRING	value for the name column
	 * @id			STRING 	value for the table Key 
	 */
	protected function _existNameInTable ($tableMap, $tableKey, $name, $id=null) {
		if (!is_null($id)) 
			$result = $tableMap->dbRead(array("$tableKey !=? and nombre =?",$id, $name));
		else
			$result = $tableMap->dbRead(array("nombre =?", $name));
		return !empty($result);  
	}
} // End class AdminController

