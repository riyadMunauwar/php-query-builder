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

    // Insert record
    public function insert(array $data) {
        $columns = implode(', ', array_keys($data));
        $values = implode(', ', array_map(function($item) {
            return ":$item";
        }, array_keys($data)));

        $this->query = "INSERT INTO {$this->table} ($columns) VALUES ($values)";
        $this->params = array_combine(
            array_map(function($key) { return ":$key"; }, array_keys($data)),
            array_values($data)
        );

        return $this->execute();
    }

    // Update record(s)
    public function update(array $data) {
        $sets = implode(', ', array_map(function($item) {
            return "$item = :update_$item";
        }, array_keys($data)));

        $this->query = "UPDATE {$this->table} SET $sets";
        $this->params = array_combine(
            array_map(function($key) { return ":update_$key"; }, array_keys($data)),
            array_values($data)
        );

        $this->buildWhere();
        return $this->execute();
    }

    // Delete record(s)
    public function delete() {
        $this->query = "DELETE FROM {$this->table}";
        $this->buildWhere();
        return $this->execute();
    }

    // Build and execute SELECT query
    public function get() {
        $this->query = "SELECT " . implode(', ', $this->columns) . " FROM {$this->table}";
        
        // Add joins
        foreach ($this->joins as $join) {
            $this->query .= " {$join['type']} JOIN {$join['table']} ON {$join['first']} {$join['operator']} {$join['second']}";
        }

        $this->buildWhere();
        
        // Add GROUP BY
        if (!empty($this->groupBy)) {
            $this->query .= " GROUP BY " . implode(', ', $this->groupBy);
        }

        // Add HAVING
        if (!empty($this->having)) {
            $havingClauses = [];
            foreach ($this->having as $having) {
                $havingClauses[] = "{$having[0]} {$having[1]} :having_{$having[0]}";
            }
            $this->query .= " HAVING " . implode(' AND ', $havingClauses);
        }

        // Add ORDER BY
        if (!empty($this->orderBy)) {
            $orderClauses = array_map(function($order) {
                return "{$order[0]} {$order[1]}";
            }, $this->orderBy);
            $this->query .= " ORDER BY " . implode(', ', $orderClauses);
        }

        // Add LIMIT and OFFSET
        if ($this->limit !== null) {
            $this->query .= " LIMIT {$this->limit}";
            if ($this->offset !== null) {
                $this->query .= " OFFSET {$this->offset}";
            }
        }

        return $this->execute();
    }

    private function buildWhere() {
        if (!empty($this->where)) {
            $whereClauses = [];
            foreach ($this->where as $where) {
                if ($where[0] === 'OR') {
                    $whereClauses[] = "OR {$where[1]} {$where[2]} :orwhere_{$where[1]}";
                } else if (strpos($where[2], '(') === 0) {
                    // Handle WHERE IN case
                    $whereClauses[] = "{$where[0]} {$where[1]} {$where[2]}";
                } else {
                    $whereClauses[] = "{$where[0]} {$where[1]} :where_{$where[0]}";
                }
            }
            $this->query .= " WHERE " . ltrim(implode(' ', $whereClauses), 'OR ');
        }
    }

    private function execute() {
        try {
            $stmt = $this->pdo->prepare($this->query);
            $stmt->execute($this->params);

            // For SELECT queries
            if (stripos($this->query, 'SELECT') === 0) {
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            // For INSERT queries, return last insert ID
            if (stripos($this->query, 'INSERT') === 0) {
                return $this->pdo->lastInsertId();
            }
            
            // For UPDATE/DELETE queries, return affected rows
            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new Exception("Query execution failed: " . $e->getMessage());
        }
    }

    // Get the raw SQL query (for debugging)
    public function toSql() {
        return [
            'query' => $this->query,
            'params' => $this->params
        ];
    }
}