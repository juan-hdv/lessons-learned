/*
 * 
 * Autor: JGM
 * general Use Functions
 * 
 */

/*
 * getOperationFromPath
 * @path 	STRING  in the form .a/b/c.../operation
 * @return	STRING	operation
 * 
 * Scope: 		General
 * Description:	Extract the last string on the @path which express an operation
 *  
 */
// window.onbeforeunload = function(e) { return 'Ask user a page leaving question here'; }; 

function getOperationFromPath (path) {
	var myarr = path.split("/");
	return myarr.pop();
}


/*
 * getLableName
 * 
 * @fieldObj	OBJECT
 * @result		STRING
 * 
 * Scope:		Forms input/labels
 * Description: Return the text of the Label associated with a field @fieldObj
 * 
 */
function getLabelName (fieldObj) {
	return $('label[for="'+$(fieldObj).attr('id')+'"]').text();
} // End getLableName

/*
 * validateEmptyFields
 * 
 * @formObj		OBJECT
 * @result		BOOLEAN
 * 
 * Scope: 		Forms
 * Description: Check for all required fields of a form @formObj not to be empty.
 * 		Return TRUE if no empty required field is found
 *  	Return FALSE if an empty required field is found. Also alerts a message.
 * 
 */
function validateEmptyFields(formObj) {
	var list=formObj.elements;
	if (formObj.elements == undefined || formObj.elements == null)
		return true;
	for (var k=0; k<list.length; k++) {
		var x = list[k];
		var req = x.required;
		var val = x.value;
		var lname = "";
		if (val === null) { 
			val = "";
		}
	   	if (val !== "")   {
	   		val = val.trim();
	   	}
		if (req && val==="") {
			lname = getLabelName (x);
		   	alert ('El campo "' + lname + '" es obligatorio.');
			return false;
		}
	}
	return true;
} // end validateEmptyFields

/*
 * submitForm
 * 
 * @formObj		: the form object
 * @scriptPath	: for submiting the form
 * @options    	: parametes as json object notation  {"param1":"value1", "param2":"value2"...} 
 * 		Generally:
 *      {"confirm":"Value"}
 *  
 *		if confirm != null => confirm = Value to show to the user in the confirm dialog.
 * 
 * 		if any of these: 
 * 		[ OR null or undefined (or  not passing the parameter) OR	{} OR false]			
 * 		=> NO confirm dialgo must be displayed
 * @Result	BOOLEAN
 * 
 * 	NOTE: In javascript
 *	You can call a Javascript function with any number of parameters, 
 *  regardless of the function's definition.
 *	Any named parameters that weren't passed will be undefined.
 *	Extra parameters can be accessed through the arguments array-like object.
 *
 * 	Scope:			Forms
 * 	Event:			Usually on the onclick event.
 * 	Description:	Submit the form @formObj, with action @scriptPath.
 * 	Before submiting:
 * 	1- Display a confirmation dialog for the operation expressed in the @scriptPath
 * 	some extra info to display in the dialog is given on the  @options paramenter.
 * 	2- Check for empty required fields and display an alert if any if found. 
 * 
 */
