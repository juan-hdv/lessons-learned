<?php
/*
 * TableMapper
 * Implements the CRUD DB operations for any DB table with a single o compossed key
 * __construct ::
 * @tableName STRING 
 * @keyNames  STRING|ARRAY(of string)  :: Example "field1, field": Compossed key: comma separated string
 * 
 * Example of use
 * 		$p = new TableMapper ('proy_lec','idproyecto, idleccion');
 *		$result = $p->dbUpdate (array($idproy, $idlecc));  
 */
class TableMapper extends DB\SQL\Mapper 
{
	protected $_tableKeys = ""; // SQL SYSNTAX
	protected $_tableName = "";
	
    public function __construct(DB\SQL $db, $tableName, $tableKeys) {
        parent::__construct($db, $tableName);
		$tableKeys = str_replace(' ', '', $tableKeys);
		if (strpos($tableKeys, ",")) // Is a list of columns
			// A string in sql-where-clause-format 
			$this->_tableKeys = join ("=? and ", explode (",",$tableKeys)) . "=? ";
		else // Is only one columns
		   $this->_tableKeys = $tableKeys . "=? ";
		$this->_tableName = $tableName;
    }
 
 	// ************  CRUD *****************
	/*
	 * @return 	array of objects (each object with all the atributes of the table)
	 * @filter: string|array: array('col1=? and col2=?...',$col1, $col2...)
	 * @options: array: (	'order' => string $orderClause, 
	 * 						'group' => string $groupClause,
     * 						'limit' => integer $limit,
	 * 						'offset' => integer $offset)
	 */
	public function dbRead($filter=null, array $options=NULL) {
        $this->load($filter, $options);
        return $this->query;
    }
 
 	public function dbCreate() {
    // Create/Add new element form POST variables
        $this->reset();
        $this->copyFrom('POST');
        $this->save();
    }
 	
	/*
	 * @keysValues = STRING|ARRAY
	 * 		STRING :: value
	 * 		ARRAY :: array(value1, value2, ...)  
	 */
    public function dbUpdate($keysValues) {
    	if (is_array($keysValues))
			$filter = array_merge(array($this->_tableKeys),$keysValues);
		else
			$filter = array($this->_tableKeys,$keysValues);
        $this->load($filter);
        $this->copyFrom('POST');
        $this->update();
    }
 
    public function dbDelete($keysValues) {
    	if (is_array($keysValues))
			$filter = array_merge(array($this->_tableKeys),$keysValues);
		else
			$filter = array($this->_tableKeys,$keysValues);
	    $this->load($filter);
	    $this->erase();
    }

    public function dbGetById($keysValues) {
    	if (is_array($keysValues))
			$filter = array_merge(array($this->_tableKeys),$keysValues);
		else
			$filter = array($this->_tableKeys,$keysValues);
        $this->load($filter);
        $this->copyTo('POST');
    }
}

