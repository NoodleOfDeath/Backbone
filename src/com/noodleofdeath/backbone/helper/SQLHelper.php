<?php

namespace com\noodleofdeath\backbone\helper;

use com\noodleofdeath\backbone\model\resource\exception\SQLException;

/** Simple data structure for an SQLHelper query with metadata. */
class SQLQuery {

    /** @var string query of this SQLHelper query. */
    public $query;

    /** @var string type of this SQLHelper query. */
    public $type;

    /**
     *
     * @param string $query
     * @param string $type */
    public function __construct(string $query, string $type) {
        $this -> query = $query;
        $this -> type = $type;
    }

    public function __toString() {
        return $this -> query;
    }

}

/** */
class SQLResponse {

    /** @var bool */
    public $success;

    /** @var array */
    public $insert_ids;

    /** @var array */
    public $affected_rows;

    /**
     *
     * @param bool $success
     * @param array $insert_ids
     * @param array $affected_rows */
    public function __construct(bool $success = false, array $insert_ids = [],
        array $affected_rows = []) {
        $this -> success = $success;
        $this -> insert_ids = $insert_ids;
        $this -> affected_rows = $affected_rows;
    }

}

/** Simple data structure for building SQLHelper expressions and queries. */
class SQLExpression {

    /** @var string key of this SQLHelper expression. */
    private $key;

    /** @var bool <code>true</code> if key should have back quotes added; <code>false</code>, otherwise. */
    private $quoteKey = true;

    /** @var number|string value of this SQLHelper expression. */
    private $value;

    /** @var bool <code>true</code> if value should have single quotes added; <code>false</code>, otherwise. */
    private $quoteValue = true;

    /** @var string relation between $key and $value. */
    private $relation;

    /** Constructs a new simple SQLHelper expression with an initial key, value,
     * and relation.
     *
     * @param string $key
     *            of this SQLHelper expression.
     * @param string|number $value
     *            of this SQLHelper expression.
     * @param string $relation
     *            between the specified key and value. */
    public function __construct($key, $value, $relation = SQLHelper::EQU) {
        $this -> key = is_array($key) ? $key[0] : $key;
        $this -> quoteKey = is_array($key) ? $key[1] : true;
        $this -> value = is_array($value) ? $value[0] : $value;
        $this -> quoteValue = is_array($value) ? $value[1] : true;
        $this -> relation = $relation;
    }

    public function __toString() {
        return sprintf('%s %s %s', SQLHelper::Key($this -> key),
            $this -> relation, SQLHelper::Value($this -> value));
    }

}

/** */
class SQLTableIndex {

    /** @var string */
    public $key_name;

    /** @var string */
    public $column_name;

    /**
     *
     * @param array $data */
    public function __construct(array $data, string $key_name = 'Key_name',
        string $column_name = 'Column_name') {
        $this -> key_name = $data[$key_name];
        $this -> column_name = $data[$column_name];
    }

}

/** Simple composite class for convenient handling of mysqli sessions. Usage is
 * just like mysqli, but connections are autoclosing and the returned resource
 * handler has convenience methods like 'self::select', 'self::insert',
 * 'self::update', and 'self::delete'. */
class SQLHelper {

    /** @var string "AND" logic operator used in SQLHelper where clauses. */
    public const AND = 'AND';

    /** @var string "OR" logic operator used in SQLHelper where clauses. */
    public const OR = 'OR';

    /** @var string "=" comparison operator used in SQLHelper expressions. */
    public const EQU = '=';

    /** @var string "!=" comparison operator used in SQLHelper expressions. */
    public const NEQ = '!=';

    /** @var string "<" comparison operator used in SQLHelper expressions. */
    public const LT = '<';

    /** @var string  "<=" comparison operator used in SQLHelper expressions. */
    public const LTE = '<=';

    /** @var string ">" comparison operator used in SQLHelper expressions. */
    public const GT = '>';

    /** @var string ">=" comparison operator used in SQLHelper expressions. */
    public const GTE = '>=';

