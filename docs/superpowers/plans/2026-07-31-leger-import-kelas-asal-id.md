# Leger Import kelas_asal_id Integration Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Pass and utilize `kelas_asal_id` parameter during Leger XLSX import to explicitly link records to a specified `KelasAsal`.

**Architecture:** Controller captures `kelas_asal_id`, Job receives and passes `kelas_asal_id`, Service looks up `KelasAsal` by ID or falls back to Excel metadata.

**Tech Stack:** PHP 8.3, Laravel 12, PHPUnit.

---

### Task 1: Update Controller, Job, Service, and Tests

**Files:**
- Modify: `app/Http/Controllers/LegerImportController.php`
- Modify: `app/Jobs/ProcessLegerImportJob.php`
- Modify: `app/Services/LegerImportService.php`
- Modify: `tests/Feature/LegerImportControllerTest.php`

- [ ] **Step 1: Write feature test in LegerImportControllerTest**

Add `test_leger_import_with_explicit_kelas_asal_id` in `tests/Feature/LegerImportControllerTest.php`.

- [ ] **Step 2: Update LegerImportController.php**

Capture `kelas_asal_id` and pass to service/job.

- [ ] **Step 3: Update ProcessLegerImportJob.php**

Add `$kelasAsalId` to job constructor and handle method.

- [ ] **Step 4: Update LegerImportService.php**

Update `importFromXlsx` to use `$kelasAsalId` if provided.

- [ ] **Step 5: Run tests and verify**

Run: `docker exec school_class_system_app php artisan test`
Expected: PASS
