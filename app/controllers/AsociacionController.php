<?php
/*
 * AsociacionController
 * 
 * Se encarga de la Administración de las clasificaciones de las lecciones según los criterios
 * de asociación.
 * 
 * Utiliza: 	
 * 			Reporte: /asociacion/read.html
 * 			Filtro:	 F2 * * * 
 *  
 * 
 */
class AsociacionController extends ModuleMainController 
{

	// const separador = ","; // ","PHP_EOL
	const separador = PHP_EOL;
	private $_pageTitle = 'Asociación de lecciones';
	private $_viewNavigation = "asociacion/nav.html";
	private $_htmlView = "/asociacion/read.html";
	
	/*
	 * Read
	 * Use F2 filter 
	 */
	public function read () 
	{
		// Construct the report filter
		$filterObject = new ReporteFiltro ("F2");
		$filter = $filterObject->filter;
		$options = $filterObject->options;
		
		// Create view Mapp		
		$vmap = new ViewMapper ($this->_db, "view_R2", "idleccion");
		// Get filtered rows
		$lecciones = $vmap->dbRead($filter, $options);
		$this->_f3->set("lista",$lecciones);

		$criterios = $this->_getForeignKeyRows();
		
    	$op = ($this->_f3->exists('PARAMS.msg') && $this->_f3->get('PARAMS.msg')=='export')?'export':'read';
        switch ($op) {
			case 'read':
		        $this->_f3->set('page_head',$this->_pageTitle);    
		        $this->_f3->set('page_subhead','Por criterio de consolidación');    
		    	$this->_f3->set ('navigation',$this->_viewNavigation);	
		    	$this->_f3->set ('view',$this->_htmlView);	
				$this->_f3->set ('op','read'); 
				$this->_setButtonsVars();
				break;
			case 'export':
				$this->export_cri($lecciones, $criterios);
				exit(); // To stop routing
			default:
				exit();
				break;
		} // Swicth
		
	} // End read
	
	/*
	 * Exporta lecciones NO globales y su criterio de agrupación
	 *
	 * */ 
	public function export_cri ($lecciones, $criterios) {
    	$delimiter = $this->_f3->get('export_delimiter');
    	$company =  $this->_f3->get('company_name');
    	$system =  $this->_f3->get('system_name');
		$dbname = $this->_f3->get('db_name');
		$lfmt = $this->_f3->get('leccion_id_format');
		
		$list = array();
		$list[] = array("$company - $system");
		$list[] = array("Listado de Lecciones Aprendidas con el criterio de asociación");
		$list[] = array("Criterio","ID","Año","Area de Conocimiento","Valoración","Descripción",
		"Lección Aprendida","Proyecto","Estado Proyecto","Tipo de proyecto","Tipo de servicio","Cliente",
		"Mercado","Ubicación","País","Arquitectura","Tecnología Principal","Base de datos");
		// Get leccion list
		foreach ($lecciones as $elem) {
			// Busca el nombre del criterio
			foreach ($criterios as $cri) {
				if ($elem->idcriterio == $cri['idcriterio']) {
				   	$nombre_criterio = $cri['nombre'];
					break;
				}
			} // End for
	        $list[] = array($nombre_criterio,sprintf ($lfmt,$elem->idleccion),$elem->ano,$elem->areac,
	        $elem->valoracion=='P'?'POSITIVA':'NEGATIVA',$elem->descripcion,$elem->leccion,
	        $elem->proyecto,$elem->estado?"ABIERTO":"CERRADO",$elem->tproyecto,$elem->tservicio,
	        $elem->cliente,$elem->mercado,$elem->ubicacion,$elem->pais,$elem->arquitectura,
	        $elem->tecnologia, $elem->basedatos);  
		}
		array2csvDownload ($list, 'leccionesxcriterio.csv', $delimiter);
	} // End export

	
	private function _setButtonsVars ()
	{
		$crud = $this->_f3->get('SESSION.'.Controller::USR_CRUD);
		$this->_f3->set ('crud', $crud);
		$usrP = $this->_f3->get('SESSION.'.Controller::USR_PROJECTS);
		$this->_f3->set ('userProjects', $usrP);
		// IsSuperUSer?
		$superUsuario = $this->_f3->get('SESSION.'.Controller::USR_TIPO) == Controller::TUSR_SUPER_MODIFICACION;
		$this->_f3->set ('superUser', $superUsuario);
	} 