    /** @var string "LIKE" comparison operator used in SQLHelper expressions. */
    public const LIKE = 'LIKE';

    /** @var string host name of IP address of the target server. */
    private $hostname;

    /** @var string username to log into the target with. */
    private $username;

    /** @var string password to log into the target with. */
    private $password;

    /** @var string database to subscribe to after log in. */
    private $database;

    /** @var string directory to log all SQLHelper queries to. */
    private $logs_dir;

    // Static Methods

    /** Adds escape characters to key metacharacters and surrounding back quotes
     * to a specified $key and returns the result.
     *
     * @param string $key
     *            to add escape characters to and surround with back quotes.
     * @param bool $quote
     *            <code>true</code> to add back quotes around the passed value
     *            if it is not a number; <code>false</code>, otherwise.
     * @return string the passed string with escaped characters added and
     *         surrounded by back quotes. */
    public static function Key($key, $quote = true) {
        return sprintf($quote ? '`%s`' : '%s',
            preg_replace('/([`])/', '\$1', addslashes($key)));
    }

    /** Adds escape characters to key metacharacters and surrounding single
     * quotes to a specified $value (unless a numerical value is passed) and
     * returns the result.
     *
     * @param string|number $value
     *            to add escape characters to and surround with single quotes
     *            (unless a numerical value is passed).
     * @param bool $quote
     *            <code>true</code> to add single quotes around the passed value
     *            if it is not a number; <code>false</code>, otherwise.
     * @return string the passed value with escaped characters added and
     *         surrounded by single quotes (unless a numerical value was
     *         passed). */
    public static function Value($value, $quote = true) {
        if (is_numeric($value))
            return $value;
        elseif (is_bool($value))
            return $value ? '1' : '0';
        if (!$value)
            return 'null';
        return sprintf($quote ? "'%s'" : '%s', addslashes($value));
    }

    /** Convenience method for building simple sql expressions.
     *
     * @param string $key
     *            of this SQLHelper expression.
     * @param string|number $value
     *            of this SQLHelper expression.
     * @param string $relation
     *            between the specified key and value.
     * @return SQLExpression constructed from the passed parameters. */
    public static function Build($key, $value, $relation = self::EQU) {
        return new SQLExpression($key, $value, $relation);
    }

    /** Adds escape characters to key metacharacters and surrounding back quotes
     * to a sequential array of column names, then joins the array with a comma
     * delimiter and returns the result.
     *
     * @param string[] $columns
     *            sequential array of column names to escape and join.
     * @return string the passed sequential array of column names joined by a
     *         comma delimiter and with added escape characters and surrounding
     *         back quotes around each column name. */
    public static function FormatColumns($columns) {
        if (is_array($columns)) {
            $cols = [];
            foreach ($columns as $col)
                array_push($cols, self::Key($col));
            return implode(', ', $cols);
        }
        return $columns;
    }

    /** Formats and adds escape characters and surrounding back/single quotes to
     * an associative array of column names and values into a separated
     * '(columns) VALUES (values)' SQLHelper clause and returns the results.
     *
     * @param string|mixed[] $values
     *            associative array containing column name and value pairs.
     * @return string a separated '(columns) VALUES (values)' SQLHelper clause
     *         generated using the column name and value pairs passed in single
     *         parameter with added escape characters and surrounding
     *         back/songle quotes. */
    public static function FormatValues($values) {
        if (is_array($values)) {
            $cols = [];
            $vals = [];
            foreach ($values as $k => $v) {
                array_push($cols, self::Key($k));
                array_push($vals, self::Value($v));
            }
            return sprintf('(%s) VALUES (%s)', implode(', ', $cols),
                implode(', ', $vals));
        }
        return $values;
    }

