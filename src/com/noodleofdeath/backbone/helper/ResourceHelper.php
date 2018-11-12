<?php

namespace com\noodleofdeath\backbone\helper;

use com\noodleofdeath\backbone\model\resource\Resource;
use com\noodleofdeath\backbone\model\resource\exception\SQLException;
use com\noodleofdeath\backbone\model\resource\exception\ResourceException;

/** Helper for managing generic resource-like records.
 *
 * @see resource */
class ResourceHelper {

    public const FetchOptDeleted = 1 << 0;

    public const FetchOptAsAssoc = 1 << 1;

    public const FetchOptAlwaysReturnArray = 1 << 2;

    public const FetchOptIgnoreAccessRules = 1 << 3;

    public const FetchOptOnlyOneResult = 1 << 4;

    public $model;

    public $table;

    public $sqlhelper;

    public $primary_key;

    public function __construct(string $model, string $table,
        SQLHelper $sqlhelper = null) {
        if (!(new \ReflectionClass($model)) -> implementsInterface(
            Resource::class)) {
            throw new ResourceException();
        }
        $this -> model = $model;
        $this -> table = $table;
        $this -> sqlhelper = $sqlhelper;
        $this -> primary_key = $model::PrimaryKey();
    }

    /** Creates a new record of this resource/entity in the database and returns
     * the corresponding resource instance.
     *
     * @param Resource $resource
     *            to create.
     * @param string[] $add_queries
     *            additional queries to include in this transaction.
     * @return object|null a resource instance corresponding to the newly
     *         created record if the transaction succeeds or <code>null</code>,
     *         if the transaction fails.
     * @throws ResourceException if an error occurs while trying to complete
     *         this transaction. */
    public function create($resource) {
        try {
            $query = SQLHelper::BuildQueryInsert($this -> table,
                $resource -> dataMap());
            $response = $this -> sqlhelper -> perform_transaction($query);
            if ($response -> success && $id = $response -> insert_ids[0])
                return self::fetch($id);
        } catch (SQLException $ex) {
            //
        }
        return null;
    }

    /** Fetches the record associated with the specified primary key or
     * SQLHelper where clause and returns a single resource instance if exactly
     * one record is fetched or a sequential array of resource instances is
     * multiple records are fetched; <code>null</code> is returned if the query
     * fails.
     *
     * @param number|mixed[] $id
     *            of the record(s) to fetch, or where clause of records to
     *            fetch.
     * @param int $opts
     * @param string|mixed[]|null $more
     * @return Resource|Resource[]|null a single resource instance if exactly
     *         one record is fetched or a sequential array of resource instances
     *         is multiple records are fetched; <code>null</code> is returned if
     *         the query fails.
     * @throws ResourceException if an error occurs while trying to complete
     *         this transaction. */
    public function fetch($id, int $opts = 0, $more = null) {
        $where = [
            is_numeric($id) ? SQLHelper::Build($this -> primary_key, $id) : SQLHelper::FormatWhere(
                $id)
        ];
        if (($opts & self::FetchOptDeleted) == 0 &&
            $this -> sqlhelper -> has_column($this -> table, Resource::kDeleted))
            array_push($where, SQLHelper::Build(Resource::kDeleted, 0));
        $where = trim(sprintf('%s %s', SQLHelper::Group($where), $more));
        if ($opts & self::FetchOptOnlyOneResult)
            $where = sprintf('%s LIMIT 1', $where);
        try {
            if ($rows = $this -> sqlhelper -> select($this -> table, '*', $where)) {
                if (($opts & self::FetchOptAlwaysReturnArray) == 0 &&
                    count($rows) == 1) {
                    return (($opts & self::FetchOptAsAssoc) != 0 ? $rows[0] : self::newInstance(
                        $rows[0]));
                }
                $resources = [];
                foreach ($rows as $row) {
                    array_push($resources,
                        ($opts & self::FetchOptAsAssoc != 0 ? $row : self::newInstance(
                            $row)));
                }
                return $resources;
            }
        } catch (SQLException $ex) {
            //
        }
        return ($opts & self::FetchOptAlwaysReturnArray) ? [] : null;
    }

