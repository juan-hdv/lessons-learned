<?php
/*
 *   ViewMapper
 *		tableKeys STRING: list of keys. Example: "key, key2..."
 */
class ViewMapper extends DB\SQL\Mapper 
{
	protected $_tableKeys = "";  // SQL SYNTAX
	protected $_tableName = "";
	
	/*
	 * @tableKeys STRING comma separated list of column names
	 */ 
    public function __construct(DB\SQL $db, $tableName, $tableKeys) {
        parent::__construct($db, $tableName);
		$tableKeys = str_replace(' ', '', $tableKeys);
		if (strpos($tableKeys, ",")) // Is a list of columns
			// A string in sql where clause format 
			$this->_tableKeys = join ("=? and ", explode (",",$tableKeys)) . "=? ";
		else // Is only one columns
		   $this->_tableKeys = $tableKeys . "=? ";
		$this->_tableName = $tableName;
    }

 	// A continuacion el CRUD - Para las vistas, solamente READ Y GETBYID
 	
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
 
    public function dbGetById($keysValues) {
    	if (is_array($keysValues))
			$filter = array_merge(array($this->_keysColumns),$keysValues);
		else
			$filter = array($this->_keysColumns,$keysValues);
        $this->load($filter);
        $this->copyTo('POST');
    }

}