    /** Formats and adds escape characters and surrounding back/single quotes to
     * an associative array of column names and values into a comma separated
     * '`column` = `value`' SQLHelper clause and returns the results.
     *
     * @param string|mixed[=>]|mixed[] $values
     *            a string, associative array, or sequential array of
     *            SQLExpression instances representing column name and value
     *            pairs.
     * @return string a comma separated '`column` = `value`' SQLHelper clause
     *         generated using the column name and value pairs passed with added
     *         escape characters and surrounding back/single quotes. */
    public static function FormatKeyValuePairs($values) {
        if (is_array($values)) {
            $pairs = [];
            foreach ($values as $k => $v)
                array_push($pairs,
                    sprintf('%s = %s', self::Key($k), self::Value($v)));
            return implode(', ', $pairs);
        }
        return $values;
    }

    /** Formats and adds escape characters and surrounding back/single quotes to
     * an associative array of column names and values into a '(`column`
     * comparisonOperator `value`) logicOperator (...)' SQLHelper clause of
     * multiple conditional expressions joined by a logic operator(s).
     *
     * @param string|mixed[] $where
     *            clause to constrain a query to. If a string is passed, it
     *            should be a complete conditional expression (i.e. `EMAIL =
     *            'user@example.com'). If multiple conditional expressions are
     *            to be used, a sequential array of SQLExpression instances
     *            should be passed.
     * @return string */
    public static function FormatWhere($where) {
        if (is_array($where)) {
            $expressions = [];
            foreach ($where as $expr)
                array_push($expressions, $expr);
            return implode(' ', $expressions);
        }
        return $where;
    }

    /**
     *
     * @param mixed[] $parts
     * @return string */
    public static function Group(...$parts) {
        if (count($parts) == 1 && is_array($parts[0]))
            return sprintf('(%s)',
                implode(sprintf(' %s ', self::AND), ...$parts));
        return sprintf('(%s)', implode(sprintf(' %s ', self::AND), $parts));
    }

    /**
     *
     * @param mixed[] ...$parts
     * @return string */
    public static function GroupAlternatives(...$parts) {
        if (count($parts) == 1 && is_array($parts[0]))
            return sprintf('(%s)', implode(sprintf(' %s ', self::OR), ...$parts));
        return sprintf('(%s)', implode(sprintf(' %s ', self::OR), $parts));
    }

    /**
     *
     * @param string $key
     * @param mixed[] $parts
     * @param $inclusive <code>true</code>
     *            if the range should be inclusive; <code>false</code>,
     *            otherwise. Default is <code>true</code>.
     * @param
     *            string|||null */
    public static function RangeGroup($key, $parts = [], $inclusive = true) {
        if (count($parts) < 2)
            return null;
        return sprintf('(%s AND %s)',
            self::Build($key, $parts[0], $inclusive ? self::GTE : self::GT),
            self::Build($key, $parts[1], $inclusive ? self::LTE : self::LT));
    }

    public static function TimeDiffRangeGroup($key, $parts = [], $inclusive = true) {}

    /**
     *
     * @param string $table
     * @param string|mixed[] $columns
     * @param string|mixed[] $where
     * @param string|mixed[]|null $more
     * @return string */
    public static function BuildQuerySelect(string $table, $columns, $where,
        $more = null) {
        $query = sprintf('SELECT %s FROM %s WHERE %s %s',
            self::FormatColumns($columns), self::Key($table),
            self::FormatWhere($where), $more);
        return trim($query);
    }

    /**
     *
     * @param string $table
     * @param string $column
     * @param string|mixed[] $where
     * @param string $count_col
     * @param string|mixed[]|null $more
     * @return string */
    public static function BuildQueryCount(string $table, string $column, $where,
        string $count_col = 'count', $more = null) {
        $query = sprintf('SELECT COUNT(%s) AS %s FROM %s WHERE %s %s', $column,
            self::Key($count_col), self::Key($table), self::FormatWhere($where),
            $more);
        return trim($query);
    }

    /**
     *
     * @param string $table
     * @param string|mixed[] $values
     * @param string|mixed[]|null $more
     * @return string */
    public static function BuildQueryInsert(string $table, $values, $more = null) {
        $query = sprintf('INSERT INTO %s %s %s', self::Key($table),
            self::FormatValues($values), $more);
        return trim($query);
    }

