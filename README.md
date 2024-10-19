# PHP Fluent Query Builder (Beta)

A lightweight, secure, and flexible SQL query builder for PHP applications. This Query Builder provides an expressive and fluent interface to build and execute database queries without writing raw SQL.

## 🌟 Features

- Fluent interface for building SQL queries
- Support for all major SQL operations (SELECT, INSERT, UPDATE, DELETE)
- Secure parameter binding to prevent SQL injection
- Multiple join types (INNER, LEFT, RIGHT)
- Complex WHERE conditions with multiple operators
- ORDER BY, GROUP BY, and HAVING clauses
- Pagination support with LIMIT and OFFSET
- PDO integration for database agnostic operations
- Debug methods for query inspection
- Comprehensive error handling

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
composer require riyad/php-query-builder (Cooming soon...)
```

## 🔧 Basic Setup

```php
// Create a PDO connection
$pdo = new PDO("mysql:host=localhost;dbname=your_database", "username", "password");

// Initialize the Query Builder
$qb = new QueryBuilder($pdo);
```

## 📖 Usage Examples

### Select Query
```php
// Simple select
$users = $qb->table('users')
    ->select(['id', 'name', 'email'])
    ->where('status', 'active')
    ->get();

// Complex select with joins
$orders = $qb->table('orders')
    ->select(['orders.id', 'users.name', 'orders.total'])
    ->join('users', 'orders.user_id', '=', 'users.id')
    ->where('orders.status', 'pending')
    ->orderBy('orders.created_at', 'DESC')
    ->limit(10)
    ->get();
```

### Insert Query
```php
$userId = $qb->table('users')
    ->insert([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'created_at' => date('Y-m-d H:i:s')
    ]);
```

### Update Query
```php
$affected = $qb->table('users')
    ->where('id', 1)
    ->update([
        'name' => 'Jane Doe',
        'updated_at' => date('Y-m-d H:i:s')
    ]);
```

### Delete Query
```php
$deleted = $qb->table('users')
    ->where('status', 'inactive')
    ->delete();
```

### Advanced Where Conditions
```php
$results = $qb->table('products')
    ->where('price', '>', 100)
    ->where('category', 'electronics')
    ->orWhere('featured', true)
    ->whereIn('id', [1, 2, 3, 4])
    ->get();
```

### Aggregates and Grouping
```php
$sales = $qb->table('orders')
    ->select(['customer_id', 'SUM(total) as total_sales'])
    ->groupBy('customer_id')
    ->having('total_sales', '>', 1000)
    ->get();
```

## 🔍 Debugging

You can inspect the generated SQL query and parameters:

```php
$debug = $qb->table('users')
    ->where('status', 'active')
    ->toSql();

print_r($debug['query']);  // Shows the SQL query
print_r($debug['params']); // Shows the bound parameters
```

## ⚠️ Known Limitations (Beta)

1. No support for sub-queries yet
2. Limited support for UNION operations
3. No support for table aliases in complex joins
4. Basic transaction support only
5. Limited database-specific feature support

## 🛣️ Roadmap

- [ ] Add support for sub-queries
- [ ] Implement UNION operations
- [ ] Add support for table aliases
- [ ] Enhanced transaction support
- [ ] Query caching mechanism
- [ ] More database-specific features
- [ ] Query logging and profiling
- [ ] Unit test coverage
- [ ] Documentation website

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request. For major changes, please open an issue first to discuss what you would like to change.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 🔒 Security

If you discover any security-related issues, please email security@example.com instead of using the issue tracker.

## 📝 License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details.

## ✨ Credits

Developed and maintained by [Riyad Munauwar/www.riyadmunauwar.com]

## 📧 Support

For support questions, please use the issue tracker or email hello@riyadmunauwar.com.

---

**Note:** This is a beta version. API might change without prior notice. Not recommended for production use yet.