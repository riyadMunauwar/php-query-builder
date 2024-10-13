# PHP Query Builder Documentation

## Table of Contents
1. [Introduction](#introduction)
2. [Installation](#installation)
3. [Basic Usage](#basic-usage)
4. [Query Methods](#query-methods)
5. [Examples](#examples)
6. [Best Practices](#best-practices)

## Introduction

This PHP Query Builder is a flexible and powerful tool for building SQL queries in a fluent, expressive manner. Inspired by Laravel's query builder, it provides an intuitive interface for database operations while maintaining the ability to work with any PHP project.

## Installation

To use this query builder in your project, simply include the `QueryBuilder.php` file in your project and use Composer's autoloading or require it manually:

```php
require_once 'path/to/QueryBuilder.php';
```

## Basic Usage

To start using the query builder, first create an instance with a PDO connection:

```php
$pdo = new PDO('mysql:host=localhost;dbname=testdb', 'username', 'password');
$queryBuilder = new QueryBuilder\QueryBuilder($pdo);
```

Then, you can start building your queries:

```php
$users = $queryBuilder
    ->table('users')
    ->where('age', '>', 18)
    ->orderBy('name')
    ->get();
```

## Query Methods

### Select Operations

- `table(string $table)`: Set the table to query.
- `select(array|string $columns)`: Specify the columns to select.
- `where(string $column, string $operator, mixed $value)`: Add a where clause.
- `orWhere(string $column, string $operator, mixed $value)`: Add an OR where clause.
- `whereIn(string $column, array $values)`: Add a where IN clause.
- `orderBy(string $column, string $direction = 'ASC')`: Add an order by clause.
- `limit(int $limit)`: Set the limit for the query.
- `offset(int $offset)`: Set the offset for the query.
- `join(string $table, string $first, string $operator, string $second, string $type = 'INNER')`: Add a join clause.
- `leftJoin(string $table, string $first, string $operator, string $second)`: Add a left join clause.
- `groupBy(string ...$columns)`: Add a group by clause.
- `having(string $column, string $operator, mixed $value)`: Add a having clause.
- `get()`: Execute the query and get the results.
- `first()`: Get the first result of the query.

### Insert Operations

- `insert(array $values)`: Insert a new record.

### Update Operations

- `update(array $values)`: Update records.

### Delete Operations

- `delete()`: Delete records.

## Examples

### Basic Select Query

```php
$users = $queryBuilder
    ->table('users')
    ->select(['id', 'name', 'email'])
    ->where('age', '>', 18)
    ->orderBy('name')
    ->limit(10)
    ->get();
```

### Join Query

```php
$orders = $queryBuilder
    ->table('orders')
    ->select(['orders.id', 'users.name', 'orders.total'])
    ->join('users', 'users.id', '=', 'orders.user_id')
    ->where('orders.status', 'completed')
    ->get();
```

### Insert Query

```php
$userId = $queryBuilder
    ->table('users')
    ->insert([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'age' => 30
    ]);
```

### Update Query

```php
$updatedRows = $queryBuilder
    ->table('users')
    ->where('id', 1)
    ->update(['status' => 'active']);
```

### Delete Query

```php
$deletedRows = $queryBuilder
    ->table('users')
    ->where('status', 'inactive')
    ->delete();
```

## Best Practices

1. **Use Prepared Statements**: The query builder uses prepared statements internally to prevent SQL injection. Always use the provided methods rather than concatenating raw SQL.

2. **Chain Methods**: Take advantage of method chaining to create readable and expressive queries.

3. **Handle Exceptions**: Wrap your database operations in try-catch blocks to handle potential PDOExceptions.

4. **Use Transactions**: For operations that involve multiple queries, use database transactions to ensure data integrity.

5. **Optimize Queries**: Use `select()` to specify only the columns you need, and use `limit()` when you don't need all results.

6. **Reuse the Query Builder**: You can reuse the query builder instance for multiple queries, just make sure to set the table each time.

Remember, while this query builder provides a convenient interface for database operations, it's important to understand the underlying SQL being generated, especially for complex queries or when optimizing for performance.