    /**
     *
     * @param string $table
     * @param string|mixed[] $values
     * @param string|mixed[] $where
     * @param string|mixed[]|null $more
     * @return string */
    public static function BuildQueryUpdate(string $table, $values, $where,
        $more = null) {
        $query = sprintf('UPDATE %s SET %s WHERE %s %s', self::Key($table),
            self::FormatKeyValuePairs($values), self::FormatWhere($where), $more);
        return trim($query);
    }

    /** Deletes a record in the specified table.
     *
     * @param string $table
     * @param string|mixed[] $where
     * @param string|mixed[]|null $more
     * @return string */
    public static function BuildQueryDelete(string $table, $where, $more = null) {
        $query = sprintf('DELETE FROM %s WHERE %s %s', self::Key($table),
            self::FormatWhere($where), $more);
        return trim($query);
    }

    /**
     *
     * @param string $table
     * @param string|mixed[] $columns
     * @param string $ref
     * @param string|mixed[]|null $more
     * @return string */
    public static function BuildQueryCreateTable(string $table, $columns,
        string $ref = null, $more = null) {
        $parts = [];
        foreach ($columns as $k => $v)
            array_push($parts, sprintf('%s %s', self::Key($k), $v));
        $query = sprintf('CREATE TABLE %s ( %s )', self::Key($table),
            implode(', ', $parts));
        if ($ref)
            $query = sprintf('%s AS SELECT * FROM %s LIMIT 0', $query,
                self::Key($ref));
        if ($more)
            $query = sprintf('%s %s', $query, $more);
        return trim($query);
    }

    /**
     *
     * @param string $table
     * @param string|mixed[]|null $more
     * @return string */
    public static function BuildQueryShowColumns(string $table, $more = null) {
        $query = sprintf('SHOW COLUMNS FROM %s %s', self::Key($table), $more);
        return trim($query);
    }

    /**
     *
     * @param string $table
     * @param string|mixed[]|null $more
     * @return string */
    public static function BuildQueryShowIndex(string $table, $more = null) {
        $query = sprintf('SHOW INDEX FROM %s %s', self::Key($table), $more);
        return trim($query);
    }

    /**
     *
     * @param string $table
     * @param SQLTableIndex $index
     * @param string|mixed[]|null $more
     * @return string */
    public static function BuildQueryAddIndex(string $table, SQLTableIndex $index,
        $more = null) {
        $query = sprintf('ALTER TABLE %s ADD INDEX %s (%s)', self::Key($table),
            self::Key($index -> key_name), self::Key($index -> column_name));
        return trim($query);
    }

    /**
     *
     * @param string $table
     * @param string $index
     * @param string|mixed[]|null $more
     * @return string */
    public static function BuildQueryDropIndex(string $table, string $index,
        $more = null) {
        $query = sprintf('ALTER TABLE %s DROP INDEX %s', self::Key($table),
            self::Key($index));
        return trim($query);
    }

    /**
     *
     * @param string $table
     * @param string $operation
     * @param string $column
     * @param string|mixed $datatype
     * @param string|mixed[]|null $more
     * @return string */
    public static function BuildQueryAlterTable(string $table, string $operation,
        string $column, $datatype, $more = null) {
        $query = sprintf('ALTER TABLE %s %s COLUMN %s %s', self::Key($table),
            $operation, self::Key($column), $datatype);
        return trim($query);
    }

    /**
     *
     * @param string $table
     * @param string|mixed[]|null $more
     * @return string */
    public static function BuildQueryDropTable(string $table, $more = null) {
        $query = sprintf('DROP TABLE %s %s', self::Key($table), $more);
        return trim($query);
    }

    /** Constructs a new SQLHelper session handler.
     *
     * @param string $host
     *            host name or IP address of the target server.
     * @param string $username
     *            to log into the target with.
     * @param string $password
     *            to log into the target with.
     * @param string $database
     *            to subscribe to after log in.
     * @param string $logs_dir
     *            directory to log all SQLHelper queries to. */
    public function __construct($hostname, $username, $password, $database,
        $logs_dir = null) {
        $this -> hostname = $hostname;
        $this -> username = $username;
        $this -> password = $password;
        $this -> database = $database;
        $this -> logs_dir = $logs_dir;
    }

