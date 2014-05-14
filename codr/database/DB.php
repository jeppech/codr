<?php
/**
 * LiveRecord 1.0 Beta
 * ------------------------------
 * Author notes goes here...!
 * ------------------------------
 * @author Jeppe Christiansen
 * @package LiveRecord
 * @version 1.0 Beta
 * @copyright 2013 (c) codr
 * ------------------------------
 */

	class CODR_DB {

		/**
		 * Connction to the database. (PDO)
		 * @var object
		 */
		private $link;

		/**
		 * Contains connection information
		 * @var array
		 */
		private $cfg = array();

		/**
		 * True if the system should wrap fields and table
		 * names with backsticks.
		 * @var boolean
		 */
		private $_auto_sticks = true;

		/**
		 * Contains an array of operators used
		 * in a MySQL query
		 * @var array
		 */
		private $_valid_word_operators = array("LIKE", "LIKE BINARY", "IS NOT NULL", "NOT NULL", "IS NULL", "NOT LIKE");

		/**
		 * Contains an array of valid join types
		 * used in a MySQL query
		 * @var array
		 */
		private $_valid_join_types = array("left","right","outer","inner","left outer","right outer");

		/**
		 * Defines which mysql statements that is accepted
		 * for internal use for now.
		 * @var array
		 */
		private $_valid_statements = array(
			"select" 	=> "SELECT",
			"from" 		=> "FROM",
			"where" 	=> "WHERE",
			"and_where" => "AND",
			"or_where" 	=> "OR",
			"join" 		=> null,
			"order" 	=> "ORDER BY",
			"limit" 	=> "LIMIT",
			"group" 	=> "GROUP BY",
			"update"	=> "UPDATE",
			"insert" 	=> "INSERT INTO",
			"delete" 	=> "DELETE FROM",
			"set"		=> "SET",
			"truncate"	=> "TRUNCATE TABLE");

		/**
		 * Defines what type and data shoule be return
		 * when a query executes.
		 * 1 = object array
		 * 2 = insert_id
		 * 3 = affected_rows
		 * @var integer
		 */
		private $_return_what = 1;

		private $_binds = array();

		/**
		 * [$_lr_insert description]
		 * @var array
		 */
		private $_lr_insert = array();
		private $_lr_update = array();
		private $_lr_set = array();
		private $_lr_delete = array();
		private $_lr_truncate = array();

		/**
		 * Which fields should be selected
		 * including alias conversion
		 * @var array
		 */
		private $_lr_select = array();

		/**
		 * From which tables should data be fetched
		 * including alias conversion
		 * @var array
		 */
		private $_lr_from = array();

		/**
		 * Any WHERE statements..?
		 * @var array
		 */
		private $_lr_where = array();

		/**
		 * If multiple values were passed to where
		 * the rest will goto here.
		 * @var array
		 */
		private $_lr_and_where = array();

		/**
		 * Any OR statements will be dropped here.
		 * @var array
		 */
		private $_lr_or_where = array();

		/**
		 * Contains any kind of JOINS
		 * @var array
		 */
		private $_lr_join = array();

		/**
		 * Contains ORDER BY statement
		 * since this only can occur once, this is a string.
		 * @var array
		 */
		private $_lr_order = array();

		/**
		 * Contains LIMIT statement
		 * @var string
		 */
		private $_lr_limit = array();

		/**
		 * Contains GROUP BY statements
		 * @var array
		 */
		private $_lr_group = array();

		/**
		 * Keeps track of the order, which
		 * the statements was requested, so
		 * we dont switch e.g. OR/AND
		 * @var array
		 */
		private $_statement_order = array();

		/**
		 * Contains the last requested query
		 * @var string
		 */
		private $_last_query = array("query","binds");

		/**
		 * Contains the last inserted id if any
		 * @var integer
		 */
		private $_insert_id = 0;

		/**
		 * Constains the number of rows
		 * affected by the last query
		 * @var integer
		 */
		private $_affected_rows = 0;

		/**
		 * Contains the last query result if any
		 * @var object/array
		 */
		private $_query_result;

		/**
		 * Containes a custom query if any.
		 * @var string
		 */
		private $_custom_query = null;

		public function __construct()
		{
			$codr 	=& get_instance();
			$cfg 	=& $codr->config->load('database');

			if (!isset($cfg[$cfg['connection']]))
			{
				show_error('Database',
					'The selected connection <b>'.$cfg['connection'].'</b>, '
					.'is not valid.<br />Please check your configuration at <b>'.APPPATH.'config');
			}

			$this->cfg = $cfg[$cfg['connection']];

			$connectionString =
				'mysql:'.
				'host='.$this->cfg['hostname'].
				';dbname='.$this->cfg['database'].
				';charset='.$this->cfg['charset'];

			try
			{
				$this->link = new PDO($connectionString,$this->cfg['username'],$this->cfg['password']);

				$this->link->setAttribute(PDO::ATTR_ERRMODE,$this->cfg['error_mode']);
			}
			catch (PDOException $e)
			{
				log_message('error','Database: '.$e->getMessage());
				show_error('Database','Could not connect to the Database, ensure correct username and password is set.');
			}
		} // __construct()

		/**
		 * Run a custom query, binds are optional.
		 * ------
		 * @param  string 	$string Custom query string
		 * @param  array 	$binds 	Binds, same as bind() method
		 * @return object/array 	MySQL response.
		 */
		public function query($string, $binds = null)
		{
			if (isset($binds) && is_array($binds))
			{
				$this->bind($binds);
			}

			$this->_custom_query = $string;

			return $this;
		}

		/**
		 * Will return selected fields
		 * ------
		 * @param  string/array $fields Parse array or string with field names
		 * @return object		For method chaining
		 */
		public function select($fields = null)
		{
			if (!isset($fields))
			{
				show_error('Database','select: Missing parameter $fields');
			}

			if (is_array($fields))
			{
				foreach ($fields as $field => $fieldAlias)
				{
					// Assume this is a non-associative array,
					// and just select the values.
					//
					// Otherwise give the field an alias.
					if (is_integer($field))
					{
						$this->_set_statement('select',$this->_stick_field($fieldAlias));
					}
					else
					{
						$field 		= $this->_stick_field($field);
						$fieldAlias = $this->_stick_field($fieldAlias);

						$this->_set_statement('select',$field.' AS '.$fieldAlias);
					}
				}
			}
			else
			{
				$this->_set_statement('select',$this->_stick_field($fields));
			}

			$this->_track_statement('select');

			return $this;
		} // select($fields)

		/**
		 * Which tables should we fetch the data from,
		 * pass either a string or and array("table1","table2");
		 * ------
		 * @param  array/string $tables Parse array or string with table names
		 * @return object		For method chaining
		 */

		public function from($tables = null)
		{
			if (!isset($tables))
			{
				show_error('Database','from: Missing parameter $tables');
			}
				if (is_array($tables))
				{
					foreach ($tables as $table => $tableAlias)
					{
						if (is_integer($table))
						{
							$this->_set_statement('from',$this->_stick_field($tableAlias));
						}
						else
						{
							$table 		= $this->_stick_field($table);
							$tableAlias	= $this->_stick_field($tableAlias);
							$this->_set_statement('from',$table.' AS '.$tableAlias);
						}
					}
				}
				else
				{
					$this->_set_statement('from',$this->_stick_field($tables));
				}
				$this->_track_statement('from');

			return $this;
		} // from($tables)

		/**
		 * Setup a where statement, if this contains more than 1
		 * the rest of the values will automatically be assigned
		 * to the AND statement
		 * ------
		 * @param  array/string $field 	Parse array with fields to be matched, or a string
		 * @param  string 		$value 	If above is string, parse value to match
		 * @return object				For method chaining
		 */

		public function where($fields = null, $value = null)
		{
			if (!isset($fields))
			{
				show_error('Database','where: Missing parameter $fields');
			}

			$statement = 'where';
			if (is_array($fields))
			{
				$i = (empty($this->_lr_where) ? 0 : 1);

				foreach ($fields as $field => $value)
				{
					$field 		= $this->_stick_field($field);
					$operator 	= $this->_is_valid_operator($value);
					// If an operator were passed, we need
					// to remove this from the value string
					if ($operator)
					{
						$value = substr($value, (strlen($operator)+1));
					}
					$value 		= $this->_quote_value($value);

					if ($i > 0)
					{
						$statement = 'and_where';
					}

					// Peform a regex, to see if the value contains any
					// known operators stored in $_valid_operators
					//
					// If non is matched, use '=' as operator
					if ($operator)
					{
						$this->_set_statement($statement,$field.' '.$operator.' '.$value);
					}
					else
					{
						$this->_set_statement($statement,$field.' = '.$value);
					}

					$this->_track_statement($statement);
					$i++;
				}
			}
			else
			{
				if (!isset($value))
				{
					show_error('Database','where: Missing parameter $value');
				}

				$fields = $this->_stick_field($fields);

				if ($operator = $this->_is_valid_operator($value))
				{
					$value = substr($value, (strlen($operator)+1));
					$value = $this->_quote_value($value);

					$this->_set_statement($statement,$fields.' '.$operator.' '.$value);
				}
				else
				{
					$value = $this->_quote_value($value);
					$this->_set_statement($statement,$fields.' = '.$value);
				}
				$this->_track_statement($statement);
			}
			return $this;
		} // where($fields, $value)

		/**
		 * This is practically the same as the where method
		 * ------
		 * @param  array/string $field 	Parse array with fields to be matched, or a string
		 * @param  string 		$value 	If above is string, parse value to match
		 * @return object				For method chaining
		 */
		public function and_where($fields = null, $value = null)
		{
			return $this->where($fields,$value);
		} // and_where($fields, $value)

		/**
		 * Setup a OR WHERE statement
		 * ------
		 * @param  array/string $fields Array of fields, or string.
		 * @param  string 		$value  If above is string, this should be set too
		 * @return object 				For method chainging
		 */
		public function or_where($fields = null, $value = null)
		{
			if (!isset($fields))
			{
				show_error('Database','or_where: Missing parameter $fields');
			}

			$statement = 'or_where';

			if (is_array($fields))
			{
				foreach ($fields as $field => $value)
				{
					$field 		= $this->_stick_field($field);
					$operator 	= $this->_is_valid_operator($value);

					if ($operator)
					{
						$value = substr($value, (strlen($operator)+1));
					}

					$value 		= (preg_match("/[:\?]/", $value) ? $value : "'".$value."'");

					if ($operator)
					{
						$this->_set_statement($statement,$field.' '.$operator.' '.$value);
					}
					else
					{
						$this->_set_statement($statement,$field.' = '.$value);
					}
					$this->_track_statement($statement);
				}
			}
			else
			{
				if (!isset($value))
				{
					show_error('Database','or_where: Missing parameter $value');
				}

				$fields = $this->_stick_field($fields);
				$value 	= $this->_quote_value($value);

				if ($operator = $this->_is_valid_operator($value))
				{
					$this->_set_statement($statement,$fields.' '.$operator.' '.$value);
				}
				else
				{
					$this->_set_statement($statement,$fields.' = '.$value);
				}
				$this->_track_statement($statement);
			}
			return $this;
		} // or_where($fields, $value)
		/**
		 * Adds JOIN statement to the query
		 * ------
		 * @param  string $table		Database table name
		 * @param  string $condition	The condition for the join
		 * @param  string $type			Which type of join, lower case
		 * @return object				For method chaining
		 */

		public function join($table = null, $condition = null, $type = null)
		{
			if (!isset($table) || !isset($condition))
			{
				show_error('Database','join: Missing parameters');
			}

			$table 		= $this->_stick_field($table);
			$condition 	= $this->_stick_field($condition);

			if (isset($type) && is_string($type))
			{
				if (!in_array(strtolower($type), $this->_valid_join_types))
				{
					show_error('Database','join: The selected join type <b>'.$type.'</b>, is not valid');
				}
				$this->_set_statement('join',strtoupper($type).' JOIN '.$table.' ON '.$condition);
			}
			else
			{
				$this->_set_statement('join','JOIN '.$table.' ON '.$condition);
			}
			$this->_track_statement('join');

			return $this;
		} // join($table, $condition, $type)

		/**
		 * Sets up the ORDER BY statement
		 * ------
		 * @param  string/array $fields	Parse either a single field, or an array("field1" => "desc"), or a string "field1 desc, field2 asc"
		 * @param  string 		$value	If above is a single field, parse either 'desc' or 'asc'
		 * @return object				For method chaining
		 */

		public function order_by($fields = null, $order_as = null)
		{
			if (!isset($fields))
			{
				show_error('Database','order_by: Missing parameter $fields');
			}

			if (is_string($fields) && preg_match("/,/", $fields))
			{
				$buffer = explode(",", $fields);

				foreach ($buffer as $fields)
				{
					list($field,$value) = explode(" ", $fields);

					if (!in_array(strtolower($value), array('asc','desc')))
					{
						show_error('Database','order_by: The selected order type <b>'.$value.'</b> is not valid.');
					}
					$this->_set_statement('order',$this->_stick_field($field). " ".strtoupper($value));
				}
			}
			else if (is_array($fields))
			{
				$_buffer = null;
				$i = 1;

				foreach ($fields as $field => $value)
				{
					if (!is_integer($field))
					{
						if (!in_array(strtolower($value), array('asc','desc')))
						{
							show_error('Database','order_by: The selected order type <b>'.$value.'</b> is not valid.');
						}
						$this->_set_statement('order',$this->_stick_field($field)." ".strtoupper($value));
					}
					else
					{
						// Since $field is numeric index array, the keys must be the fields.
						$value = $this->_stick_field($value);

						$_order_as = (isset($order_as) ? $order_as : 'asc');

						if (!in_array(strtolower($_order_as), array('asc','desc')))
						{
							show_error('Database','order_by: The selected order type <b>'.$_order_as.'</b> is not valid.');
						}

						if ($i < count($fields))
						{
							$_buffer .= $value.', ';
						}
						else
						{
							$_buffer .= $value.' '.strtoupper($_order_as).' ';
						}
						$i++;
					}
				}
				if (!empty($_buffer))
				{
					$this->_set_statement('order',$_buffer);
				}
			}
			else
			{
				// Check if the value does match the
				// allowed types.
				//
				// If not, make the results ascending

				$fields = $this->_stick_field($fields);

				if (isset($order_as) && is_string($order_as))
				{
					if (!in_array(strtolower($order_as), array('asc','desc')))
					{
						show_error('Database','order_by: The selected order type <b>'.$order_as.'</b> is not valid.');
					}
					$this->_set_statement('order',$fields.' '.strtoupper($order_as));
				}
				else
				{
					$this->_set_statement('order',$fields.' ASC');
				}
			}

			$this->_track_statement('order');

			return $this;
		} // order_by($fields, $order_as)

		/**
		 * Set a limit for the query, where $offset defines
		 * which row to start from, and $limit is the amount of
		 * records to include after the $offset
		 *
		 * If the offset isen't set, the query records will be
		 * limited to the amount of $limit
		 * ------
		 * @param  integer 	$limit	Parse limit value
		 * @param  integer 	$offset	Parse limit offset value
		 * @return object			For method chaining.
		 */

		public function limit($limit = null, $offset = null)
		{
			// TODO - Check if limit is set!
			if (!isset($limit) || !is_integer($limit))
			{
				show_error('Database','limit: Missing or invalid parameter $limit');
			}

			if (isset($offset) && is_integer($offset))
			{
				$this->_set_statement('limit',$offset.', '.$limit);
			}
			else
			{
				$this->_set_statement('limit',$limit);
			}
			$this->_track_statement('limit');

			return $this;
		} // limit($limit, $offset)

		/**
		 * Should the query be grouped,
		 * pass either a string or an array("field1","field2")
		 * ------
		 * @param  array/string $fields Parse either null indexed array with field names or a string.
		 * @return object				For method chaining.
		 */

		public function group_by($fields = null)
		{
			if (!isset($fields))
			{
				show_error('Database','group_by: Missing parameter $fields');
			}

			if (is_array($fields))
			{
				foreach ($fields as $field)
				{
					$this->_set_statement('group',$this->_stick_field($field));
				}
			}
			else
			{
				$this->_set_statement('group',$this->_stick_field($fields));
			}
			$this->_track_statement('group');

			return $this;
		} // group_by($fields)

		/**
		 * When using PDO, it is possible to bind
		 * alias to data, so it is auto escaped.
		 *
		 * Existing parameters will be overwritten.
		 *
		 * E.g
		 * 	$db->bind(':uname','Username')
		 * 	$db->bind(array("uname" => "Username"))
		 * ------
		 * @param  array/string $alias [description]
		 * @param  string 		$value 	[description]
		 * @return object 				[description]
		 */
		public function bind($alias = null, $value = null)
		{
			if (!isset($alias))
			{
				show_error('Database','bind: Missing parameter $alias');
			}

			if (is_array($alias))
			{
				foreach ($alias as $b_alias => $b_value)
				{
					$this->_binds[$b_alias] = $b_value;
				}
			}
			else
			{
				if (isset($value) && !is_array($value))
				{
					$this->_binds[$alias] = $value;
				}
			}

			return $this;
		} // bind($alias, $value)

		/**
		 * This can be used to finish the query set, or just to
		 * fetch all fields from a table or multiple tables
		 *
		 * The method can also replace the methods from and limit,
		 * be passing in all parameters, if these haven't already
		 * been set
		 * ------
		 * @param  array/string 	$table	Parse table name
		 * @param  int 				$limit	Parse limit value
		 * @param  int 				$offset	Parse offset value
		 * @return object/array				Return data fetched from database
		 */

		public function get($table = null, $limit = null, $offset = null)
		{
			//TODO - Support multiple tables;

			if (isset($table))
			{
				$this
					->select('*')
					->from($table);

				if (isset($limit))
				{
					$this->limit($limit,$offset);
				}
			}

			return $this->_execute_query();
		} // get($table, $limit, $offset)

		/**
		 * This can be used to fetch all fields
		 * from a table or multiple tables, containing
		 * a where statement
		 *
		 * The method can also replace the methods from and limit,
		 * be passing in all parameters, if these haven't already
		 * been set
		 * ------
		 * @param  array/string $table  Parse table string or array
		 * @param  array 		$field 	Parse array with fields to be matched
		 * @param  integer 		$limit  How many records should be fetched
		 * @param  integer 		$offset What offset should the result start from
		 * @return object/array			Fetched data
		 */
		public function get_where($table = null, $fields = null, $limit = null, $offset = null)
		{

			$this
				->select('*')
				->from($table)
				->where($fields);

			if (!isset($limit) || !is_integer($limit))
			{
				return $this->_execute_query();
			}

			if (isset($offset) && is_integer($offset))
			{
				$this->limit($limit,$offset);
			}
			else
			{
				$this->limit($limit);
			}
			return $this->_execute_query();
		} // get_where($table, $fields, $limit, $offset)

		/**
		 * Adds a record to the database
		 * ------
		 * @param  string 			$table 	Database table name
		 * @param  array/string 	$field 	Either fieldname, or array("field" => "value")
		 * @param  string 			$value 	String fieldname (Optional)
		 * @return object        		For method chaining
		 */
		public function insert($table = null, $field = null, $value = null)
		{
			if (!isset($table) && !isset($field))
			{
				show_error('Database','Tried to insert data, invalid parameters');
			}

			$this->_set_statement('insert',$this->_stick_field($table));
			$this->_track_statement('insert');

			if (is_array($field))
			{
				$this->_stmt_set($field);
			} else {
				$this->_stmt_set($field,$value);
			}

			return $this;
		} // insert($table, $value)

		/**
		 * Updates an existing record in the database
		 * ------
		 * @param  string 	$table 	Database tablename to update
		 * @param  array 	$field 	Field/values to update, array("field" => "value")
		 * @param  array 	$where 	Conditions to match, see where() method
		 * @return object        	For method chaining
		 */
		public function update($table = null, $field = null, $where = null)
		{
			if (!isset($table) && !isset($field) && !isset($where))
			{
				show_error('Database','update: Missing parameters');
			}

			if (!is_array($field))
			{
				show_error('Database','update: Invalid datatype, expected array');
			}

			$this->_set_statement('update',$this->_stick_field($table));
			$this->_track_statement('update');

			$this->_stmt_set($field);
			if (is_array($where))
			{
				$this->where($where);
			}

			return $this;
		} // update($table, $field, $where)

		/**
		 * Deletes a record from a table
		 * ------
		 * @param  string 		$table 	Database tablename
		 * @param  array/string $where 	Either string or array("field" => "value"), see where() method
		 * @param  string 		$value 	If $where is a string, this should be the value to match
		 * @return object 				For method chaining
		 */
		public function delete($table = null, $where = null, $value = null)
		{
			if (!isset($table) && !isset($where))
			{
				show_error('Database','delete: Missing parameters');
			}
			$this->_set_statement('delete',$this->_stick_field($table));
			$this->_track_statement('delete');

			if (is_array($where))
			{
				$this->where($where);
			} else if ($where == "*")
			{
				return $this->_execute_query();
			} else {
				if (!isset($value))
				{
					show_error('Database','delete: Missing parameter $value');
				}
				$this->where($where,$value);
			}
			return $this;
		}

		/**
		 * Truncates any giving table
		 * @param  string $table Database tablename
		 * @return [type]        [description]
		 */
		public function truncate($table = null)
		{
			if (!isset($table))
			{
				show_error('Database','truncate: Missing parameter $table');
			}

			$this->_set_statement('truncate',$table);
			$this->_track_statement('truncate');

			// Execute the query, but do not return
			// the result of the query, because
			// truncate will always generate 0 or false.

			$this->_execute_query();

			// Since no errors has occured
			// we assume the query went right.
			return true;
		}
		/**
		 * Insert a record to the database,
		 * and execute it afterwards.
		 *
		 * This method can also be used to
		 * execute a query, just by running
		 * the method with no parameters.
		 * ------
		 * @param string 		$table 	Database tablename
		 * @param array 		$value 	Values to add to the record
		 * @return interger 			Returns last inserted ID
		 */
		public function set($table = null, $field = null, $value = null)
		{
			if (isset($table) && !is_array($table))
			{
				$this->insert($table,$field,$value);
			}
			return $this->_execute_query();
		} // set($table, $value)

		/**
		 * Turns the automated stick feature
		 * on and off
		 * ------
		 * @param  bool 	$value 	True or false
		 * @return object			For method chaining
		 */
		public function auto_sticks($value)
		{
			if (is_bool($value))
			{
				$this->_auto_sticks = $value;
			} else {
				show_error('Database','auto_sticks: A boolean should be set, instead saw <b>'.$value.'</b>');
			}
			return $this;
		} // auto_sticks($value)

		private function _stmt_set($field, $value = null)
		{
			if (is_array($field))
			{
				foreach ($field as $db_field => $db_value)
				{
					$this->_set_statement('set',$this->_stick_field($db_field).' = '.$this->_quote_value($db_value));
				}
			}
			else
			{
				$this->_set_statement('set',$this->_stick_field($field).' = '.$this->_quote_value($value));
			}
			$this->_track_statement('set');
		}

		/**
		 * Glues the entire query together, binds eventually
		 * values and executes the query.
		 * ------
		 * @return array/object Returns either data, insertID or affected rows
		 */
		public function _execute_query()
		{
			$_query_buffer 	= $this->_custom_query;
			$_statements 	=& $this->_statement_order;

			if (empty($_query_buffer))
			{
				// Iterate through all the requested statements
				// and fill the buffer var

				foreach ($_statements as $k => $v)
				{
					$_query_buffer .= $this->_query_build($v,$this->{'_lr_'.$v});
				}
			} else {
				$this->_detect_return_method();
			}

			$this->_last_query['query'] = $_query_buffer;
			$this->_last_query['binds'] = $this->_binds;

			$stmt = $this->link->prepare($_query_buffer);

			// If any binds, attach 'em to the query.
			foreach ($this->_binds as $alias => $value)
			{
				$stmt->bindValue($alias,$value);
			}
			//echo '<pre>'.$_query_buffer.'</pre>';

			$stmt->execute();
			$_return_what = $this->_return_what;
			// Flushes all previous saved variables
			// so the class is ready for a new query.
			$this->_flush_query();

			$_affected_rows = $stmt->rowCount();
			$_insert_id 	= $this->link->lastInsertId();

			if ($_return_what == 1)
			{
				while ($row = $stmt->fetch($this->cfg['fetch_mode']))
				{
					$this->_query_result[] = $row;
				}

				return $this->_query_result;
			}
			else if ($_return_what == 2)
			{
				if (!$_insert_id)
				{
					return false;
				}
				return $_insert_id;
			}
			else if ($_return_what == 3)
			{
				if (!$_affected_rows)
				{
					return false;
				}
				$this->_affected_rows = $_affected_rows;
				return $_affected_rows;
			}
		} // _execute_query()

		private function _detect_return_method()
		{
			if (preg_match("/UPDATE|DELETE|TRUNCATE+/", $this->_custom_query))
			{
				$this->_return_what = 3;
			} else if (preg_match("/INSERT/", $this->_custom_query))
			{
				$this->_return_what = 2;
			}
			return;
		}

		private function _flush_query()
		{
			$_statements 	=& $this->_valid_statements;

			foreach ($_statements as $k => $v)
			{
				if (isset($this->{'_lr_'.$k}))
				{
					$this->{'_lr_'.$k} = array();
				}
			}
			$this->_custom_query = null;
			$this->_query_result = array();
			$this->_statement_order = array();
			$this->_binds = array();
			$this->_return_what = 1;
		} // _flush_query()

		/**
		 * Builds parts of the MySQL query string
		 * ------
		 * @param  string 	$statement 	Name of the statement array
		 * @param  array 	$data 		The data to add to the query
		 * @return string 				Part of the query string
		 */
		private function _query_build($statement, &$data)
		{
			$i 			= 1;
						// Returns begining of SQL statement e.g. SELECT/INSERT INTO/DELETE FROM
			$_buffer 	= (!isset($this->_valid_statements[$statement]) ? null : $this->_valid_statements[$statement].' ');
			$count 		= count($data);

			/**
			 * If the statement matching any of the string
			 * stored in the array, use the following
			 * procedure to write the query.
			 *
			 * The first one are for functions which
			 * is comma seperated
			 */
			if (in_array($statement, array('select','from','order','group','set','insert')))
			{
				switch ($statement) {
					case 'insert':
						$this->_return_what = 2;
						break;
				}

				foreach ($data as $k => $v)
				{
					if ($i < $count)
					{
						$_buffer .= $v.', ';
					}
					else
					{
						$_buffer .= $v.' ';
					}
					$i++;
				}
			}
			else if (in_array($statement, array('where','and_where','or_where','join','where','update','delete','truncate','limit')))
			{
				switch ($statement) {
					case 'update':
					case 'delete':
					case 'truncate':
						$this->_return_what = 3;
						break;
				}

				foreach ($data as $k => $v)
				{
					$_buffer .= $v.' ';
				}
			}
			return $_buffer;
		} // _query_build($statement, &$data)

		/**
		 * Returns the last used query, and
		 * binds if any.
		 * @return string Complete query string,
		 */
		public function last_query()
		{
			if (!empty($this->last_query['query']))
			{
				return $this->last_query;
			}
			return false;
		}

		/**
		 * A little workaround to check if the query
		 * returned any rows, so we know if we can
		 * run $stmt->fetch();
		 * -------
		 * @return interger Number of rows returned.
		 */
		private function _query_returned_rows()
		{
			return $this->link->query('SELECT FOUND_ROWS()')->fetchColumn();
		}

		/**
		 * Appends the requested data to the
		 * statements arrays
		 * ------
		 * @param array 	$statement 	Name of the SQL statement
		 * @param string 	$data 		The data to be passed to statement array
		 */
		private function _set_statement($statement, $data)
		{
			if (isset($statement) && isset($data))
			{
				if (array_key_exists($statement, $this->_valid_statements))
				{
					array_push($this->{'_lr_'.$statement},$data);
				}
				else
				{
					log_message('debug','DB->_set_statement: Statement passed, '.$statement.', are invalid.');
				}
			}
			else
			{
				log_message('debug','DB->_set_statement: No statement or parameters passed.');
			}
		} // _set_statement($statement, $data)

		/**
		 * Keep track of the order, of which the
		 * statements were requested.
		 * ------
		 * @param  array/string 	$statement	[description]
		 * @return void 						[description]
		 */
		private function _track_statement($statement)
		{
			if (!in_array($statement, $this->_statement_order))
			{
				array_push($this->_statement_order, $statement);
			}
		} // _track_statement($statement)

		/**
		 * Checks if the operator passed through
		 * the where method is a valid one.
		 *
		 * TODO - Add support for LIKE, BINARY LIKE
		 * ------
		 * @param  [type]  $value [description]
		 * @return boolean        [description]
		 */
		private function _is_valid_operator($value) {
			if (isset($value)) {

				$_word_operators = implode("|", $this->_valid_word_operators);

				if (preg_match("/^([=><]*?|\!\=|".$_word_operators.") .+$/", $value, $match)) {
					return array_pop($match);
				}
			}
			return false;
		} // _is_valid_operator($value)

		/**
		 * Wrapping fields and table names
		 * with backsticks (`)
		 * ------
		 * @param  string $field Field
		 * @return string        Formatted field
		 */
		private function _stick_field($field)
		{
			if (!preg_match('/`/', $field) && ($field != '*') && $this->_auto_sticks)
			{
				// Match any table names associated with a field name.
				if (preg_match('/^[a-zA-Z0-9\-_\*]+\.[a-zA-Z0-9\-_\*]+$/', $field))
				{
					$_arr_field = explode(".", $field);
					foreach ($_arr_field as $k => $v)
					{
						if ($v != '*')
						{
							$_arr_field[$k] = '`'.$v.'`';
						}
					}
					return implode(".", $_arr_field);
				}

				/**
				 * This is primarily used for join conditions.
				 * The following tries to match as many table and field
				 * names as posible, then splits them up, add backsticks
				 * puts them togheter and replace the original string
				 * with the formatted one.
				 */
				preg_match_all('/([a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+)/', $field, $match);

				// Did we find any match?
				if (!empty($match[0]))
				{
					$_replace = array();
					// Search through the matches
					foreach ($match[0] as $k => $v) {
						$_arr_field = explode('.', $v);
						$_buffer = array();
						// Wrap field/table with backsticks
						foreach ($_arr_field as $value)
						{
							if ($value != '*')
							{
								// Add the formatted field/value to the buffer
								$_buffer[] = "`".$value."`";
							}
						}
						// Store the formatted field/value in the replace var
						$_replace[$k] = implode('.', $_buffer);
					}

					$patterns = array_map(array($this, '_stick_field_regex_match'), $match[0]);

					// Replace the original string with the formatted string
					return preg_replace($patterns, $_replace, $field);

				} else {
					return '`'.$field.'`';
				}
			}
			return $field;
		} // _stick_field($field)

		private function _stick_field_regex_match($field)
		{
			return '/\b'.$field.'\b/';
		}
		/**
		 * Quotes the necessary values if it's
		 * not matching a bound param
		 * ------
		 * @param  string $value Field value
		 * @return string        Formatted value
		 */
		private function _quote_value($value)
		{
			if (!preg_match('/[:\?]|\#\W?[a-zA-Z]+/', $value))
			{
				return "'".$value."'";
			}
			$value = str_replace("#","",$value);
			return $value;
		} // _quote_value($value)
	}
?>