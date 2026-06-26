![PHP](https://img.shields.io/badge/PHP-8.2-blue)
![Laravel](https://img.shields.io/badge/Laravel-11-red)
![MySQL](https://img.shields.io/badge/MySQL-8.0-orange)
![Redis](https://img.shields.io/badge/Redis-7-red)
![Nginx](https://img.shields.io/badge/Nginx-green)
![k6](https://img.shields.io/badge/k6-Load_Testing-purple)

# High-Performance E-Commerce Laravel Backend

A production-grade e-commerce backend built with **Laravel 11**, **MySQL**, **Redis**, **Nginx**, and **k6**, designed to demonstrate how modern backend systems maintain reliability, consistency, and high performance under concurrent workloads.

Unlike a traditional CRUD-based application, this project focuses on solving real-world backend engineering challenges through production-oriented design and optimization techniques. It implements pessimistic database locking, distributed Redis locks, asynchronous job processing, distributed caching, batch processing, ACID-compliant transactions, request performance monitoring using Aspect-Oriented Programming (AOP), and load balancing across multiple application instances.

The application is deployed as three Laravel instances behind an Nginx load balancer, while Redis serves as the distributed cache, queue broker, session store, and locking provider. MySQL ensures transactional consistency through row-level locking and ACID transactions. The entire system is validated using automated load and stress testing with **k6**, allowing direct comparison between the baseline implementation and the optimized architecture.

---

# Project Overview

This project implements a scalable e-commerce backend capable of supporting concurrent users while preserving data integrity and maintaining low response times under heavy workloads.

Beyond implementing business functionality such as authentication, product management, shopping carts, orders, and administrative operations, the project demonstrates how production systems address complex backend challenges including race conditions, distributed synchronization, asynchronous processing, caching strategies, workload distribution, and performance monitoring.

The backend exposes a RESTful API secured with JWT authentication and is deployed as three independent Laravel instances behind an Nginx reverse proxy. Redis provides distributed caching, queue management, session storage, and distributed locking, while MySQL guarantees transactional consistency using pessimistic row-level locking. Every optimization implemented in the system is validated through automated k6 performance tests, structured application logs, and benchmark comparisons before and after optimization.

---

# Technology Stack

| Layer                | Technology            | Purpose                                     |
| -------------------- | --------------------- | ------------------------------------------- |
| Backend Framework    | Laravel 11            | REST API and application logic              |
| Programming Language | PHP 8.2               | Backend implementation                      |
| Database             | MySQL                 | Persistent storage and ACID transactions    |
| Cache                | Redis                 | Distributed caching                         |
| Queue Broker         | Redis                 | Background job processing                   |
| Authentication       | JWT                   | Stateless authentication                    |
| Reverse Proxy        | Nginx                 | Load balancing across application instances |
| Load Testing         | k6                    | Stress and performance testing              |
| Monitoring           | Custom AOP Middleware | Request performance analysis                |

---

# System Architecture

The system follows a distributed architecture consisting of three Laravel application instances behind an Nginx reverse proxy configured with the **Least Connections** load balancing algorithm. Redis acts as the distributed infrastructure layer, providing caching, queue management, session storage, and distributed locking, while MySQL ensures transactional consistency through row-level locking.

Each Laravel instance runs with ten PHP workers, allowing the system to process up to thirty concurrent PHP workers simultaneously. Queue workers execute asynchronous tasks independently from user requests, improving overall responsiveness and reducing request latency.

```
                   Clients
             (Postman / k6 Users)
                      │
                      ▼
      Nginx (Least Connection Load Balancer)
                      │
      ┌───────────────┼───────────────┐
      ▼               ▼               ▼
 Laravel #1      Laravel #2      Laravel #3
    :8001           :8002           :8003
      │               │               │
      └───────────────┼───────────────┘
                      │
           ┌──────────┴──────────┐
           ▼                     ▼
        MySQL                 Redis
   ACID Transactions      Cache • Queue
   Row-Level Locks     Distributed Locks
                        Session Storage
                             │
                             ▼
                      Queue Workers
```

---

# Core Features

* JWT-based authentication and authorization.
* Product catalog with Redis-backed distributed caching.
* Full-text product search using MySQL FULLTEXT indexes.
* Shopping cart management with per-user caching.
* ACID-compliant checkout process.
* Pessimistic database locking to prevent race conditions.
* Distributed Redis locks to prevent duplicate checkout requests.
* Asynchronous job processing using Laravel queues.
* Batch processing for large-scale sales report generation.
* Nginx load balancing across multiple Laravel instances.
* Aspect-Oriented Programming (AOP) middleware for request monitoring.
* Structured logging for benchmarking and performance analysis.
* Comprehensive load and stress testing using k6.

---

# Performance Requirements

The project demonstrates ten major non-functional requirements related to backend performance, scalability, concurrency, and reliability.

### NFR#1 — Race Condition Prevention

Order placement executes inside database transactions using pessimistic row-level locking to prevent overselling and maintain stock consistency during concurrent purchases.

### NFR#2 — Resource Management

System resources are protected through request rate limiting, cart size restrictions, quantity validation, and global order throughput limits.

### NFR#3 — Asynchronous Processing

Background tasks such as login notifications, invoice generation, order confirmation, and image processing execute asynchronously using Redis queues, significantly reducing request latency.

### NFR#4 — Batch Processing

Daily sales reports process large datasets using chunk-based execution, ensuring stable memory consumption regardless of dataset size.

### NFR#5 — Load Distribution

Incoming traffic is distributed across three Laravel instances using the Least Connections algorithm, improving throughput and balancing request workloads.

### NFR#6 — Distributed Caching

Redis caches products, search results, stores, shopping carts, authentication data, and frequently accessed resources while preventing cache stampedes through distributed locking.

### NFR#7 — Distributed Locking

The application combines MySQL pessimistic locking with Redis distributed locks to prevent race conditions, duplicate checkouts, and cache synchronization issues.

### NFR#8 — Transaction Integrity

The complete checkout workflow executes inside a single ACID-compliant database transaction. Background jobs are dispatched only after a successful commit using `DB::afterCommit()`.

### NFR#9 — Stress Testing

The optimized system successfully supports one hundred concurrent users executing realistic browsing, shopping, searching, and checkout scenarios with zero request failures.

### NFR#10 — Performance Monitoring

A custom AOP middleware records request duration, memory usage, executed database queries, response status, and request metadata, enabling bottleneck identification and quantitative performance comparison.

---

# NFR Implementation Reference

| NFR | Requirement            | Implementation                                                    |
| --- | ---------------------- | ----------------------------------------------------------------- |
| #1  | Race Condition         | `OrderService::placeOrderOptimized()` — DB lockForUpdate          |
| #2  | Resource Management    | RateLimiter, cart limits, input validation, pagination            |
| #3  | Async Processing       | SendOrderConfirmationJob, GenerateInvoiceJob, ProcessUserImage     |
| #4  | Batch Processing       | `php artisan reports:generate` — chunk-based processing           |
| #5  | Load Distribution      | Nginx Least Connections across 3 Laravel instances                |
| #6  | Distributed Caching    | Redis Cache — products, cart, orders, search, stores              |
| #7  | Distributed Locking    | `Cache::lock()` Redis mutex + MySQL `lockForUpdate()`             |
| #8  | Transaction Integrity  | `DB::transaction()` + `DB::afterCommit()` on checkout             |
| #9  | Stress Testing         | k6 — 100 concurrent users, zero failures                          |
| #10 | Performance Monitoring | AOP Middleware — duration, memory, query count per request         |

---

# Prerequisites

Before running the project, ensure the following are installed:

* PHP 8.2+
* Composer
* MySQL 8.0+
* Redis 7+
* Nginx
* k6 (for performance testing)
* Node.js (optional, for k6 script management)

---

# Installation

### 1. Clone the repository

```bash
git clone https://github.com/your-username/your-repo-name.git
cd your-repo-name
```

### 2. Install dependencies

```bash
composer install
```

### 3. Configure environment

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configure your .env file

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ecommerce
DB_USERNAME=root
DB_PASSWORD=

REDIS_HOST=127.0.0.1
REDIS_PORT=6379

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

STRICT_NFR_MODE=true
```

### 5. Run migrations and seeders

```bash
php artisan migrate --seed
```

### 6. Generate JWT secret

```bash
php artisan jwt:secret
```

---

# API Endpoints

| Method | Endpoint                  | Description                 | Auth     |
| ------ | ------------------------- | --------------------------- | -------- |
| POST   | /api/auth/register        | Register a new user         | Public   |
| POST   | /api/auth/login           | Login and receive JWT token | Public   |
| POST   | /api/auth/logout          | Logout and invalidate token | Required |
| GET    | /api/products             | List all products (cached)  | Required |
| GET    | /api/products/{id}        | Get product details (cached)| Required |
| GET    | /api/stores               | List all stores (cached)    | Required |
| GET    | /api/stores/{id}          | Get store details (cached)  | Required |
| GET    | /api/stores/{id}/products | Get store products (cached) | Required |
| GET    | /api/stores/filter        | Filter stores by criteria   | Required |
| GET    | /api/search               | Full-text search (cached)   | Required |
| GET    | /api/cart/view            | View cart (cached)          | Required |
| POST   | /api/cart/add             | Add item to cart (locked)   | Required |
| POST   | /api/cart/update-quantity/{id} | Update cart item quantity   | Required |
| DELETE | /api/cart/remove/{id}     | Remove item from cart       | Required |
| POST   | /api/orders/place         | Place order (ACID + locked) | Required |
| GET    | /api/orders               | Get user orders (cached)    | Required |

---

# Running Performance Tests

All performance experiments can be executed in two configurations:

* **Baseline (Before Optimization)** – Runs the application using the standard configuration without Redis-based optimizations.
* **Optimized (After Optimization)** – Enables caching, distributed locking, asynchronous queues, and other performance optimizations.

### Baseline Configuration

```env
STRICT_NFR_MODE=false
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
CACHE_STORE=file
```

Run any test using:

```bash
k6 run -e STRICT_NFR_MODE=false <test-script>
```

### Optimized Configuration

```env
STRICT_NFR_MODE=true
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
CACHE_STORE=redis
```

Before running the optimized tests, ensure that:

* Redis is running.
* Queue workers are started.
* All three Laravel instances are running.
* Nginx is configured as the reverse proxy.

Run any test using:

```bash
k6 run -e STRICT_NFR_MODE=true <test-script>
```

### Starting the Full Environment

Start the three Laravel application instances:

```bash
# Terminal 1
set PHP_CLI_SERVER_WORKERS=10
php artisan serve --port=8001

# Terminal 2
set PHP_CLI_SERVER_WORKERS=10
php artisan serve --port=8002

# Terminal 3
set PHP_CLI_SERVER_WORKERS=10
php artisan serve --port=8003
```

Start Nginx:

```bash
start nginx
```

Start the queue workers (optimized mode only):

```bash
php artisan queue:work --queue=default,reports
```

### Available Test Scripts

| Test               | Script                                  | Purpose                                                                                                          |
| ------------------ | --------------------------------------- | ---------------------------------------------------------------------------------------------------------------- |
| Race Condition     | `Tests/Order/Order_Race_Condition.js`   | Verifies stock consistency under concurrent checkout requests.                                                   |
| Duplicate Checkout | `Tests/Order/Duplicate_Checkout.js`     | Validates Redis distributed locking.                                                                             |
| Login Performance  | `Tests/Auth/Login.js`                   | Measures the impact of asynchronous job processing.                                                              |
| Search Performance | `Tests/Search/Search.js`                | Benchmarks Redis caching and MySQL full-text search.                                                             |
| Stress Test        | `Tests/Order/Stress.js`                 | Evaluates system stability under heavy traffic.                                                                  |
| Combined Workload  | `Tests/Combined/Combined_100_Users.js`  | Simulates a realistic workload with 100 concurrent users performing browsing, shopping, and checkout operations. |

---

# Logging and Test Results

To support performance analysis and validate the implementation of the non-functional requirements, every test execution automatically generates both structured application logs and k6 performance reports.

### Business Logs

Business logs record the functional behavior of the system during execution, including events such as authentication requests, product access, cart operations, stock updates, and order processing. These logs help verify the correctness of the application under concurrent workloads.

```text
storage/logs/
├── order/
├── auth/
├── search/
└── combined/
```

### AOP Performance Logs

The application uses an AOP-based performance monitoring middleware that captures performance metrics for every HTTP request.

Each log entry includes:

- HTTP method
- Request URL
- Response status code
- Execution time (`duration_ms`)
- Memory usage (`memory_kb`)
- Number of database queries (`query_count`)
- Timestamp

Example:

```json
{
  "method": "POST",
  "url": "/api/orders/place",
  "duration_ms": 110.43,
  "memory_kb": 3715.36,
  "query_count": 10,
  "status_code": 201,
  "timestamp": "2026-06-19 18:20:23"
}
```

### k6 Performance Reports

Each k6 test script automatically exports a JSON summary using the `handleSummary()` function. Separate reports are generated for both the **Before Optimization** and **After Optimization** executions.

```text
nginx/Results/
├── Order/
├── Auth/
├── Search/
└── Combined/
```

The generated reports include:

- Response time statistics
- Throughput
- Failure rate
- Number of requests
- Number of virtual users
- Test duration
- Custom performance metrics

Together, the structured logs and k6 reports provide complete evidence for analyzing system behavior, comparing performance before and after optimization, and validating the implementation of all required non-functional requirements.