    /** Runs and logs an SQLHelper query using a specified existing
     * <code>\mysqli</code> resource or ephemeral local <code>\mysqli</code>
     * resource instance if an existing one is not supplied.
     *
     * @param string $query
     *            to run.
     * @param \mysqli $con
     *            existing <code>\mysqli</code> resource to run the query.
     * @return \mysqli_result|null a <code>\mysqli_result</code> if the query
     *         succeeds; <code>null</code>, otherwise. */
    public function query(string $query, \mysqli $con = null) {
        $existing_con = !is_null($con);
        $con = $existing_con ? $con : self::connect();
        $result = $con -> query($query);
        if (!$existing_con)
            $con -> close();
        if (!$result)
            throw new SQLException($con -> error);
        if ($this -> LogsDir)
            self::log($query);
        return $result;
    }

    /** Performs a sequential array of queries as a transaction.
     *
     * @param string[] $queries
     *            to run.
     * @return SQLResponse response object that contains metdadata about the
     *         transaction event upon commit. */
    public function perform_transaction(...$queries) {
        if (count($queries) == 1 && is_array($queries[0]))
            $queries = $queries[0];
        $con = self::connect();
        $con -> autocommit(false);
        $con -> begin_transaction();
        $insert_ids = [];
        $affected_rows = [];
        try {
            foreach ($queries as $query) {
                self::query($query, $con);
                array_push($insert_ids, $con -> insert_id);
                array_push($affected_rows, $con -> affected_rows);
            }
        } catch (\Exception $e) {
            $con -> rollback();
            self::log('FATAL ERROR: Transaction failed, rolling back changes.');
            self::log($e -> getMessage());
        }
        $success = $con -> commit();
        $response = new SQLResponse($success, $insert_ids, $affected_rows);
        $con -> close();
        return $response;
    }

    /** Logs an SQLHelper query if a log directory was specified.
     *
     * @param string $query
     *            query to log. */
    public function log($query) {
        $logs_dir = sprintf('%s/%s/%s/%s', $this -> logs_dir, date('Y', time()),
            date('m', time()), date('d', time()));
        if (!is_dir($logs_dir))
            mkdir($logs_dir, 0755, true);
        $log_file = sprintf('%s/%s.log', $logs_dir, date('Y-m-d H', time()));
        $message = sprintf("[%s] %s\r\n", date('Y-m-d H:i:s', time()), $query);
        file_put_contents($log_file, $message, FILE_APPEND | LOCK_EX);
    }

    /** Executes an SQLHelper select query on a specified table for specific
     * columns constrained by a specified where clause and comparison
     * operator(s).
     *
     * @param string $table
     *            to run the query on.
     * @param string|string[] $columns
     *            to retrieve from the specified table. If a string is passed,
     *            it should be a single column or expression. If multiple
     *            specific columns are to be selected, a sequential array of
     *            strings representing the column names should be passed.
     * @param string|mixed[] $where
     *            clause to constrain the query to. If a string is passed, it
     *            should be a complete conditional expression (i.e. `EMAIL =
     *            'user@example.com'). If multiple conditional expressions are
     *            to be used, an associative array should be passed (with the
     *            key-value pairs representing a column name and value,
     *            respectively) as well as a comparison operator(s), and logic
     *            operator(s) (see next two parameters) that specifies how to
     *            construct and join each conditional statment.
     * @param mixed[] $more
     *            additional SQLHelper statements such as ORDER BY and/or JOIN
     *            statements.
     * @return object|mixed[] If a single row was fetched, an associative object
     *         representing that row will be returned. If multiple rows were
     *         fetched, a sequential array of associative objects will be
     *         returned. `false` will be returned, otherwise. */
    public function select(string $table, $columns, $where, $more = null) {

        // Construct query.
        $query = self::BuildQuerySelect($table, $columns, $where, $more);
        $result = [];

        // Execute query.
        if ($link = self::query($query))
            while ($row = $link -> fetch_assoc())
                array_push($result, $row);

        // Return result.
        return $result;
    }

