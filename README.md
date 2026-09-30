### 🧠 Core Engineering Highlight: Atomic Transactions
To guarantee data integrity during complex inventory transfers across departments, I implemented strict SQL transactions using PHP Data Objects (PDO). This ensures that if any part of the transfer process fails (e.g., deducting stock but failing to log the movement), the entire operation is rolled back, preventing orphaned data or stock discrepancies.
# Case-Study-2-Multi-Module-Medical-Inventory-ERP
Client/Context: Clínica La Floresta (Caracas) Role: Full-Stack Developer &amp; Database Architect Tech Stack: PHP, MySQL, Vanilla JavaScript, Relational Database Design.

1. The Problem
The clinic required a robust, scalable system to manage a complex web of medical inventory across multiple departments. The operation lacked a unified architecture, leading to data silos, slow query times, and difficulties in tracking the lifecycle of critical medical supplies. They needed a secure, multi-module web application to centralize their entire inventory operation.

2. The Solution
I architected and developed a custom, modular web application from the ground up, focusing on backend stability, data integrity, and fast execution.

Database Architecture: Designed a highly normalized MySQL database schema to handle multiple interconnected modules (master inventory, departmental stock, user roles, and transaction logs), ensuring absolute data integrity and zero redundancy.

Backend Development (PHP): Built a secure PHP backend utilizing PDO to handle complex CRUD operations, session management, and relational data joining. I focused heavily on refactoring queries to optimize server response times.

Frontend Integration: Developed a clean, functional interface utilizing JavaScript and asynchronous requests (Fetch/AJAX) to interact with the PHP backend, providing a seamless, fast experience for the clinical staff without unnecessary page reloads.

3. The Impact
Centralized multiple departmental inventories into a single, reliable source of truth.

Optimized database queries, drastically reducing load times when generating large inventory and consumption reports.

Delivered a scalable, modular foundation that allows the clinic to easily add new features as their operational needs grow.
