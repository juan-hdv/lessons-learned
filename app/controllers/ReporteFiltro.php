<?php
require_once ("_phplib/general_lib.php");

/*
 * ReporteFiltro
 * 	Filtros de los reportes del sistema
 * 
 */
class ReporteFiltro 
{
	/*
	 * FILTROS
	 * 		F1,F2: Sobre los mismos campos y las mismas tablas auxiliares
	 * 
	 * 		F1: Sobre el campo nombre de las tablas auxiliares
	 * 			Lecciones Globales y No Globales
	 * 
	 * 		F2: Sobre el campo código de las tablas auxiliares.
	 * 			Sólo lecciones No Globales
	 */
	protected $_filtros = array (
		/* 
		 * F1:
		 * 	LECCIONES GLOBALES Y NO GLOBALES 
		 *  - Filtro por valores (nombre) no códigos (keys) : r_filtro1 * * *
		 *  - Todas las lecciones: globales y agrupadas
		 *  - Ordenadas por 
		 * 		1.Criterio-Orden DESC, Esglobal DESC, 3.Valoracion DESC, 4.Ano DESC, 5.Leccion ASC
		 */
		"F1"=>array ("view"=>"view_R1", "keys"=>"idleccion"),
		/* 
		 * F2
		 * 	LECCIONES NO GLOBALES 
		 *  - Filtro por códigos (keys) : r_filtro2 * * *
		 *  - Lecciones NO Globales, 
		 * 	- Ordenadas por 1.Criterio-Orden ASC, 2.Valoración DESC, 3.Año DESC, y 4.Lección ASC
		 */
		"F2"=>array ("view"=>"view_R2", "keys"=>"idleccion"),
	);
	  
	public $filter;		// SQL Filter
	public $options;  	// SQL Options
	
	private $_filterCode = '';
	private $_f3 = null;
	private $_db = null;
	
	// N/A Fiel Code and
	protected $_NAFieldName = "Sin especificar";
	protected $_NAFieldCode = -1;


	function __construct ($filterCode) {

		$f3=Base::instance();
		$db=new DB\SQL(
            $f3->get('db_dns') . $f3->get('db_name'),
            $f3->get('db_user'),
            $f3->get('db_pass')
        );
	    $this->_f3=$f3;
	    $this->_db=$db;
		
		$this->_filterCode = $filterCode;
		$viewName = $this->_filtros[$this->_filterCode]['view'];
		$viewTableKeys = $this->_filtros[$this->_filterCode]['keys'];
		// Map view
		$vmap = new ViewMapper ($this->_db, $viewName, $viewTableKeys);
		// Get auxiliary table rows
		$this->_filterGetForeignKeyRows ();
		$this->_filterSetDefaultSelectValues ();
		if ($this->_f3->get ('POST.filter')) {
			$this->_f3->copy('POST', 'MY_POST');  // Copy POST TO MY_POST
			$this->_filterConstruct ();
		} else
			$this->_filterSetDefaultMYPOST ();
	}
		