    /** Executes an SQLHelper count query on a specified table for specific
     * columns constrained by a specified where clause and comparison
     * operator(s).
     *
     * @param string $table
     *            to run the query on.
     * @param string|string[] $column
     * @param string|mixed[] $where
     *            clause to constrain the query to. If a string is passed, it
     *            should be a complete conditional expression (i.e. `EMAIL =
     *            'user@example.com'). If multiple conditional expressions are
     *            to be used, an associative array should be passed (with the
     *            key-value pairs representing a column name and value,
     *            respectively) as well as a comparison operator(s), and logic
     *            operator(s) (see next two parameters) that specifies how to
     *            construct and join each conditional statment.
     * @param string $count_col
     * @param string|mixed[]|null $more
     *            additional SQLHelper statements such as ORDER BY and/or JOIN
     *            statements.
     * @return number number of rows that match the criteria. */
    public function count(string $table, $column, $where,
        string $count_col = 'count', $more = null) {
        // Construct query.
        $query = self::BuildQueryCount($table, $column, $where, $count_col,
            $more);
        $result = 0;
        // Execute query.
        if ($link = self::query($query))
            $result = ($link -> fetch_assoc())[$count_col];
        return $result;
    }

    /** Executes an SQLHelper insert query into a specified table and with an
     * associative array of specified column-value pairs.
     *
     * @param string $table
     *            to run the query on.
     * @param string|mixed[] $values
     *            associative array where each key-value pair represents a
     *            column name and value to be inserted into the specified table.
     * @param string|mixed[]|null $more
     * @return number|null primary key id of the record that was just inserted
     *         into the table, or <code>null</code> if the query failed. */
    public function insert(string $table, $values, $more = null) {

        // Construct query.
        $query = self::BuildQueryInsert($table, $values);

        // Execute query.
        $con = self::connect();
        if (self::query($query, $con))
            $id = $con -> insert_id;
        $con -> close();

        // Return result.
        return $id;
    }

    /** Executes an SQLHelper update query on a specified table with an
     * associative array of specified column-value pairs constrained by a
     * specified where clause and comparison operator(s).
     *
     * @param string $table
     *            to run the query on.
     * @param string|mixed[] $values
     *            associative array where each key-value pair represents a
     *            column name and value to be inserted into the specified table.
     * @param string|mixed[] $where
     *            clause to constrain the query to. If a string is passed, it
     *            should be a complete conditional expression (i.e. `EMAIL =
     *            'user@example.com'). If multiple conditional expressions are
     *            to be used, an associative array should be passed (with the
     *            key-value pairs representing a column name and value,
     *            respectively) as well as a comparison operator(s), and logic
     *            operator(s) (see next two parameters) that specifies how to
     *            construct and join each conditional statment.
     * @param string|mixed[]|null $more
     * @return number|false number of rows affected by the executed query, or
     *         <code>false</code> if the query fails. */
    public function update(string $table, $values, $where, $more = null) {

        // Construct query.
        $query = self::BuildQueryUpdate($table, $values, $where, $more);
        $row_count = false;

        // Execute query.
        $con = self::connect();
        if (self::query($query, $con))
            $row_count = $con -> affected_rows;
        $con -> close();

        // Return result.
        return $row_count;
    }

    /** Executes an SQLHelper delete query on a specified table constrained by a
     * specified where clause.
     *
     * @param string $table
     *            to run the query on.
     * @param string|mixed[] $where
     *            clause to constrain the query to. If a string is passed, it
     *            should be a complete conditional expression (i.e. `EMAIL =
     *            'user@example.com'). If multiple conditional expressions are
     *            to be used, an associative array should be passed (with the
     *            key-value pairs representing a column name and value,
     *            respectively) as well as a comparison operator(s), and logic
     *            operator(s) (see next two parameters) that specifies how to
     *            construct and join each conditional statment.
     * @param string|mixed[]|null $more
     * @return number|false number of rows that were deleted, or
     *         <code>false</code> if the query fails. */
    public function delete(string $table, $where, $more = null) {

        // Construct query.
        $query = self::BuildQueryDelete($table, $where, $more);
        $row_count = false;

        // Execute query.
        $con = self::connect();
        if (self::query($query, $con))
            $row_count = $con -> affected_rows;
        $con -> close();

        // Return result.
        return $row_count;
    }

