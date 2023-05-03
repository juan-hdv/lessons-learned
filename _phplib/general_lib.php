<?php

/*
 *  mystrcmp
 * 	@str1 @str strings to be compared
 * 	MySql saves the strings in html entities 
 * 	This function us to compare PHP strings with MySql strings  
 *
 */
function mystrcmp ($str1, $str2) {
	return strcasecmp(htmlentities($str1,ENT_COMPAT,'UTF-8'),$str2) == 0;	
} // mystrcmp


/*
 * _objectToArray
 * Copy relevant fields/values from a F3 tableMapper Array of Objects to a simple Array
 * Return the simple associative Array (<field>=><value>) 
 */
function objectsToArray ($objectArray, $fieldNames) {
	$fieldsArray = explode (',',trim($fieldNames));
	$a = Array();		
	foreach ($objectArray as $key=>$elem) {
		foreach ($fieldsArray as $fName) {
			$a[$key][$fName] = $elem->$fName;
		}
	}		
	return $a;
} // End _objectToArray

function array2csvDownload($array, $filename = "export.csv", $delimiter=";") {
	header('Content-Encoding: UTF-8');
	header('Content-Type: application/csv; charset=UTF-8');
    header('Content-Disposition: attachement; filename="'.$filename.'";');
	echo "\xEF\xBB\xBF"; // UTF-8 BOM
	
	// open the "output" stream
    // see http://www.php.net/manual/en/wrappers.php.php#refsect2-wrappers.php-unknown-unknown-unknown-descriptioq
    $f = fopen("php://output",'w');
    foreach ($array as $fields) {
    	// Preprocess fields: add doble quotes, and replace CR/LF with LF
    	foreach ($fields as $key=>$val) {
    		if (is_string($val))
    			$fields[$key] = str_replace(PHP_EOL,"\r ",$val);
    	}
		// Export row - fputcsv insert the "" for string columns
        fputcsv($f, $fields, $delimiter);
    }
}

function downloadFile ($filename) {
	if (!file_exists($filename)) 
		return;
	$mime_types=array(
	    "pdf" => "application/pdf",
	    "txt" => "text/plain",
	    "html" => "text/html",
	    "htm" => "text/html",
	    "exe" => "application/octet-stream",
	    "zip" => "application/zip",
	    "doc" => "application/msword",
	    "xls" => "application/ms-excel",
	    "ppt" => "application/ms-powerpoint",
	    "gif" => "image/gif",
	    "png" => "image/png",
	    "jpeg"=> "image/jpg",
	    "jpg" =>  "image/jpg",
	    "php" => "text/plain"
	);
	$filetype = pathinfo($filename, PATHINFO_EXTENSION);
	if (!array_key_exists($filetype,$mime_types))
		return;
	 
	$mime_type = $mime_types[$filetype];

	//set headers
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
	header("Content-Disposition: attachment; filename=\"$filename\"");
	header("Content-type: ".$mime_type); 
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
	//for IE6 and IE7
	header("Pragma: public");
    header('Content-Length: ' . filesize($filename));
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	// required in IE, or Content-Disposition may be ignored
	if (ini_get('zlib.output_compression')) 
		ini_set('zlib.output_compression', 'Off');
	
	readfile($filename);
	exit ();
} // downloadFile