	private function _getForeignKeyRows () 
	{
		// Get related table fields (from Foreign Keys) for corrent table
		// criterios
		$tableMap = new TableMapper ($this->_db,'criterios','idcriterio');
		$temp = objectsToArray ($tableMap->dbRead(), 'idcriterio,nombre,color,orden'); 
		array_unshift ($temp, array('idcriterio'=>null,'nombre'=>'N/A', 'color'=>'FFFFFF','orden'=>100000));
		$this->_f3->set ('criterios', $temp);
		return $temp;
	} // End _getForeignKeyRows	

	public function update ()  
	{
	    if($this->_f3->exists('POST.update')) {
			// New lecciones
		    $lec = new TableMapper ($this->_db,'lecciones', 'idleccion');
			$lec->load(array('idleccion=?',$this->_f3->get('POST.idleccion')));
			$oldCri = $lec->idcriterio;
			$newCri = $this->_f3->get('POST.idcriterio');
			if (empty ($newCri) || is_null($newCri)) $newCri = null;
			$lec->idcriterio = $newCri;
			if (is_null($lec->idcriterio)) $lec->lecglobal = null; // Unparent the leccion
	        $lec->update();
			
			// Now shows the filter and report			
			$this->_info_message[] = "Se ha aplicado el criterio de asociación a la lección.";
			$this->_f3->set('SESSION.'.Controller::INFO_MESSAGE, $this->_info_message);
			$this->_f3->set('PARAMS.msg','msg');
			
			$this->read();
	    }
	} // End update

	/* STATIC
	 * recreateLeccionesGlobales
	 * 
	 * 	@$this_db		DB conecction
	 * 
	 * 	1. 	Delete all rows from lglobals
	 * 	2. 	Delete global lecciones from lecciones 
	 * 	3. 	For each criterio on table "criterios" create a Global leccion and the coresponding
	 * 		record on lglobals.
	 * 
	 */	
    static public function recreateLeccionesGlobales ($controller) 
	{
		// Save the "Leccion Aprendida" text for every Global leccion in an array, if any
		$lecMap = new TableMapper ($controller->_db,'lecciones', 'idleccion');
		$lecciones = $lecMap->dbRead(array("esglobal=?", 1));
		$leccionText = array();
		foreach ($lecciones as $lec)
			$leccionText [$lec->idcriterio] = $lec->leccion;
		unset ($lecMap, $lec, $lecciones);
				
		// Delete all FROM lglobals AND all Global leccion from lecciones
		// In an sql Batch Transaction
		$result = $controller->_db->exec(
		    array(
		        'DELETE FROM lglobales',
		        'DELETE FROM lecciones WHERE lecciones.esglobal = 1'
			    )
		);
		
		// Create a list of criterios
		$criMap = new TableMapper ($controller->_db,'criterios', 'idcriterio');
		$criMap->dbRead(array(),array('order'=>'idcriterio ASC'));
		// For evey criterio =>
		while (!$criMap->dry()) {
			$idcri = $criMap->idcriterio;
			// Get all non Global lecciones associated with idcri
			$lec = new TableMapper ($controller->_db,'lecciones', 'idleccion');
			$lecciones = $lec->dbRead(array("idcriterio=? and esglobal=?", $idcri,0));
			$numlec = count ($lecciones);
			
			if ($numlec<2) {
				$criMap->skip();
				continue;
			} 
			
			// Create new GLOBAL lec in lecciones
			$lecGlo = new TableMapper ($controller->_db,'lecciones', 'idleccion');
			$lecGlo->idproyecto = null;
			$lecGlo->valoracion = $lecciones[0]->valoracion; // valoracion from First leccion 
			$lecGlo->ano = $lecciones[0]->ano;		  // ano from First leccion 
			$lecGlo->areac = null;
			$lecGlo->descripcion = null;
			$lecGlo->leccion = null;  // Leccion Text
			$lecGlo->idcriterio = $idcri;
			$lecGlo->lecglobal = null; // Is global
			$lecGlo->esglobal = 1;
			$lecGlo->save();
			$idleccionGlobal = $lecGlo->idleccion;
			// Set the leccion Text
			if (!empty($leccionText) && !empty($leccionText [$idcri]))
				$lecGlo->leccion = $leccionText [$idcri];  // Leccion Text
			$lecGlo->update();
	
			// Create a row in lglobales
			$lglo = new TableMapper ($controller->_db,'lglobales', 'idlglobales');
			$lglo->idleccion = $lecGlo->idleccion;  // The brand new created leccion
			$lglo->anos = "";
			$lglo->proyectos = "";
			$lglo->tservicios = "";
			$lglo->tproyectos = "";
			$lglo->clientes = "";
			$lglo->mercados = "";
			$lglo->ubicaciones = "";
			$lglo->paises = "";
			$lglo->arquitecturas = "";
			$lglo->tecnologias = "";
			$lglo->basesdatos = "";
			$lglo->save();
	
			// Reconstruct Global Lec 
			// For every leccion associated with criterio $idcri
			// Update GLOBAL lec with all lecciones associated with criterio idcri
			while (!$lec->dry()) {
				// Get the proyect info for the leccion to be associated to the GLOBAL
				$pinfo = new ViewMapper ($controller->_db,'view_proyectos', 'idproyecto');
				$pinfo->load(array("idproyecto=?", $lec->idproyecto));
				if ($pinfo->dry()) trigger_error("AsociacionController->_addToGlobalLec: No se encontró idleccion=".$lec->idproyecto." en view_proyectos");
				
				// Append the leccion info into the GLOBAL leccion
				$lglo->anos = self::_insertStringToList ($lec->ano, $lglo->anos);
				$lglo->lecciones = self::_insertStringToList (sprintf ($controller->_f3->get('leccion_id_format'),$lec->idleccion), $lglo->lecciones);
				$lglo->proyectos = self::_insertStringToList ($pinfo->codigo, $lglo->proyectos);
				$lglo->tservicios = self::_insertStringToList ($pinfo->tservicio,$lglo->tservicios);
				$lglo->tproyectos = self::_insertStringToList ($pinfo->tproyecto,$lglo->tproyectos);
				$lglo->clientes = self::_insertStringToList ($pinfo->cliente,$lglo->clientes);
				$lglo->mercados = self::_insertStringToList ($pinfo->mercado,$lglo->mercados);
				$lglo->ubicaciones = self::_insertStringToList ($pinfo->ubicacion,$lglo->ubicaciones);
				$lglo->paises = self::_insertStringToList ($pinfo->pais,$lglo->paises);
				$lglo->arquitecturas = self::_insertStringToList ($pinfo->arquitectura,$lglo->arquitecturas);
				$lglo->tecnologias = self::_insertStringToList ($pinfo->tecnologia,$lglo->tecnologias);
				$lglo->basesdatos = self::_insertStringToList ($pinfo->basedatos,$lglo->basesdatos);
				$lglo->update();
				
				$lec->lecglobal = $idleccionGlobal;
				$lec->update();
				$lec->skip();
			} // End while !$lec->dry()			
			//---------------------------------------
			
			$criMap->skip();
		} // End while !$criterios->dry()
	} // recreateLeccionesGlobales