    /** Creates a new table.
     *
     * @param string $table
     * @param [] $columns
     * @param string|null $ref
     *            name table to use as a template.
     * @param string|mixed|null $more
     * @return bool */
    public function create_table(string $table, $columns, $ref = null, $more = null) {

        // Construct query.
        $query = self::BuildQueryCreateTable($table, $columns, $ref, $more);

        // Execute query and return the result.
        if (self::query($query)) {
            if ($ref) {
                foreach (self::show_index($ref) as $index) {
                    $index = new SQLTableIndex($index);
                    if ($index -> key_name == 'PRIMARY')
                        $index -> key_name = $index -> column_name;
                    self::add_index($table, $index);
                }
                return true;
            }
            return true;
        }
        return false;
    }

    /**
     *
     * @param string $table
     * @param string|mixed[]|null $more
     * @return array */
    public function show_columns(string $table, $more = null) {
        // Construct query.
        $query = self::BuildQueryShowColumns($table, $more);
        $cols = [];
        if ($link = self::query($query))
            while ($col = $link -> fetch_assoc())
                array_push($cols, $col);
        return $cols;
    }

    /**
     *
     * @param string $table
     * @param string $column
     * @param string|mixed[]|null $more
     * @return bool */
    public function has_column(string $table, string $column, $more = null) {
        // Construct query
        $query = self::BuildQueryShowColumns($table,
            sprintf('LIKE %s %s', self::Value($column), $more));
        if ($link = self::query($query))
            return $link -> num_rows > 0;
        return false;
    }

    /**
     *
     * @param string $table
     * @param string|mixed[]|null $more
     * @return array */
    public function show_index(string $table, $more = null) {
        // Construct query.
        $query = self::BuildQueryShowIndex($table, $more);
        $objs = [];
        // Execute query.
        if ($link = self::query($query))
            while ($row = $link -> fetch_assoc())
                array_push($objs, $row);
        // Return result.
        return $objs;
    }

    /**
     *
     * @param string $table
     * @param SQLTableIndex $index
     * @param string|mixed[]|null $more
     * @return \mysqli_result|bool */
    public function add_index(string $table, SQLTableIndex $index, $more = null) {
        // Construct query.
        $query = self::BuildQueryAddIndex($table, $index, $more);
        // Execute query and return the result.
        return self::query($query);
    }

    /**
     *
     * @param string $table
     * @param string $index
     * @return \mysqli_result|bool */
    public function drop_index(string $table, string $index, $more = null) {
        // Construct query.
        $query = self::BuildQueryDropIndex($table, $index, $more);
        // Execute query and return the result.
        return self::query($query);
    }

    // Instance Methods

    /** Drops a specified table.
     *
     * @param string $table
     * @param string $operation
     * @param string $column
     * @param string|mixed $datatype
     * @param string|mixed[]|null $more
     * @return bool */
    public function alter_table(string $table, string $operation, string $column,
        $datatype, $more = null) {
        // Construct query.
        $query = self::BuildQueryAlterTable($table, $operation, $column,
            $datatype, $more);
        // Execute query and return the result.
        return self::query($query);
    }

    /** Drops a specified table.
     *
     * @param string $table
     * @param string|mixed[]|null $more
     * @return bool */
    public function drop_table(string $table, $more = null) {
        // Construct query.
        $query = self::BuildQueryDropTable($table, $more);
        // Execute query and return the result.
        return self::query($query);
    }

    /** Constructs and opens a new mysqli session and returns the resource
     * pointer.
     *
     * @return \mysqli resource pointer to a new open mysqli session. */
    private function connect() {
        return new \mysqli($this -> hostname, $this -> username,
            $this -> password, $this -> database);
    }

}

?>