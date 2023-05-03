<?php
/*
 * ReportesGraficosController
 * 	Se encarga de la Administración de los reportes Graficos con la librería p Chart
 * 
 */
require_once ("_phplib/pChart/pData.class");  
require_once ("_phplib/pChart/pChart.class");
  
 
class ReportesGraficosController extends ModuleMainController {
	
	protected $_pageTitle = 'Reportes Gráficos';
	protected $_graphsDir = '/graphs';
	
	/*
	 * REPORTES
	 * 
	 */
	protected $_reportes = array (
		"RG1"=>array ("title"=>"Lecciones Aprendidas Repetidas por Area de Conocimiento", 
					 "view"=>"", "keys"=>"",
					 "htmlView"=>"/reportesGraficos/rg1.html"),
		"RG2"=>array ("title"=>"Lecciones Aprendidas Repetidas por Proyectos (Todos)", 
					 "view"=>"", "keys"=>"",
					 "htmlView"=>"/reportesGraficos/rg1.html"),
			"RG21"=>array ("title"=>"Lecciones Aprendidas Repetidas por Proyectos (Abiertos)", 
						 "view"=>"", "keys"=>"",
						 "htmlView"=>"/reportesGraficos/rg1.html"),
			"RG22"=>array ("title"=>"Lecciones Aprendidas Repetidas por Proyectos (Cerrados)", 
						 "view"=>"", "keys"=>"",
						 "htmlView"=>"/reportesGraficos/rg1.html"),
		"RG3"=>array ("title"=>"Lecciones Aprendidas Repetidas por Tipos de Proyecto", 
					 "view"=>"", "keys"=>"",
					 "htmlView"=>"/reportesGraficos/rg1.html"),
		"RG4"=>array ("title"=>"Lecciones Aprendidas Repetidas por Tipos de Servicio", 
					 "view"=>"", "keys"=>"",
					 "htmlView"=>"/reportesGraficos/rg1.html"),
		"RG5"=>array ("title"=>"Lecciones Aprendidas Repetidas por Clientes", 
					 "view"=>"", "keys"=>"",
					 "htmlView"=>"/reportesGraficos/rg1.html"),
		"RG6"=>array ("title"=>"Lecciones Aprendidas Repetidas por Mercados", 
					 "view"=>"", "keys"=>"",
					 "htmlView"=>"/reportesGraficos/rg1.html"),
		"RG7"=>array ("title"=>"Lecciones Aprendidas Repetidas por Arquitecturas", 
					 "view"=>"", "keys"=>"",
					 "htmlView"=>"/reportesGraficos/rg1.html"),
		"RG8"=>array ("title"=>"Lecciones Aprendidas Repetidas por Tecnología Principal", 
					 "view"=>"", "keys"=>"",
					 "htmlView"=>"/reportesGraficos/rg1.html"),
		"RG9"=>array ("title"=>"Lecciones Aprendidas Repetidas por Base de Datos", 
					 "view"=>"", "keys"=>"",
					 "htmlView"=>"/reportesGraficos/rg1.html"),
		"RG10"=>array ("title"=>"Lecciones Aprendidas Repetidas por Ubicaciones", 
					 "view"=>"", "keys"=>"",
					 "htmlView"=>"/reportesGraficos/rg1.html"),
		"RG11"=>array ("title"=>"Lecciones Aprendidas Repetidas por Países", 
					 "view"=>"", "keys"=>"",
					 "htmlView"=>"/reportesGraficos/rg1.html"),
		"RG12"=>array ("title"=>"Lecciones Aprendidas Repetidas por Años", 
					 "view"=>"", "keys"=>"",
					 "htmlView"=>"/reportesGraficos/rg1.html"),
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
				$this->_f3->reroute('/reportesGraficos/RG1');
				return;
			} 
		}
		else  {
			$this->_f3->reroute('/reportesGraficos/RG1');
			return;
		}
		
    	$op = ($this->_f3->exists('PARAMS.msg') && $this->_f3->get('PARAMS.msg')=='export')?'export':'read';
    	if ($op == 'read')
			// Recreate lecciones Globales in tables: lglobales and lecciones
			AsociacionController::recreateLeccionesGlobales($this);
		
		$viewName = $this->_reportes[$this->_reportCode]['view'];
		$viewTableKeys = $this->_reportes[$this->_reportCode]['keys'];
		$title = $this->_reportes[$this->_reportCode]['title'];
		$htmlView = $this->_reportes[$this->_reportCode]['htmlView'];
		/*
		if (!empty($viewName))
			$vmap = new ViewMapper ($this->_db, $viewName, $viewTableKeys);
		*/
		$BASEPATH = $this->_f3->get('BASE');
		$data = NULL;
		$longestLegend = 0;
		$this->_createData ($data,$longestLegend);
		$this->_createChart ($data,$longestLegend);
		$this->_f3->set('dataSet',is_null ($data)?0:1);
		if (is_null ($data)) {
			$dir =  $_SERVER['DOCUMENT_ROOT'] . $BASEPATH . $this->_graphsDir; // 
			// delete all charts (*.png) on /graphs directory
			foreach (glob("$dir/*.png") as $filename)
 			   unlink ("$filename");
		}

        switch ($op) {
			case 'read':
				// Set general Template variables
		        $this->_f3->set('page_head',$this->_pageTitle);    
		        $this->_f3->set('page_subhead',$title);
		        $this->_f3->set('navigation',$this->_viewNavigation);	
		        $this->_f3->set('view', $htmlView);
				$this->_f3->set('reportCode', $this->_reportCode);
				break;	
			case 'export':
				downloadFile ($_SERVER['DOCUMENT_ROOT'] . $BASEPATH . $this->_graphsDir . "/graph".$this->_reportCode.".png");
				exit();
			default:
				break;
		} // Swicth
	} // end report

	private function _createData (&$data, &$longestLegend) {
		$result = array ();
	 	$rc = $this->_reportCode;
		switch ($rc) {
			case "RG1": // areasc
				$result = $this->_db->exec (
					'SELECT areasc.nombre as nom, COUNT(*) as cont '. 
					'FROM areasc '.
					'JOIN lecciones l ON l.areac = areasc.idareac '.
					'AND l.esglobal =0 '.
					'AND l.lecglobal IS NOT NULL '. 
					'GROUP BY areasc.idareac '.
					'ORDER BY nom'		
				);
				// Get the list of lecciones related with the report (for Labels)
				$result2 = $this->_db->exec (
					'SELECT l.idleccion as id, areasc.nombre as nom '. 
					'FROM lecciones l, areasc '.
					'WHERE l.areac = areasc.idareac '.
					'AND l.esglobal =0 '.
					'AND l.lecglobal IS NOT NULL '. 
					'ORDER BY nom'		
				);
				break;
			case "RG2":  // Proyectos (Todos)
			case "RG21": // Proyectos (abiertos)
			case "RG22": // Proyectos (cerrados)
				$where = '';
				switch ($rc) {
					case "RG21": // Proyectos (abiertos)
						$where = 'WHERE estado =1 ';
						break;
					case "RG22": // Proyectos (cerrados)
						$where = 'WHERE estado =0 ';
						break;
				} // End swicth
				$result = $this->_db->exec (
					'SELECT proyectos.codigo as nom, COUNT(*) as cont '. 
					'FROM proyectos '.
					'JOIN lecciones l ON l.idproyecto = proyectos.idproyecto '.
					'AND l.esglobal =0 '.
					'AND l.lecglobal IS NOT NULL '.
					$where .  
					'GROUP BY proyectos.idproyecto '.
					'ORDER BY nom'
				);
				// Get the list of lecciones related with the report (for Labels)
				$result2 = $this->_db->exec (
					'SELECT l.idleccion as id, p.codigo as nom '. 
					'FROM lecciones l, proyectos p '.
					'WHERE l.idproyecto = p.idproyecto '.
					'AND l.esglobal =0 '.
					'AND l.lecglobal IS NOT NULL '. 
					'ORDER BY nom'
				);
				break;
			case "RG3":	// tproyectos
				$result = $this->_db->exec (
					'SELECT tproyectos.nombre as nom, COUNT(*) as cont '. 
					'FROM tproyectos, proyectos '.
					'JOIN lecciones l ON l.idproyecto = proyectos.idproyecto '.
					'AND l.esglobal =0 '.
					'AND l.lecglobal IS NOT NULL '. 
					'WHERE tproyectos.idtproyecto = proyectos.tproyecto '.
					'GROUP BY tproyectos.idtproyecto '.
					'ORDER BY nom'
				);
				// Get the list of lecciones related with the report (for Labels)
				$result2 = $this->_db->exec (
					'SELECT l.idleccion as id, tproyectos.nombre as nom '. 
					'FROM lecciones l, proyectos p, tproyectos '.
					'WHERE l.idproyecto = p.idproyecto '.
					'AND p.tproyecto = tproyectos.idtproyecto '.
					'AND l.esglobal =0 '.
					'AND l.lecglobal IS NOT NULL '. 
					'ORDER BY nom'
				);
				break;
			case "RG4":	// tservicios
				$result = $this->_db->exec (
					'SELECT tservicios.nombre as nom, COUNT(*) as cont '. 
					'FROM tservicios, proyectos '.
					'JOIN lecciones l ON l.idproyecto = proyectos.idproyecto '.
					'AND l.esglobal =0 '.
					'AND l.lecglobal IS NOT NULL '. 
					'WHERE tservicios.idtservicio = proyectos.tservicio '.
					'GROUP BY tservicios.idtservicio '.
					'ORDER BY nom'
				);
				// Get the list of lecciones related with the report (for Labels)
				$result2 = $this->_db->exec (
					'SELECT l.idleccion as id, tservicios.nombre as nom '. 
					'FROM lecciones l, proyectos p, tservicios '.
					'WHERE l.idproyecto = p.idproyecto '.
					'AND p.tservicio = tservicios.idtservicio '.
					'AND l.esglobal =0 '.
					'AND l.lecglobal IS NOT NULL '. 
					'ORDER BY nom'
				);
				break;
			case "RG5":	// clientes
				$result = $this->_db->exec (
					'SELECT clientes.nombre as nom, COUNT(*) as cont '. 
					'FROM clientes, proyectos '.
					'JOIN lecciones l ON l.idproyecto = proyectos.idproyecto '.
					'AND l.esglobal =0 '.
					'AND l.lecglobal IS NOT NULL '. 
					'WHERE clientes.idcliente = proyectos.cliente '.
					'GROUP BY clientes.idcliente '.
					'ORDER BY nom'
				);
				// Get the list of lecciones related with the report (for Labels)
				$result2 = $this->_db->exec (
					'SELECT l.idleccion as id, clientes.nombre as nom '. 
					'FROM lecciones l, proyectos p, clientes '.
					'WHERE l.idproyecto = p.idproyecto '.
					'AND p.cliente = clientes.idcliente '.
					'AND l.esglobal =0 '.
					'AND l.lecglobal IS NOT NULL '. 
					'ORDER BY nom'
				);
				break;
			case "RG6":	// mercados
				$result = $this->_db->exec (
					'SELECT mercados.nombre as nom, COUNT(*) as cont '. 
					'FROM mercados, proyectos '.
					'JOIN lecciones l ON l.idproyecto = proyectos.idproyecto '.
					'AND l.esglobal =0 '.
					'AND l.lecglobal IS NOT NULL '. 
					'WHERE mercados.idmercado = proyectos.mercado '.
					'GROUP BY mercados.idmercado '.
					'ORDER BY nom'
				);
				// Get the list of lecciones related with the report (for Labels)
				$result2 = $this->_db->exec (
					'SELECT l.idleccion as id, mercados.nombre as nom '. 
					'FROM lecciones l, proyectos p, mercados '.
					'WHERE l.idproyecto = p.idproyecto '.
					'AND p.mercado = mercados.idmercado '.
					'AND l.esglobal =0 '.
					'AND l.lecglobal IS NOT NULL '. 
					'ORDER BY nom'
				);
				break;
			case "RG7":	// arquitecturas
				$result = $this->_db->exec (
					'SELECT arquitecturas.nombre as nom, COUNT(*) as cont '. 
					'FROM arquitecturas, proyectos '.
					'JOIN lecciones l ON l.idproyecto = proyectos.idproyecto '.
					'AND l.esglobal =0 '.
					'AND l.lecglobal IS NOT NULL '. 
					'WHERE arquitecturas.idarquitectura = proyectos.arquitectura '.
					'GROUP BY arquitecturas.idarquitectura '.
					'ORDER BY nom'
				);
				// Get the list of lecciones related with the report (for Labels)
				$result2 = $this->_db->exec (
					'SELECT l.idleccion as id, arquitecturas.nombre as nom '. 
					'FROM lecciones l, proyectos p, arquitecturas '.
					'WHERE l.idproyecto = p.idproyecto '.
					'AND p.arquitectura = arquitecturas.idarquitectura '.
					'AND l.esglobal =0 '.
					'AND l.lecglobal IS NOT NULL '. 
					'ORDER BY nom'
				);
				break;
			case "RG8":	// Tecnologias
				$result = $this->_db->exec (
					'SELECT tecnologias.nombre as nom, COUNT(*) as cont '. 
					'FROM tecnologias, proyectos '.
					'JOIN lecciones l ON l.idproyecto = proyectos.idproyecto '.
					'AND l.esglobal =0 '.
					'AND l.lecglobal IS NOT NULL '. 
					'WHERE tecnologias.idtecnologia = proyectos.tecnologia '.
					'GROUP BY tecnologias.idtecnologia '.
					'ORDER BY nom'
				);
				// Get the list of lecciones related with the report (for Labels)
				$result2 = $this->_db->exec (
					'SELECT l.idleccion as id, tecnologias.nombre as nom '. 
					'FROM lecciones l, proyectos p, tecnologias '.
					'WHERE l.idproyecto = p.idproyecto '.
					'AND p.tecnologia = tecnologias.idtecnologia '.
					'AND l.esglobal =0 '.
					'AND l.lecglobal IS NOT NULL '. 
					'ORDER BY nom'
				);
				break;
			case "RG9":	// basesdatos
				$result = $this->_db->exec (
					'SELECT basesdatos.nombre as nom, COUNT(*) as cont '. 
					'FROM basesdatos, proyectos '.
					'JOIN lecciones l ON l.idproyecto = proyectos.idproyecto '.
					'AND l.esglobal =0 '.
					'AND l.lecglobal IS NOT NULL '. 
					'WHERE basesdatos.idbasedatos = proyectos.basedatos '.
					'GROUP BY basesdatos.idbasedatos '.
					'ORDER BY nom'
				);
				// Get the list of lecciones related with the report (for Labels)
				$result2 = $this->_db->exec (
					'SELECT l.idleccion as id, basesdatos.nombre as nom '. 
					'FROM lecciones l, proyectos p, basesdatos '.
					'WHERE l.idproyecto = p.idproyecto '.
					'AND p.basedatos = basesdatos.idbasedatos '.
					'AND l.esglobal =0 '.
					'AND l.lecglobal IS NOT NULL '. 
					'ORDER BY nom'
				);
				break;
			case "RG10":	// ubicaciones
				$result = $this->_db->exec (
					'SELECT ubicaciones.nombre as nom, COUNT(*) as cont '. 
					'FROM ubicaciones, proyectos '.
					'JOIN lecciones l ON l.idproyecto = proyectos.idproyecto '.
					'AND l.esglobal =0 '.
					'AND l.lecglobal IS NOT NULL '. 
					'WHERE ubicaciones.idubicacion = proyectos.ubicacion '.
					'GROUP BY ubicaciones.idubicacion '.
					'ORDER BY nom'
				);
				// Get the list of lecciones related with the report (for Labels)
				$result2 = $this->_db->exec (
					'SELECT l.idleccion as id, ubicaciones.nombre as nom '. 
					'FROM lecciones l, proyectos p, ubicaciones '.
					'WHERE l.idproyecto = p.idproyecto '.
					'AND p.ubicacion = ubicaciones.idubicacion '.
					'AND l.esglobal =0 '.
					'AND l.lecglobal IS NOT NULL '. 
					'ORDER BY nom'
				);
				break;
			case "RG11":	// paises
				$result = $this->_db->exec (
					'SELECT paises.nombre as nom, COUNT(*) as cont '. 
					'FROM paises, ubicaciones, proyectos '.
					'JOIN lecciones l ON l.idproyecto = proyectos.idproyecto '.
					'AND l.esglobal =0 '.
					'AND l.lecglobal IS NOT NULL '. 
					'WHERE ubicaciones.idubicacion = proyectos.ubicacion '.
					'AND ubicaciones.pais = paises.idpais '. 
					'GROUP BY paises.idpais '.
					'ORDER BY nom'
				);
				// Get the list of lecciones related with the report (for Labels)
				$result2 = $this->_db->exec (
					'SELECT l.idleccion as id, paises.nombre as nom '. 
					'FROM lecciones l, proyectos p, ubicaciones, paises '.
					'WHERE l.idproyecto = p.idproyecto '.
					'AND p.ubicacion = ubicaciones.idubicacion '.
					'AND ubicaciones.pais = paises.idpais '. 
					'AND l.esglobal =0 '.
					'AND l.lecglobal IS NOT NULL '. 
					'ORDER BY nom'
				);
				break;
			case "RG12":	// Años
				// Create an array of 10 years with @year as the center value
				$year = $this->_f3->get('year');
				$anos = array();
				for ($k=-10; $k<=10; $k++) { 
					$y = $year+$k;
					$anos["$y"] = "'$y'";
				} 
				$lanos = join (',',$anos);
				$result = $this->_db->exec (
					'SELECT lecciones.ano as nom, COUNT(*) as cont '. 
					'FROM lecciones '.
					"JOIN lecciones l ON l.ano IN ($lanos) ".
					'AND l.esglobal =0 '.
					'AND l.lecglobal IS NOT NULL '. 
					'GROUP BY lecciones.ano '.
					'ORDER BY nom'
				);
				// Get the list of lecciones related with the report (for Labels)
				$result2 = $this->_db->exec (
					'SELECT l.idleccion as id, l.ano as nom '. 
					'FROM lecciones l '.
					'WHERE l.esglobal =0 '.
					'AND l.lecglobal IS NOT NULL '. 
					'ORDER BY nom'
				);
				break;
		} // End switch
		if (!empty($result) && !empty($result2)) {
			// Construct a list of related lecciones for each label
	        $idfmt = $this->_f3->get('leccion_id_format');
			$newArray = array();
			foreach ($result2 as $key=>$arreglo) {
				$newArray[$arreglo['nom']][] = sprintf($idfmt,$arreglo['id']);
			}
			$lecciones = array();
			foreach ($newArray as $key=>$elem) {
				$lecciones[$key] = join (',',$elem);
			}	
			
			$aData = array();
			$aLabels = array();
			// Construct the lables (Absise for the chart)
			foreach ($result as $key=>$row) {
				$nom = $row['nom'];
				$lec = !empty($lecciones)&&!empty($lecciones[$nom])?" (".$lecciones[$nom].")":'';
				$aLabels[] = $row['nom'] . $lec;
				$aData[] = $row['cont'];
			}  
			$longestLegend = $this->_getLongestString ($aLabels);
			
			$data = new pData();
			$data->AddPoint($aData,"Serie1");  
			$data->AddPoint($aLabels,"Serie2");
			$data->AddAllSeries();  
			$data->SetAbsciseLabelSerie("Serie2");
		}
		/* 
		else {
			$data = NULL;
	        $this->_info_message[] = "La consulta no arrojó resultados. Escoja otro reporte.";
			$this->_f3->set(Controller::INFO_MESSAGE, $this->_info_message);
		} 
		*/ 
	} // createData
		
	private function _createChart ($data, $longestLegend) {
		 if (empty($data)) return;

		 $dirFonts = "_phplib/pChart/Fonts";
		 
		 // Initialise the graph  
		 $w = 900;
		 $h = 450;
		 $marginW = 10;
		 $marginH = 10;
		 
		 // Get the picture box of x number of "A" characters of font (fontfile)
		 $bbox = imagettfbbox(9, 0, "$dirFonts/tahoma.ttf", $longestLegend);
		 $legendWidth =  abs($bbox[4] - $bbox[0]) + 30;
		 $pieW = $w - 2*$marginW - 300;
		 $pieH = $h - 2*$marginH;
		 $pieRad = ($pieW + $pieH - 8*$marginH)/4;  // Average of W/2 and H/2
		 		 
		 // This will create a wxh picture
		 $chart = new pChart($w,$h);  
			
		 // void drawFilledRoundedRectangle($X1,$Y1,$X2,$Y2,$Radius,$R,$G,$B)  
		 $chart->drawFilledRoundedRectangle($marginW,$marginH,$w-$marginW,$h-$marginH,5,240,240,240);  
		 // void drawRoundedRectangle($X1,$Y1,$X2,$Y2,$Radius,$R,$G,$B)  
		 $chart->drawRoundedRectangle($marginW-3,$marginH-3,$w-$marginW-3,$h-$marginH-3,5,230,230,230);  
		  
		 // Draw the pie chart  
		 $chart->setFontProperties("$dirFonts/tahoma.ttf",8);

		 // void drawPieGraph($Data,$DataDescription,$XPos,$YPos,$Radius=100,$DrawLabels=PIE_NOLABEL,$EnhanceColors=TRUE,$Skew=60,$SpliceHeight=20,$SpliceDistance=0,$Decimals=0)
		 // X,Y Graph Center   
		 $chart->drawPieGraph($data->GetData(),$data->GetDataDescription(),$pieW/2,$pieH/2,$pieRad,PIE_PERCENTAGE,TRUE,50,20,5);

		 // void drawPieLegend($XPos,$YPos,$Data,$DataDescription,$R,$G,$B) 
		 $chart->drawPieLegend($w-$legendWidth-$marginW,$marginH*2,$data->GetData(),$data->GetDataDescription(),250,250,250);

		 $rc = $this->_reportCode; 
		 // Title
		 // $chart->drawTitle(60,22,$this->_reportes[$rc]->title,50,50,50,585);
		 $chart->setFontProperties("$dirFonts/pf_arma_five.ttf",8);  
		 // drawTextBox($X1,$Y1,$X2,$Y2,$Text,$Angle=0,$R=255,$G=255,$B=255,$Align=ALIGN_LEFT,$Shadow=TRUE,$BgR=-1,$BgG=-1,$BgB=-1,$Alpha=100)
 		 $chart->drawTextBox($marginW+5,$marginH+5,$pieW/3,30,$this->_reportes[$rc]['title'],0,250,80,80,ALIGN_LEFT,false);  		 
		   		  
		 $chart->Render("graphs/graph$rc.png");  		
	} // createChart

	/*
	 * _getLongestString
	 * @arr		ARRAY
	 * @Result
	 * 
	 * Return the longest string in @arr 
	 * 
	 */
	private function _getLongestString ($arr) {
		// Create an array of string lengths 
		$lengths = array_map('strlen', $arr);
		// Get the max str len
		$maxlen = max($lengths);
		// Get the key for that max len  
		$key = array_search($maxlen, $lengths);
		// Return string of max length
		return $arr[$key];
	} // End _getLongestString
		
} // End class AdminController