	/*
	 * 	readGlobales
	 * 		Display a list of global lecciones
	 */
	public function readGlobales () {

		// Recreate lecciones Globales in tables: lglobales and lecciones
		$this->recreateLeccionesGlobales($this);
		
    	// New ViewProyecto
        $viewLecciones = new ViewMapper($this->_db,'view_lecciones','idleccion');
		$lecciones = $viewLecciones->dbRead(array("esglobal=?",1), array('order'=>'criterio_orden ASC'));
        $this->_f3->set('lista',$lecciones);

    	$op = ($this->_f3->exists('PARAMS.msg') && $this->_f3->get('PARAMS.msg')=='export')?'export':'read';
        switch ($op) {
			case 'read':
		        $this->_f3->set('page_head',$this->_pageTitle);    
		        $this->_f3->set('page_subhead','Listar lecciones globales');	// Title    
		        $this->_f3->set('navigation',$this->_viewNavigation);	
		        $this->_f3->set('view','asociacion/readGlobales.html');	
		        $this->_f3->set('op','readGlobales');
				break;	
			case 'export':
				$this->export_glo($lecciones);
				exit(); // To stop routing
			default:
				exit();
				break;
		} // Swicth
	} // End readGlobales

	/*
	 * 
	 * Exporta lecciones GLOBALES y su criterio de agrupación
	 *
	 * Para las leciones Globales, los campos string que agrupan varios elementos,
	 * se deben separar por un @caracter_separador, de manera que al exportar a CSV  
	 * puedan ser diferenciados. Originalmete estos varios elemnetos, se guardan en la
	 * base de datos separados por PHP_EOL. 
	 */ 
	public function export_glo ($lecciones) {
		$caracter_separador = "|";
		
    	$delimiter = $this->_f3->get('export_delimiter');
    	$company =  $this->_f3->get('company_name');
    	$system =  $this->_f3->get('system_name');
		$dbname = $this->_f3->get('db_name');
		$lfmt = $this->_f3->get('leccion_id_format');
		
		$list = array();
		$list[] = array("$company - $system");
		$list[] = array("Listado de Lecciones Aprendidas globales");
		$list[] = array(
		"Criterio",
		"ID Global",
		"ID Lecciones que agrupa",
		"Año",
		"Area de Conocimiento",
		"Valoración",
		"Lección Aprendida",
		"Proyectos",
		"Tipos de proyectos",
		"Tipos de servicios",
		"Clientes",
		"Mercados",
		"Ubicaciones",
		"Paises",
		"Arquitecturas",
		"Tecnología Principal",
		"Bases de datos");

		// Get leccion list
		foreach ($lecciones as $elem) {
	        $list[] = array($elem->criterio,
	        sprintf ($lfmt,$elem->idleccion),
	        str_replace(PHP_EOL,"|",$elem->lecciones),
	        $elem->ano,
	        'GLOBAL',
	        $elem->valoracion=='P'?'POSITIVA':'NEGATIVA',
	        $elem->leccion,
	        str_replace(PHP_EOL,$caracter_separador,$elem->proyectos),
	        str_replace(PHP_EOL,$caracter_separador,$elem->tproyectos),
	        str_replace(PHP_EOL,$caracter_separador,$elem->tservicios),
	        str_replace(PHP_EOL,$caracter_separador,$elem->clientes),
	        str_replace(PHP_EOL,$caracter_separador,$elem->mercados),
	        str_replace(PHP_EOL,$caracter_separador,$elem->ubicaciones),
	        str_replace(PHP_EOL,$caracter_separador,$elem->paises),
	        str_replace(PHP_EOL,$caracter_separador,$elem->arquitecturas),
	        str_replace(PHP_EOL,$caracter_separador,$elem->tecnologias),
	        str_replace(PHP_EOL,$caracter_separador,$elem->basesdatos));  
		}
		array2csvDownload ($list, 'lecciones_globales.csv', $delimiter);
	} // End export

