<?php

class QueryBuilder {
    private $pdo;
    private $table;
    private $columns = ['*'];
    private $where = [];
    private $orderBy = [];
    private $groupBy = [];
    private $having = [];
    private $joins = [];
    private $limit = null;
    private $offset = null;
    private $params = [];
    private $query = '';

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function table($table) {
        $this->table = $table;
        return $this;
    }

    public function select($columns = ['*']) {
        $this->columns = is_array($columns) ? $columns : func_get_args();
        return $this;
    }

    public function where($column, $operator = null, $value = null) {
        if (is_array($column)) {
            foreach ($column as $key => $value) {
                $this->where[] = [$key, '=', $value];
                $this->params[":where_{$key}"] = $value;
            }
            return $this;
        }

        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->where[] = [$column, $operator, $value];
        $this->params[":where_{$column}"] = $value;
        return $this;
    }

    public function orWhere($column, $operator = null, $value = null) {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->where[] = ['OR', $column, $operator, $value];
        $this->params[":orwhere_{$column}"] = $value;
        return $this;
    }

    public function whereIn($column, array $values) {
        $placeholders = [];
        foreach ($values as $key => $value) {
            $param = ":wherein_{$column}_{$key}";
            $placeholders[] = $param;
            $this->params[$param] = $value;
        }
        
        $this->where[] = [$column, 'IN', "(" . implode(',', $placeholders) . ")"];
        return $this;
    }

    public function whereNull($column) {
        $this->where[] = [$column, 'IS', 'NULL'];
        return $this;
    }

    public function whereNotNull($column) {
        $this->where[] = [$column, 'IS NOT', 'NULL'];
        return $this;
    }

    public function whereBetween($column, array $values) {
        $this->params[":between1_{$column}"] = $values[0];
        $this->params[":between2_{$column}"] = $values[1];
        $this->where[] = [$column, 'BETWEEN', ":between1_{$column} AND :between2_{$column}"];
        return $this;
    }

    public function join($table, $first, $operator = null, $second = null, $type = 'INNER') {
        $this->joins[] = [
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second,
            'type' => $type
        ];
        return $this;
    }

