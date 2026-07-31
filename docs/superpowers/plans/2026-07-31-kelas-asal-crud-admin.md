# KelasAsal Admin CRUD Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement admin-restricted `store`, `update`, and `destroy` endpoints for Class X management in `KelasAsalController`.

**Architecture:** Controller methods perform role check for `$user->role === 'admin'`. Routes are grouped under `auth:web` middleware. CSRF exceptions updated for `/kelas-asal`.

**Tech Stack:** PHP 8.3, Laravel 12, PHPUnit / Pest.

## Global Constraints
- Only users with `$user->role === 'admin'` can execute store, update, destroy.
- Unauthenticated returns 401. Non-admin returns 403.
- Class operations forced to `tingkat = 'X'`.

---

### Task 1: Add CSRF Exception and Admin CRUD Methods to KelasAsalController & Routes

**Files:**
- Modify: `bootstrap/app.php`
- Modify: `app/Http/Controllers/KelasAsalController.php`
- Modify: `routes/web.php`
- Modify: `tests/Feature/KelasAsalControllerTest.php`

- [ ] **Step 1: Write failing feature tests for Admin CRUD**

Update `tests/Feature/KelasAsalControllerTest.php` with tests:
1. `test_admin_can_create_kelas_x`
2. `test_non_admin_cannot_create_kelas_x`
3. `test_admin_can_update_kelas_x`
4. `test_admin_can_delete_kelas_x`

- [ ] **Step 2: Run test to verify failure**

Run: `docker exec school_class_system_app php artisan test --filter=KelasAsalControllerTest`
Expected: FAIL (404 / 405 Method Not Allowed)

- [ ] **Step 3: Update bootstrap/app.php, KelasAsalController.php, and routes/web.php**

1. In `bootstrap/app.php`: Add `'kelas-asal', 'kelas-asal/*'` to CSRF exceptions.
2. In `app/Http/Controllers/KelasAsalController.php`: Add `store`, `update`, `destroy` with admin role checks and validation.
3. In `routes/web.php`: Add `POST`, `PUT`, `DELETE` routes under `auth:web` middleware.

- [ ] **Step 4: Run test to verify pass**

Run: `docker exec school_class_system_app php artisan test --filter=KelasAsalControllerTest`
Expected: PASS

- [ ] **Step 5: Run full test suite**

Run: `docker exec school_class_system_app php artisan test`
Expected: PASS
