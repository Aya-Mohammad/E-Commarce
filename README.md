# High-Performance E-Commerce Backend

A production-grade e-commerce backend built with **Laravel 11**, **MySQL**, **Redis**, **Nginx**, and **k6**, designed to demonstrate how modern backend systems maintain reliability, consistency, and high performance under concurrent workloads.

Unlike a traditional CRUD-based application, this project focuses on solving real-world backend engineering challenges through production-oriented design and optimization techniques. It implements pessimistic database locking, distributed Redis locks, asynchronous job processing, distributed caching, batch processing, ACID-compliant transactions, request performance monitoring using Aspect-Oriented Programming (AOP), and load balancing across multiple application instances.

The application is deployed as three Laravel instances behind an Nginx load balancer, while Redis serves as the distributed cache, queue broker, session store, and locking provider. MySQL ensures transactional consistency through row-level locking and ACID transactions. The entire system is validated using automated load and stress testing with **k6**, allowing direct comparison between the baseline implementation and the optimized architecture.

---

# Table of Contents

* [Project Overview](#project-overview)
* [Technology Stack](#technology-stack)
* [System Architecture](#system-architecture)
* [Core Features](#core-features)
* [Performance Requirements](#performance-requirements)
* [Prerequisites](#prerequisites)
* [Environment Configuration](#environment-configuration)
* [Installation](#installation)
* [Database Seeding](#database-seeding)
* [Running the Application](#running-the-application)
* [Load Testing](#load-testing)
* [Test Scripts](#test-scripts)
* [Logs and Results](#logs-and-results)
* [API Endpoints](#api-endpoints)
* [Troubleshooting](#troubleshooting)
* [Evidence Checklist](#evidence-checklist)

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