	/*
	 * @filter: string|array: array('col1=? and col2=?...',$col1, $col2...)
	 * @options: array: (	'order' => string $orderClause, 
	 * 						'group' => string $groupClause,
     * 						'limit' => integer $limit,
	 * 						'offset' => integer $offset)
	 */
	 private function _filterConstruct () 
	 {
	 	$rc = $this->_filterCode;
	 	$this->options = null;
	 	$this->filter = null;
		// Get MY_POST
		switch ($rc) {
			case "F1":
			case "F2":
				$ano = $this->_f3->get ('MY_POST.ano');
				$val = $this->_f3->get ('MY_POST.valoracion');
				$are = $this->_f3->get ('MY_POST.areac');
				$pro = $this->_f3->get ('MY_POST.proyecto');
				$est = $this->_f3->get ('MY_POST.estado');
				$tpr = $this->_f3->get ('MY_POST.tproyecto');
				$tse = $this->_f3->get ('MY_POST.tservicio');
				$cli = $this->_f3->get ('MY_POST.cliente');
				$mer = $this->_f3->get ('MY_POST.mercado');
				$ubi = $this->_f3->get ('MY_POST.ubicacion');
				$pai = $this->_f3->get ('MY_POST.pais');
				$arq = $this->_f3->get ('MY_POST.arquitectura');
				$len = $this->_f3->get ('MY_POST.tecnologia');
				$bas = $this->_f3->get ('MY_POST.basedatos');
				break;
			case "R3":
				break;
		} // End switch
		
		// Filter Conditions
		$s = '';
		switch ($rc) {
			case "F1": // Lecciones Globales y NO Globales
				$s .= $ano!=$this->_NAFieldName?"anos LIKE ? and ":'';
				$s .= $val!=$this->_NAFieldCode?"valoracion = ? and ":''; // * Este campo es diferente
				$s .= $are!=$this->_NAFieldName?"nombre LIKE ? and ":'';  // En F1 areac se llama nombre
				$s .= $pro!=$this->_NAFieldName?"proyectos LIKE ? and ":'';
				$s .= $est!=$this->_NAFieldCode?"(estado = 3 or estado LIKE ?) and ":''; // * Este campo es diferente. 3 = leccion Global
				$s .= $tpr!=$this->_NAFieldName?"tproyectos LIKE ? and ":'';
				$s .= $tse!=$this->_NAFieldName?"tservicios LIKE ? and ":'';
				$s .= $cli!=$this->_NAFieldName?"clientes LIKE ? and ":'';
				$s .= $mer!=$this->_NAFieldName?"mercados LIKE ? and ":'';
				$s .= $ubi!=$this->_NAFieldName?"ubicaciones LIKE ? and ":'';
				$s .= $pai!=$this->_NAFieldName?"paises LIKE ? and ":'';
				$s .= $arq!=$this->_NAFieldName?"arquitecturas LIKE ? and ":'';
				$s .= $len!=$this->_NAFieldName?"tecnologias LIKE ? and ":'';
				$s.= $bas!=$this->_NAFieldName?"basesdatos LIKE ?":'';
				break;
			case "F2": // Sólo lecciones NO Globales
				$s .= $ano!=$this->_NAFieldCode?"ano = ? and ":'';
				$s .= $val!=$this->_NAFieldCode?"valoracion = ? and ":'';
				// Areac					
				if (!is_null($are)) 
					$s .= $are!=$this->_NAFieldCode?"idareac = ? and ":'';
				else 
					$s .= $are!=$this->_NAFieldCode?"idareac IS NULL and ":'';
				$s .= $est!=$this->_NAFieldCode?"estado = ? and ":''; // * Este campo es diferente. 3 = leccion Global
				$s .= $pro!=$this->_NAFieldCode?"idproyecto = ? and ":'';
				$s .= $tpr!=$this->_NAFieldCode?"idtproyecto = ? and ":'';
				$s .= $tse!=$this->_NAFieldCode?"idtservicio = ? and ":'';
				$s .= $cli!=$this->_NAFieldCode?"idcliente = ? and ":'';
				$s .= $mer!=$this->_NAFieldCode?"idmercado = ? and ":'';
				$s .= $ubi!=$this->_NAFieldCode?"idubicacion = ? and ":'';
				$s .= $pai!=$this->_NAFieldCode?"idpais = ? and ":'';
				$s .= $arq!=$this->_NAFieldCode?"idarquitectura = ? and ":'';
				$s .= $len!=$this->_NAFieldCode?"idtecnologia = ? and ":'';
				$s .= $bas!=$this->_NAFieldCode?"idbasedatos = ?":'';
				break;
			case "R3":
				break;
		} // End swicth
		$s = preg_replace("/ \s*and\s*$/", '', $s);
				
		// Filter Values
		switch ($rc) {
			case "F1":
			case "F2":
				if ($rc == "F1") 
					$field = "_NAFieldName";
				elseif ($rc == "F2")
					$field = "_NAFieldCode";
				
				$v = array();
				$v[] = $ano!=$this->$field?$ano:false;
				$v[] = $val!=$this->_NAFieldCode?$val:false; // * Campo especial 
				$v[] = $are!=$this->$field?$are:false;
				$v[] = $pro!=$this->$field?$pro:false;
				$v[] = $est!=$this->_NAFieldCode?$est:false; // * Campo especial 
				$v[] = $tpr!=$this->$field?$tpr:false;
				$v[] = $tse!=$this->$field?$tse:false;
				$v[] = $cli!=$this->$field?$cli:false;
				$v[] = $mer!=$this->$field?$mer:false;
				$v[] = $ubi!=$this->$field?$ubi:false;
				$v[] = $pai!=$this->$field?$pai:false;
				$v[] = $arq!=$this->$field?$arq:false;
				$v[] = $len!=$this->$field?$len:false;
				$v[] = $bas!=$this->$field?$bas:false;
				$v = array_filter ($v, function ($val){return $val!==false;});

				
				if ($rc == "F1") {  // To process LIKE condition
					foreach ($v as $key=>$value) $v[$key] = '%'.$value.'%';	
				}
				
				if (empty ($s) || empty ($v)) {
					$this->filter = null;
				} else  
					$this->filter = array_merge(array ($s), $v);
				break;
			case "R3":
				break;
		}	// End swicth
		
		
	} // End _filterSetDefaultSelectValues
 	
