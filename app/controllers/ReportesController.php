<?php
/*
 * ReportesController
 * 	Se encarga de la Administración de los reportes del sistema
 * 
 */
class ReportesController extends ModuleMainController 
{
	protected $_pageTitle = 'Reportes';
	/*
	 * REPORTES
	 * 
	 */
	protected $_reportes = array (
		/* 
		 * R1:
		 * 	LECCIONES GLOBALES Y NO GLOBALES 
		 *  - Filtro por valores (nombre) no códigos (keys) : r_filtro1 * * *
		 *  - Todas las lecciones: globales y no globales
		 *  - Ordenadas por 
		 * 		1.Criterio-Orden DESC, Esglobal DESC, 3.Valoracion DESC, 4.Ano DESC, 5.Leccion ASC
		 */
		"R1"=>array ("title"=>"Lecciones aprendidas consolidadas", 
					 "view"=>"view_R1", 
					 "keys"=>"idleccion",
					 "htmlView"=>"/reportes/r1.html",
					 "filterCode"=>"F1"),
		/* 
		 * R2:
		 *  A MANERA DE EJEMPLO DE UN SEGUNDO REPORTE
		 * 	* NO SE USA EN EL MOMENTO
		 * 
		 * 	LECCIONES NO GLOBALES 
		 *  - Filtro por códigos (keys) : r_filtro2 * * *
		 *  - Lecciones NO Globales, 
		 * 	- Ordenadas por 1.Criterio-Orden ASC, 2.Valoración DESC, 3.Año DESC, y 4.Lección ASC
		 */
		"R2"=>array ("title"=>"Lecciones aprendidas", 
					 "view"=>"view_R2", 
					 "keys"=>"idleccion",
					 "htmlView"=>"/reportes/r2.html",
					 "filterCode"=>"F2"),
	);  
	protected $_viewNavigation = '';
	protected $_view = '';
	protected $_reportCode = '';

	public function report () 
	{
		if ($this->_f3->exists ('PARAMS.report')) {
			$this->_reportCode = $this->_f3->get ('PARAMS.report');
			if (array_key_exists($this->_reportCode,$this->_reportes)) 
				$this->_reportCode = $this->_f3->get ('PARAMS.report');
			else {
				$this->_f3->reroute('/reportes');
				return;
			} 
		}
		else  {
			$this->_f3->reroute('/reportes');
			return;
		}	
		
		// Recreate lecciones Globales in tables: lglobales and lecciones
		AsociacionController::recreateLeccionesGlobales($this);

		$viewName = $this->_reportes[$this->_reportCode]['view'];
		$viewTableKeys = $this->_reportes[$this->_reportCode]['keys'];
		$filterCode = $this->_reportes[$this->_reportCode]['filterCode'];
		$reportTitle = $this->_reportes[$this->_reportCode]['title'];
		$htmlView = $this->_reportes[$this->_reportCode]['htmlView'];
		
		// Construct the report filter
		$filterObject = new ReporteFiltro ($filterCode);
		$filter = $filterObject->filter;
		$options = $filterObject->options;
		
		// Create view Mapp		
		$vmap = new ViewMapper ($this->_db, $viewName, $viewTableKeys);
		// Get filtered rows
		$lista = $vmap->dbRead($filter, $options);
		$this->_f3->set("lista",$lista);

    	$op = ($this->_f3->exists('PARAMS.msg') && $this->_f3->get('PARAMS.msg')=='export')?'export':'read';
        switch ($op) {
			case 'read':
			// Set general Template variables
	        $this->_f3->set('page_head',$this->_pageTitle);    
	        $this->_f3->set('page_subhead',$reportTitle);
	        $this->_f3->set('navigation',$this->_viewNavigation);	
	        $this->_f3->set('view', $htmlView);
				break;	
			case 'export':
				$this->export($lista);
				exit(); // To stop routing
			default:
				break;
		} // Swicth
	} // end leccioneFiltro
	
	public function export($lista)
    {
		$caracter_separador = "|";
    	
    	$delimiter = $this->_f3->get('export_delimiter');
    	$company =  $this->_f3->get('company_name');
    	$system =  $this->_f3->get('system_name');
		$dbname = $this->_f3->get('db_name');
		$lfmt = $this->_f3->get('leccion_id_format');
		$list = array();
		$list[] = array("$company - $system");
		$list[] = array("Listado de proyectos");
		$list[] = array(
		"Area de Conocimiento",
		"Valoración",
		"[ID Global]:ID Lecciones",
		"Año",
		"Proyecto",
		"Estado",
		"Tipo de proyecto",
		"Tipo de servicio",
		"Cliente",
		"Mercado",
		"Ubicación",
		"País",
		"Arquitectura",
		"Tecnología principal",
		"Base de datos",
		"Lección Aprendida");
		// Get project list
		foreach ($lista as $elem) {
			$idleccion = sprintf ($lfmt,$elem->idleccion);
	        $list[] = array($elem->nombre,
	        ($elem->valoracion=='P'?'A REPETIR':'A EVITAR'),
	        !$elem->esglobal?'"'.$idleccion.'"':"[$idleccion]".
	        ($elem->esglobal?": ".str_replace(PHP_EOL,$caracter_separador,$elem->lecciones):''),
	        is_null($elem->anos)?"":str_replace(PHP_EOL,$caracter_separador,$elem->anos),
	        is_null($elem->proyectos)?"":str_replace(PHP_EOL,$caracter_separador,$elem->proyectos),
	        ($elem->estado==0?"CERRADO":($elem->estado==1?"ABIERTO":"")),
	        is_null($elem->tproyectos)?"":str_replace(PHP_EOL,$caracter_separador,$elem->tproyectos),
	        is_null($elem->tservicios)?"":str_replace(PHP_EOL,$caracter_separador,$elem->tservicios),
	        is_null($elem->clientes)?"":str_replace(PHP_EOL,$caracter_separador,$elem->clientes),
	        is_null($elem->mercados)?"":str_replace(PHP_EOL,$caracter_separador,$elem->mercados),
	        is_null($elem->ubicaciones)?"":str_replace(PHP_EOL,$caracter_separador,$elem->ubicaciones),
	        is_null($elem->paises)?"":str_replace(PHP_EOL,$caracter_separador,$elem->paises),
	        is_null($elem->arquitecturas)?"":str_replace(PHP_EOL,$caracter_separador,$elem->arquitecturas),
	        is_null($elem->tecnologias)?"":str_replace(PHP_EOL,$caracter_separador,$elem->tecnologias),
	        is_null($elem->basesdatos)?"":str_replace(PHP_EOL,$caracter_separador,$elem->basesdatos),
	        $elem->leccion);	          
		}
		array2csvDownload ($list, 'lecciones_consolidadas.csv', $delimiter);
	} // End Export
		
} // End ReportesController

