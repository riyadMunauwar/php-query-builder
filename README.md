# PHP Fluent Query Builder (Beta)

A powerful, secure, and flexible SQL query builder for PHP applications. This Query Builder provides an expressive interface to build and execute database queries with advanced features and query inspection capabilities.

## 🌟 Features

- Fluent interface for building SQL queries
- Support for all major SQL operations (SELECT, INSERT, UPDATE, DELETE)
- Secure parameter binding to prevent SQL injection
- Advanced WHERE clause conditions
  - Basic comparisons (=, >, <, !=, etc.)
  - WHERE IN
  - WHERE NULL / NOT NULL
  - WHERE BETWEEN
  - OR WHERE conditions
- Multiple join types (INNER, LEFT, RIGHT)
- ORDER BY, GROUP BY, and HAVING clauses
- Pagination support with LIMIT and OFFSET
- Query inspection and debugging capabilities
- PDO integration for database agnostic operations
- Query building separation from execution
- Comprehensive error handling
- Query state reset functionality

## 📋 Requirements

- PHP 7.4 or higher
- PDO PHP Extension
- A supported database system (MySQL, PostgreSQL, SQLite, etc.)

## 🚀 Installation

Currently, this package is in beta. You can install it by cloning this repository:

```bash
git clone https://github.com/riyadMunauwar/php-query-builder.git
```

Future versions will be available via Composer:

```bash
composer require riyad/php-query-builder [Cooming soon...]
```

## 🔧 Basic Setup

```php
// Create a PDO connection
$pdo = new PDO("mysql:host=localhost;dbname=your_database", "username", "password");

// Initialize the Query Builder
$qb = new QueryBuilder($pdo);
```

## 📖 Usage Examples

### Basic Select Query
```php
// Simple select with conditions
$users = $qb->table('users')
    ->select(['id', 'name', 'email'])
    ->where('status', 'active')
    ->orderBy('name', 'ASC')
    ->get();
```

### Advanced Where Conditions
```php
// Multiple where conditions
$users = $qb->table('users')
    ->where('status', 'active')
    ->whereNull('deleted_at')
    ->whereBetween('age', [18, 65])
    ->whereIn('role', ['admin', 'manager'])
    ->orWhere('is_premium', true)
    ->get();
```

### Join Operations
```php
// Complex select with joins
$orders = $qb->table('orders')
    ->select(['orders.id', 'users.name', 'orders.total'])
    ->leftJoin('users', 'orders.user_id', '=', 'users.id')
    ->where('orders.status', 'pending')
    ->orderBy('orders.created_at', 'DESC')
    ->get();
```

### Insert Operations
```php
$userId = $qb->table('users')
    ->insert([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'created_at' => date('Y-m-d H:i:s')
    ]);
```

### Update Operations
```php
$affected = $qb->table('users')
    ->where('id', 1)
    ->update([
        'name' => 'Jane Doe',
        'updated_at' => date('Y-m-d H:i:s')
    ]);
```

### Delete Operations
```php
$deleted = $qb->table('users')
    ->where('status', 'inactive')
    ->whereNotNull('deleted_at')
    ->delete();
```

### Query Inspection
```php
// Build query without execution
$qb->table('users')
    ->where('status', 'active')
    ->buildQuery('SELECT');

// Get the built query and parameters
$queryData = $qb->getQuery();
print_r($queryData['query']);   // View the SQL query
print_r($queryData['params']);  // View the parameters

// Execute when ready
$results = $qb->execute();
```

### Query Reset
```php
// Reset query builder state for new query
$qb->reset();
```

## 🔍 Debugging

You can inspect any query before execution:

```php
$debug = $qb->table('users')
    ->where('status', 'active')
    ->buildQuery('SELECT')
    ->toSql();

print_r($debug['query']);  // Shows the SQL query
print_r($debug['params']); // Shows the bound parameters
```

## ⚠️ Known Limitations (Beta)

1. No support for sub-queries yet
2. Limited support for UNION operations
3. No support for table aliases in complex joins
4. Basic transaction support only
5. Limited support for database-specific features

## 🛣️ Roadmap

- [ ] Add support for sub-queries
- [ ] Implement UNION operations
- [ ] Add support for complex table aliases
- [ ] Enhanced transaction support
- [ ] Query caching mechanism
- [ ] More database-specific features
- [ ] Query logging and profiling
- [ ] Unit test coverage
- [ ] Documentation website
- [ ] More WHERE clause variations
- [ ] Raw query support
- [ ] Query events and hooks

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request. For major changes, please open an issue first to discuss what you would like to change.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 🔒 Security

If you discover any security-related issues, please email issue@riyadmunauwar.com instead of using the issue tracker.

## 📝 License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details.

## ✨ Credits

Developed and maintained by [Riyad Munauwar]
*Website* www.riyadmunauwar.com

## 📧 Support

For support questions, please use:
- GitHub Issues for bug reports and feature requests
- Email hello@riyadmunauwar.com for general inquiries
- Documentation at [php-query-builder.riyadmunauwar.com](https://php-query-builder.riyadmunauwar.com)

## 📚 Best Practices

1. Always use parameter binding instead of string concatenation
2. Inspect queries in development using `toSql()`
3. Reset the query builder state between queries using `reset()`
4. Use meaningful table and column names
5. Keep queries simple and maintainable
6. Use transactions for related operations

---

**Note:** This is a beta version. API might change without prior notice. Not recommended for production use yet.