 	private function _filterSetDefaultMYPOST () 
 	{
		$rc = $this->_filterCode;
		if ($rc == "F1")
			$field = "_NAFieldName";
		elseif ($rc == "F2")
			$field = "_NAFieldCode";
 		switch ($rc) {
			case "F1":
			case "F2":
				$this->_f3->set ('MY_POST.ano',$field);
				$this->_f3->set ('MY_POST.valoracion',$this->_NAFieldCode); // *Campo especial
				$this->_f3->set ('MY_POST.areac',$field);
				$this->_f3->set ('MY_POST.proyecto',$field);
				$this->_f3->set ('MY_POST.estado',$this->_NAFieldCode); // *Campo especial
				$this->_f3->set ('MY_POST.tproyecto',$field);
				$this->_f3->set ('MY_POST.tservicio',$field);
				$this->_f3->set ('MY_POST.cliente',$field);
				$this->_f3->set ('MY_POST.mercado',$field);
				$this->_f3->set ('MY_POST.ubicacion',$field);
				$this->_f3->set ('MY_POST.pais',$field);
				$this->_f3->set ('MY_POST.arquitectura',$field);
				$this->_f3->set ('MY_POST.tecnologia',$field);
				$this->_f3->set ('MY_POST.basedatos',$field);
				break;
			case "R3":
				break;
		} // end swicth
	} // end _filterSetDefaultMYPOST
	
 	private function _filterSetDefaultSelectValues () 
 	{
		$rc = $this->_filterCode;
		if ($rc == "F1")
			$NAArray = Array ($this->_NAFieldName, $this->_NAFieldName);
		elseif ($rc == "F2")
			$NAArray = Array ($this->_NAFieldCode, $this->_NAFieldName);
		switch ($rc) {
			case "F1":
			case "F2":
				// Año
				$t = array($this->_NAFieldCode => $this->_NAFieldName) + $this->_f3->get ('anos');
				$this->_f3->set ('anos', $t);
				
				// Tipo de leccion (P o N): valoracion
				// -> Skip: No hay que hacer nada, los valoers posibles están en la Vista
				
				// New Areac
				$t = $this->_f3->get ('areasc');
				if ($rc == "F1") { // Include GLOBALS
					array_unshift ($t, array_combine(array('idareac','nombre'),array(NULL,'GLOBAL')));
				}
				// And the default
				array_unshift ($t, array_combine(array('idareac','nombre'),$NAArray));
				$this->_f3->set ('areasc', $t);
				
				// Proyectos view (id, nom)
				$t = $this->_f3->get ('proyectos');
				array_unshift($t, array_combine(array('idproyecto','codigo'),$NAArray));
				$this->_f3->set ('proyectos', $t);

				// Estado del proyecto (1/0/3): Abrieto/Cerrado/Leccion Global
				// -> Skip: No hay que hacer nada, los valoers posibles están en la Vista
				
				// TProyectos
				$t = $this->_f3->get ('tproyectos');
				array_unshift ($t, array_combine(array('idtproyecto','nombre'),$NAArray));
				$this->_f3->set ('tproyectos', $t);
				// TServicio
				$t = $this->_f3->get ('tservicios');
				array_unshift ($t, array_combine(array('idtservicio','nombre'),$NAArray));
				$this->_f3->set ('tservicios', $t);
			    // Cliente
			    $t = $this->_f3->get ('clientes');
			   	array_unshift ($t, array_combine(array('idcliente','nombre'),$NAArray));
				$this->_f3->set ('clientes', $t);
				// Mercado
				$t = $this->_f3->get ('mercados');
				array_unshift ($t, array_combine(array('idmercado','nombre'),$NAArray));
				$this->_f3->set ('mercados', $t);
				// Ubicación
				$t = $this->_f3->get ('ubicaciones');
				array_unshift ($t, array_combine(array('idubicacion','nombre'),$NAArray));
				$this->_f3->set ('ubicaciones', $t);
				// Paises 
				$t = $this->_f3->get ('paises');
				array_unshift ($t, array_combine(array('idpais','nombre'),$NAArray));
				$this->_f3->set ('paises', $t);
				// Arquitectura
				$t = $this->_f3->get ('arquitecturas');
				array_unshift ($t, array_combine(array('idarquitectura','nombre'),$NAArray));
				$this->_f3->set ('arquitecturas', $t);
				// Tecnologias
				$t = $this->_f3->get ('tecnologias');
				array_unshift ($t, array_combine(array('idtecnologia','nombre'),$NAArray));
				$this->_f3->set ('tecnologias', $t);
				// Base de datos
				$t = $this->_f3->get ('basesdatos');
				array_unshift ($t, array_combine(array('idbasedatos','nombre'),$NAArray));
				$this->_f3->set ('basesdatos', $t);
				break;
			case "R3":
				break;
		} // End switch 		
 	} // End _filterSetDefaultSelectValues

