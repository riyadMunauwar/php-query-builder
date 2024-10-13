<?php

namespace QueryBuilder;

use PDO;
use Exception;

class QueryBuilder
{
    protected $pdo;
    protected $table;
    protected $columns = ['*'];
    protected $wheres = [];
    protected $orderBy = [];
    protected $limit;
    protected $offset;
    protected $joins = [];
    protected $groupBy = [];
    protected $havings = [];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function table($table)
    {
        $this->table = $table;
        return $this;
    }

    public function select($columns = ['*'])
    {
        $this->columns = is_array($columns) ? $columns : func_get_args();
        return $this;
    }

    public function where($column, $operator = null, $value = null)
    {
        if (is_array($column)) {
            foreach ($column as $key => $value) {
                $this->where($key, '=', $value);
            }
            return $this;
        }

        if (func_num_args() == 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = compact('column', 'operator', 'value');
        return $this;
    }

    public function orWhere($column, $operator = null, $value = null)
    {
        if (func_num_args() == 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = ['type' => 'OR', 'column' => $column, 'operator' => $operator, 'value' => $value];
        return $this;
    }

    public function whereIn($column, array $values)
    {
        $this->wheres[] = compact('column', 'values', 'type');
        $this->addBinding($values);
        return $this;
    }

    public function orderBy($column, $direction = 'ASC')
    {
        $this->orderBy[] = compact('column', 'direction');
        return $this;
    }

    public function limit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset($offset)
    {
        $this->offset = $offset;
        return $this;
    }

    public function join($table, $first, $operator = null, $second = null, $type = 'INNER')
    {
        if (func_num_args() == 3) {
            $second = $operator;
            $operator = '=';
        }

        $this->joins[] = compact('table', 'first', 'operator', 'second', 'type');
        return $this;
    }

    public function leftJoin($table, $first, $operator = null, $second = null)
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    public function groupBy(...$columns)
    {
        $this->groupBy = array_merge($this->groupBy, $columns);
        return $this;
    }

    public function having($column, $operator = null, $value = null)
    {
        if (func_num_args() == 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->havings[] = compact('column', 'operator', 'value');
        return $this;
    }

    public function get()
    {
        $sql = $this->toSql();
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->getBindings());
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function first()
    {
        $results = $this->limit(1)->get();
        return $results ? $results[0] : null;
    }

    public function insert(array $values)
    {
        $sql = "INSERT INTO {$this->table} (" . implode(', ', array_keys($values)) . ") VALUES (" . implode(', ', array_fill(0, count($values), '?')) . ")";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_values($values));
        return $this->pdo->lastInsertId();
    }

    public function update(array $values)
    {
        $set = [];
        foreach ($values as $column => $value) {
            $set[] = "{$column} = ?";
        }
        $sql = "UPDATE {$this->table} SET " . implode(', ', $set);
        $sql .= $this->compileWheres();
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_merge(array_values($values), $this->getBindings()));
        return $stmt->rowCount();
    }

    public function delete()
    {
        $sql = "DELETE FROM {$this->table}";
        $sql .= $this->compileWheres();
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->getBindings());
        return $stmt->rowCount();
    }

    protected function toSql()
    {
        $sql = "SELECT " . implode(', ', $this->columns) . " FROM {$this->table}";
        
        foreach ($this->joins as $join) {
            $sql .= " {$join['type']} JOIN {$join['table']} ON {$join['first']} {$join['operator']} {$join['second']}";
        }

        $sql .= $this->compileWheres();

        if (!empty($this->groupBy)) {
            $sql .= " GROUP BY " . implode(', ', $this->groupBy);
        }

        if (!empty($this->havings)) {
            $sql .= " HAVING " . $this->compileHavings();
        }

        if (!empty($this->orderBy)) {
            $sql .= " ORDER BY " . implode(', ', array_map(function($order) {
                return "{$order['column']} {$order['direction']}";
            }, $this->orderBy));
        }

        if ($this->limit) {
            $sql .= " LIMIT {$this->limit}";
        }

        if ($this->offset) {
            $sql .= " OFFSET {$this->offset}";
        }

        return $sql;
    }

    protected function compileWheres()
    {
        if (empty($this->wheres)) {
            return '';
        }

        $wheres = [];
        foreach ($this->wheres as $where) {
            if (isset($where['type']) && $where['type'] === 'OR') {
                $wheres[] = "OR {$where['column']} {$where['operator']} ?";
            } else {
                $wheres[] = "{$where['column']} {$where['operator']} ?";
            }
        }

        return " WHERE " . implode(' AND ', $wheres);
    }

    protected function compileHavings()
    {
        return implode(' AND ', array_map(function($having) {
            return "{$having['column']} {$having['operator']} ?";
        }, $this->havings));
    }

    protected function getBindings()
    {
        $bindings = [];
        foreach ($this->wheres as $where) {
            if (isset($where['values'])) {
                $bindings = array_merge($bindings, $where['values']);
            } else {
                $bindings[] = $where['value'];
            }
        }
        foreach ($this->havings as $having) {
            $bindings[] = $having['value'];
        }
        return $bindings;
    }
}