    public function leftJoin($table, $first, $operator = null, $second = null) {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    public function rightJoin($table, $first, $operator = null, $second = null) {
        return $this->join($table, $first, $operator, $second, 'RIGHT');
    }

    public function orderBy($column, $direction = 'ASC') {
        $this->orderBy[] = [$column, strtoupper($direction)];
        return $this;
    }

    public function groupBy($columns) {
        $this->groupBy = is_array($columns) ? $columns : func_get_args();
        return $this;
    }

    public function having($column, $operator = null, $value = null) {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->having[] = [$column, $operator, $value];
        $this->params[":having_{$column}"] = $value;
        return $this;
    }

    public function limit($limit) {
        $this->limit = $limit;
        return $this;
    }

    public function offset($offset) {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Build the complete SQL query string
     * @param string $type Query type (SELECT, INSERT, UPDATE, DELETE)
     * @param array $data Data for INSERT/UPDATE queries
     * @return $this
     */
    public function buildQuery($type = 'SELECT', array $data = []) {
        $query = '';

        switch (strtoupper($type)) {
            case 'SELECT':
                $query = $this->buildSelectQuery();
                break;
            case 'INSERT':
                $query = $this->buildInsertQuery($data);
                break;
            case 'UPDATE':
                $query = $this->buildUpdateQuery($data);
                break;
            case 'DELETE':
                $query = $this->buildDeleteQuery();
                break;
            default:
                throw new Exception("Unsupported query type: {$type}");
        }

        $this->query = $query;
        return $this;
    }

    private function buildSelectQuery() {
        $query = "SELECT " . implode(', ', $this->columns) . " FROM {$this->table}";
        
        if (!empty($this->joins)) {
            foreach ($this->joins as $join) {
                $query .= " {$join['type']} JOIN {$join['table']} ON {$join['first']} {$join['operator']} {$join['second']}";
            }
        }

        if (!empty($this->where)) {
            $query .= $this->buildWhereClause();
        }

        if (!empty($this->groupBy)) {
            $query .= " GROUP BY " . implode(', ', $this->groupBy);
        }

        if (!empty($this->having)) {
            $query .= $this->buildHavingClause();
        }

        if (!empty($this->orderBy)) {
            $query .= $this->buildOrderByClause();
        }

        if ($this->limit !== null) {
            $query .= " LIMIT {$this->limit}";
            if ($this->offset !== null) {
                $query .= " OFFSET {$this->offset}";
            }
        }

        return $query;
    }

    private function buildInsertQuery(array $data) {
        $columns = implode(', ', array_keys($data));
        $values = implode(', ', array_map(function($item) {
            return ":$item";
        }, array_keys($data)));

        $this->params = array_combine(
            array_map(function($key) { return ":$key"; }, array_keys($data)),
            array_values($data)
        );

        return "INSERT INTO {$this->table} ($columns) VALUES ($values)";
    }

    private function buildUpdateQuery(array $data) {
        $sets = implode(', ', array_map(function($item) {
            return "$item = :update_$item";
        }, array_keys($data)));

        $this->params = array_combine(
            array_map(function($key) { return ":update_$key"; }, array_keys($data)),
            array_values($data)
        );

        $query = "UPDATE {$this->table} SET $sets";
        
        if (!empty($this->where)) {
            $query .= $this->buildWhereClause();
        }

        return $query;
    }

    private function buildDeleteQuery() {
        $query = "DELETE FROM {$this->table}";
        
        if (!empty($this->where)) {
            $query .= $this->buildWhereClause();
        }

        return $query;
    }

    private function buildWhereClause() {
        $whereClauses = [];
        foreach ($this->where as $where) {
            if ($where[0] === 'OR') {
                $whereClauses[] = "OR {$where[1]} {$where[2]} :orwhere_{$where[1]}";
            } else if (strpos($where[2], '(') === 0 || $where[2] === 'NULL' || strpos($where[2], ':between') === 0) {
                $whereClauses[] = "{$where[0]} {$where[1]} {$where[2]}";
            } else {
                $whereClauses[] = "{$where[0]} {$where[1]} :where_{$where[0]}";
            }
        }
        return " WHERE " . ltrim(implode(' ', $whereClauses), 'OR ');
    }

    private function buildHavingClause() {
        $havingClauses = [];
        foreach ($this->having as $having) {
            $havingClauses[] = "{$having[0]} {$having[1]} :having_{$having[0]}";
        }
        return " HAVING " . implode(' AND ', $havingClauses);
    }

    private function buildOrderByClause() {
        $orderClauses = array_map(function($order) {
            return "{$order[0]} {$order[1]}";
        }, $this->orderBy);
        return " ORDER BY " . implode(', ', $orderClauses);
    }

    /**
     * Get the built query and parameters
     * @return array
     */
    public function getQuery() {
        return [
            'query' => $this->query,
            'params' => $this->params
        ];
    }

    private function execute() {
        try {
            $stmt = $this->pdo->prepare($this->query);
            $stmt->execute($this->params);

            if (stripos($this->query, 'SELECT') === 0) {
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            if (stripos($this->query, 'INSERT') === 0) {
                return $this->pdo->lastInsertId();
            }
            
            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new Exception("Query execution failed: " . $e->getMessage());
        }
    }

    public function get() {
        $this->buildQuery('SELECT');
        return $this->execute();
    }

    public function insert(array $data) {
        $this->buildQuery('INSERT', $data);
        return $this->execute();
    }

    public function update(array $data) {
        $this->buildQuery('UPDATE', $data);
        return $this->execute();
    }

    public function delete() {
        $this->buildQuery('DELETE');
        return $this->execute();
    }

    public function toSql() {
        return $this->getQuery();
    }

    /**
     * Reset all query components
     * @return $this
     */
    public function reset() {
        $this->table = null;
        $this->columns = ['*'];
        $this->where = [];
        $this->orderBy = [];
        $this->groupBy = [];
        $this->having = [];
        $this->joins = [];
        $this->limit = null;
        $this->offset = null;
        $this->params = [];
        $this->query = '';
        return $this;
    }
}