 	private function _filterGetForeignKeyRows () 
	{
		// Get related table fields (from Foreign Keys) for corrent table
		switch ($this->_filterCode) {
			case "F1":
			case "F2":
				// Create an array of 10 years with @year as the center value
				$year = $this->_f3->get('year');
				$anos = array();
				for ($k=-10; $k<=10; $k++) { 
					$y = $year+$k;
					$anos["$y"] = "$y";
				} 
				$this->_f3->set('anos', $anos);
				// New Areac
				$tableMap = new TableMapper ($this->_db, 'areasc', 'idareac');
				$temp = objectsToArray ($tableMap->dbRead(), 'idareac,nombre'); 
				$this->_f3->set('areasc', $temp);
				// Proyectos view (id, nom)
				$tableMap = new ViewMapper ($this->_db,'view_proyectos_codes','idproyecto'); 
				$temp = objectsToArray ($tableMap->dbRead(), 'idproyecto,codigo'); 
				$this->_f3->set('proyectos', $temp); 
				// TProyectos
				$tableMap = new TableMapper ($this->_db,'tproyectos','idtproyecto'); 
				$temp = objectsToArray ($tableMap->dbRead(), 'idtproyecto,nombre'); 
				$this->_f3->set('tproyectos', $temp); 
				// TServicio
				$tableMap = new TableMapper ($this->_db,'tservicios','idtservicio'); 
				$temp = objectsToArray ($tableMap->dbRead(), 'idtservicio,nombre'); 
				$this->_f3->set('tservicios', $temp); 
			    // Cliente
				$tableMap = new TableMapper ($this->_db,'clientes','idcliente');
				$temp = objectsToArray ($tableMap->dbRead(), 'idcliente,nombre'); 
				$this->_f3->set('clientes', $temp); 
				// Mercado
				$tableMap = new TableMapper ($this->_db, 'mercados','idmercado');
				$temp = objectsToArray ($tableMap->dbRead(), 'idmercado,nombre'); 
				$this->_f3->set('mercados', $temp); 
				// Ubicación
				$tableMap = new TableMapper ($this->_db,'ubicaciones','idubicacion');
				$temp = objectsToArray ($tableMap->dbRead(), 'idubicacion,nombre'); 
				$this->_f3->set('ubicaciones', $temp);
				// Paises 
				$tableMap = new TableMapper ($this->_db,'paises','idpais'); 
				$temp = objectsToArray ($tableMap->dbRead(), 'idpais,nombre'); 
				$this->_f3->set('paises', $temp); 
				// Arquitectura
				$tableMap = new TableMapper ($this->_db, 'arquitecturas','idarquitectura');
				$temp = objectsToArray ($tableMap->dbRead(), 'idarquitectura,nombre'); 
				$this->_f3->set('arquitecturas', $temp); 
				// tecnologias
				$tableMap = new TableMapper ($this->_db, 'tecnologias','idtecnologia');
				$temp = objectsToArray ($tableMap->dbRead(), 'idtecnologia,nombre'); 
				$this->_f3->set('tecnologias', $temp); 
				// Base de datos
				$tableMap = new TableMapper ($this->_db, 'basesdatos','idbasedatos');
				$temp = objectsToArray ($tableMap->dbRead(), 'idbasedatos,nombre'); 
				$this->_f3->set('basesdatos', $temp); 				
				break;
			case "R3":
				break;
		} // End switch
	} // end _filterGetForeignKeyRows
} // End class AdminController