function submitForm (formObj, scriptPath, options) {
	var doAction = false;
	var op = '';
	if (!options || options.noconfirm) {
		doAction = true;
	}	
	else {
		if (options.confirm) {
			op = options.confirm;
			op = op.toLowerCase();
		}
		var msgConfirm = '';
		switch (op) {
			case "delete":			msgConfirm = "¿Confirma la eliminación del registro?"; break;
			case "create":			msgConfirm = "¿Confirma la creación del registro?"; break;
			case "update":			msgConfirm = "¿Confirma la modificación del registro?";	break;
			case "changePassword":	msgConfirm = "¿Confirma el cambio de la clave?"; break;
			case "unassociate":		msgConfirm = "¿Confirma la eliminación de la asociación del registro?"; break;
			case "associate":		msgConfirm = "¿Confirma la asociación del registro?"; break;
			case "cancel": 			msgConfirm = "Haga click en ACEPTAR para salir SIN SALVAR."; break;
			default: 				msgConfirm = "¿Confirma la operación?"; break; 
		}
		if (options.registerValue) {
	   		msgConfirm = msgConfirm +  " (Registro: '" + options.registerValue + "')";
		}
	   	if (options.moreInfo) {
	   		msgConfirm = options.moreInfo + "\n\n" +  msgConfirm;
	   	}
		doAction = confirm (msgConfirm); 
	}
	validData = true;
	if (doAction && op!="cancel") {
		var validData = validateEmptyFields(formObj);
	}
	// Check if doAction OR some requiered fields are empty
	if (!doAction || !validData) {
		// Save onsubmit EventHandler (Simulate a Static variable)
		submitForm.onsubmitEventHandler = formObj.onsubmit;
		// var formTrigeredFunction = formObj.onsubmit;
		// Set the onsummit event to cancel the sumbit (return = false)
		// Id there is an already set trigger function, call the funtion before returning false
		formObj.onsubmit = function() {
			// if (formTrigeredFunction) formTrigeredFunction();
			formObj.action = '';
		    return false;
		}
		return false;
	} else {
		formObj.action = scriptPath;
		// Execute the onsubmit method when being cancelled by a extrange behaviour of the submit
		// (after an alert, for example)
		
		// Restore onsubmit EventHandler (Simulate a Static variable)
		if (submitForm.onsubmitEventHandler != undefined)
			formObj.onsubmit = submitForm.onsubmitEventHandler; 
			
		if (formObj.onsubmit != null) {
			formObj.onsubmit();
		}
		formObj.submit();
		return true;
	}
} // End submitForm

/*
 * capitalizeInputs
 * 
 * Scope: 		Forms inputs (type=text)
 * Event:		Used on the onsubmit
 * Description: Change to UpperCase the input fields text on the form
 * Exceptions: 	The inputs with class "normalCase" are not converted to upcase.
 * 
 */
function capitalizeInputs() {
    jQuery("input:not('.normalCase')[type='text']")  
          .each(function() {this.value = this.value.toUpperCase()});  
}

/*
 * popPrintWindow
 * 
 * @obj			OBJECT
 * @title		STRING
 * @subtitle	STRING
 * 
 * Scope: 		DIVS tags
 * Description: Display a popup Window with title @title, with the HTML contents of the @obj
 * and gives the option of printing.
 * 
 * 
 */
function popPrintWindow(title, obj, BASE) {
	var d = new Date();
	var year = d.getFullYear();
	
	var print_header = "<br>CO - Lessons Learned<br>"+ title+"<hr>";
	var print_footer = "<br><hr>JGM " + year;
		
	var data = obj.innerHTML;
	data = print_header + data + print_footer;

    var mywindow = window.open('', 'Print', 'height=400,width=600, scrollbars=yes');
    mywindow.document.write('<html><head><title>Reporte Lecciones Aprendidas</title>');
    mywindow.document.write('<link rel="stylesheet" href="'+BASE+'/ui/css/bootstrap.min.css" type="text/css" />');
    mywindow.document.write('<link rel="stylesheet" href="'+BASE+'/ui/css/main.css" type="text/css" />');
    mywindow.document.write('</head><body >');
    mywindow.document.write('<input type="button" value="Imprimir" onclick="window.document.close();window.focus();window.print();window.close(); return true;" />');
    mywindow.document.write('<input type="button" value="Cancelar" onclick="window.close();return true" />');
    mywindow.document.write('<div id="print_area" style="overflow-x:auto">'+data+'</div>');
    mywindow.document.write('</body></html>');

    return true;
} // End popPrintWindow

function winPopUp (url,w,h) {
	var left = (window.screen.availWidth-w)/2; 
	var top = (window.screen.availHeight-h) / 2; 

	window.open(url,"_blank","directories=no, menubar=no, toolbar=no, status=no, titlebar=no, scrollbars=yes, resizable=yes, top="+top+", left="+left+", width="+w+", height="+h);
}