    /**
     *
     * @param number|mixed[] $id
     * @param int $opts
     * @return number */
    public function count($id, int $opts = 0, $more = null) {
        $where = [
            is_numeric($id) ? SQLHelper::Build($this -> primary_key, $id) : SQLHelper::FormatWhere(
                $id)
        ];
        if (($opts & self::FetchOptDeleted) == 0 &&
            $this -> sqlhelper -> has_column($this -> table, Resource::kDeleted))
            array_push($where, SQLHelper::Build(Resource::kDeleted, 0));
        $where = sprintf('%s %s', SQLHelper::Group($where), $more);
        try {
            $count = $this -> sqlhelper -> count($this -> table, '*', $where);
        } catch (SQLException $ex) {
            //
            echo $where;
        }
        return $count;
    }

    /** Modifies the record associated with the specified primary key and
     * returns a corresponsing resource instance.
     *
     * @param number|mixed[] $id
     *            of the record(s) to update.
     * @param Resource $resource
     *            to update the new record with.
     * @param string[] $add_queries
     *            additional queries to include in this transaction.
     * @return number|null number of affected rows by this transaction.
     * @throws ResourceException if an error occurs while trying to complete
     *         this transaction. */
    public function update($id, $resource) {
        $where = is_numeric($id) ? SQLHelper::Build($this -> primary_key, $id) : SQLHelper::FormatWhere(
            $id);
        try {
            $query = SQLHelper::BuildQueryUpdate($this -> table,
                $resource -> dataMap(), $where);
            $response = $this -> sqlhelper -> perform_transaction($query);
            if ($response -> success)
                return $response -> affected_rows[0];
        } catch (SQLException $ex) {
            //
        }
        return null;
    }

    /** Soft deletes the record associated with the specified primary key and
     * returns <code>true</code> if the transaction succeeds;
     * <code>false</code>, otherwise.
     *
     * @param number|mixed[] $id
     *            of the record(s) to soft delete.
     * @param string[] $add_queries
     *            additional queries to include in this transaction.
     * @return number|false <code>true</code> if the transaction succeeds;
     *         <code>false</code>, otherwise.
     * @throws ResourceException if an error occurs while trying to complete
     *         this transaction. */
    public function delete($id) {
        return self::update($id, self::newInstance(
            [
                Resource::kDeleted => 1,
            ]));
    }

    /** Restores the record associated with the specified primary key and
     * returns <code>true</code> if the transaction succeeds;
     * <code>false</code>, otherwise.
     *
     * @param number|mixed[] $id
     *            of the record(s) to restore.
     * @param string[] $add_queries
     *            additional queries to include in this transaction.
     * @return number|false <code>true</code> if the transaction succeeds;
     *         <code>false</code>, otherwise.
     * @throws ResourceException if an error occurs while trying to complete
     *         this transaction. */
    public function restore($id) {
        return self::update($id, self::newInstance(
            [
                Resource::kDeleted => 0,
            ]));
    }

    /** Permanently deletes the record associated with the specified primary key
     * and returns <code>true</code> if the transaction succeeds;
     * <code>false</code>, otherwise.
     *
     * @param number|mixed[] $id
     *            of the record(s) to permanently delete.
     * @param string[] $add_queries
     *            additional queries to include in this transaction.
     * @return number|false <code>true</code> if the transaction succeeds;
     *         <code>false</code>, otherwise.
     * @throws ResourceException if an error occurs while trying to complete
     *         this transaction. */
    public function destroy($id) {
        $where = is_numeric($id) ? SQLHelper::Build($this -> primary_key, $id) : SQLHelper::FormatWhere(
            $id);
        try {
            $query = SQLHelper::BuildQueryDelete($this -> table, $where);
            $response = $this -> sqlhelper -> perform_transaction($query);
            if ($response -> success)
                return $response -> affected_rows[0];
        } catch (SQLException $ex) {
            //
        }
        return false;
    }

    /**
     *
     * @param mixed[] $args
     * @return object */
    public function newInstance($args) {
        return (new \ReflectionClass($this -> model)) -> newInstance($args);
    }

}

