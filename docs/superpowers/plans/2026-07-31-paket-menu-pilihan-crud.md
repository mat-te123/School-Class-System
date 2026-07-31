# PaketMenuPilihan Admin CRUD Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement admin-restricted `store`, `update`, and `destroy` endpoints in `PaketMenuPilihanController`.

**Architecture:** Add `store`, `update`, `destroy` methods enforcing `$user->role === 'admin'`. Add web routes and CSRF exceptions.

**Tech Stack:** PHP 8.3, Laravel 12, PHPUnit.

## Global Constraints
- Admin role check: `$user->role === 'admin'`
- Unauthenticated returns 401, non-admin returns 403.

---

### Task 1: Add CSRF Exception and Admin CRUD Methods to PaketMenuPilihanController & Routes

**Files:**
- Modify: `bootstrap/app.php`
- Modify: `app/Http/Controllers/PaketMenuPilihanController.php`
- Modify: `routes/web.php`
- Modify: `tests/Feature/PaketMenuPilihanControllerTest.php`

- [ ] **Step 1: Write failing feature tests in PaketMenuPilihanControllerTest**

Update `tests/Feature/PaketMenuPilihanControllerTest.php` with:
- `test_admin_can_create_paket_menu_pilihan`
- `test_non_admin_cannot_create_paket_menu_pilihan`
- `test_admin_can_update_paket_menu_pilihan`
- `test_admin_can_delete_paket_menu_pilihan`

- [ ] **Step 2: Run test to verify failure**

Run: `docker exec school_class_system_app php artisan test --filter=PaketMenuPilihanControllerTest`
Expected: FAIL (405 Method Not Allowed)

- [ ] **Step 3: Implement CSRF exception, controller methods, and routes**

1. Update `bootstrap/app.php` with `'paket-menu-pilihan', 'paket-menu-pilihan/*'`.
2. Update `app/Http/Controllers/PaketMenuPilihanController.php` with `store`, `update`, `destroy`.
3. Update `routes/web.php` with `POST`, `PUT`, `DELETE` routes under `auth:web` group.

- [ ] **Step 4: Run test to verify pass**

Run: `docker exec school_class_system_app php artisan test --filter=PaketMenuPilihanControllerTest`
Expected: PASS

- [ ] **Step 5: Run full test suite**

Run: `docker exec school_class_system_app php artisan test`
Expected: PASS