	/*
	 * 	updateGlobales
	 * 		Update the "leccion" field of a selected global leccion	
	 */
	public function updateGlobales () {
		$idleccion = $this->_f3->get('POST.idleccion');
		// Map Lecciones		
		$lec = new TableMapper ($this->_db,'lecciones', 'idleccion');
	    if($this->_f3->exists('POST.updateGlobales')) {
	    	// Map lecciones
			$lec->dbUpdate ($idleccion);

	        $this->_info_message[] = "La lección fue actualizada.";
			$this->_f3->set('SESSION.'.Controller::INFO_MESSAGE,$this->_info_message);
	        $this->_f3->reroute('/asociacion/readGlobales/msg');
			return;
		} else {
			$areac_nombre = $this->_f3->get('POST.areac');
			$lec->dbGetById($idleccion); // => Populate POST
			// Get areac nombre 
			$this->_f3->set('POST.areac_nombre',$areac_nombre);
		}
		
        // Header, subheader and view
        $idfmt = $this->_f3->get('leccion_id_format');
        $this->_f3->set('page_head',$this->_pageTitle);    
        $this->_f3->set('page_subhead','Modificar lección global: ' . sprintf($idfmt,$idleccion));	// SubTitle    
    	$this->_f3->set('navigation',$this->_viewNavigation);	
        $this->_f3->set('view','asociacion/updateGlobales.html');
        $this->_f3->set('op','updateGlobales');
        $this->_f3->set('disableMainMenu',1);
	} // updateGlobales 

	// Insert $str in a CSV list and re-sort the list
	static private function _insertStringToList ($str, $list) {
		$l = $list;
		$glue = self::separador;
		$list = trim ($list);
		if (empty($list))
			$list = $str;
		else {
			if (strpos ($list, $str) === false) // If $str not in $list
				$list .= $glue.$str;
		}
		$arr = explode ($glue, $list);	
		sort ($arr);

		$list = join ($glue, $arr);
		return $list;
	} //  _insertStringToList

} // End class AsociacionController

