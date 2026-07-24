# Database Schema Design Specification: School Class System

| Metadata | Details |
| :--- | :--- |
| **Topic** | Database Schema Design (ERD & Migrations) |
| **Date** | 2026-07-24 |
| **Status** | Approved |
| **Draw.io Files** | [database_schema.drawio](file:///d:/project_new/School-Class-System/database_schema.drawio) / [database_schema.xml](file:///d:/project_new/School-Class-System/database_schema.xml) |

---

## 1. Overview & Entities

The database schema supports end-to-end automated school class assignment. It consists of 9 core tables:

1. **`users`**: User accounts for teachers (`guru`) and students (`siswa`).
2. **`academic_years`**: Active academic periods (e.g. `2025/2026 Ganjil`).
3. **`subjects`**: Subject registry (e.g., Math, Physics, Chemistry).
4. **`school_classes`**: Available classes and quota limits.
5. **`class_subject_weights`**: Subject weight percentages per class.
6. **`student_grades`**: Parsed grades from raw eRaport files.
7. **`selection_schedules`**: Time window for student class preference submission.
8. **`class_selections`**: Top 3 class preferences per student.
9. **`class_assignments`**: Final automated class placement results and calculated scores.

---

## 2. Table Specifications

### `users`
- `id`: `bigIncrements` (Primary Key)
- `name`: `string(191)`
- `username`: `string(50)` (Unique, NISN/NIP)
- `password`: `string(255)`
- `role`: `enum('guru', 'siswa')`
- `remember_token`: `string(100)` (Nullable)
- `timestamps`

### `academic_years`
- `id`: `bigIncrements` (Primary Key)
- `name`: `string(50)`
- `is_active`: `boolean` (Default: `false`)
- `timestamps`

### `subjects`
- `id`: `bigIncrements` (Primary Key)
- `code`: `string(20)` (Unique)
- `name`: `string(100)`
- `timestamps`

### `school_classes`
- `id`: `bigIncrements` (Primary Key)
- `academic_year_id`: `foreignId('academic_years')`
- `name`: `string(100)`
- `quota`: `unsignedInteger`
- `description`: `text` (Nullable)
- `timestamps`

### `class_subject_weights`
- `id`: `bigIncrements` (Primary Key)
- `school_class_id`: `foreignId('school_classes')->cascadeOnDelete()`
- `subject_id`: `foreignId('subjects')->cascadeOnDelete()`
- `weight_percentage`: `decimal(5, 2)`
- `timestamps`
- Unique: `['school_class_id', 'subject_id']`

### `student_grades`
- `id`: `bigIncrements` (Primary Key)
- `student_id`: `foreignId('users')->cascadeOnDelete()`
- `academic_year_id`: `foreignId('academic_years')`
- `subject_id`: `foreignId('subjects')`
- `score`: `decimal(5, 2)`
- `timestamps`
- Unique: `['student_id', 'academic_year_id', 'subject_id']`

### `selection_schedules`
- `id`: `bigIncrements` (Primary Key)
- `academic_year_id`: `foreignId('academic_years')`
- `title`: `string(150)`
- `start_time`: `dateTime`
- `end_time`: `dateTime`
- `is_active`: `boolean` (Default: `true`)
- `timestamps`

### `class_selections`
- `id`: `bigIncrements` (Primary Key)
- `student_id`: `foreignId('users')->cascadeOnDelete()`
- `selection_schedule_id`: `foreignId('selection_schedules')->cascadeOnDelete()`
- `school_class_id`: `foreignId('school_classes')`
- `priority`: `tinyInteger` (1, 2, 3)
- `timestamps`
- Unique: `['student_id', 'selection_schedule_id', 'priority']`
- Unique: `['student_id', 'selection_schedule_id', 'school_class_id']`

### `class_assignments`
- `id`: `bigIncrements` (Primary Key)
- `student_id`: `foreignId('users')->cascadeOnDelete()`
- `selection_schedule_id`: `foreignId('selection_schedules')`
- `school_class_id`: `foreignId('school_classes')`
- `calculated_score`: `decimal(8, 2)`
- `assigned_priority`: `tinyInteger` (Nullable)
- `status`: `enum('accepted', 'pending', 'rejected')`
- `timestamps`
- Unique: `['student_id', 'selection_schedule_id']`

---

## 3. Draw.io Export Files

The XML schema diagram is available in:
- [database_schema.drawio](file:///d:/project_new/School-Class-System/database_schema.drawio)
- [database_schema.xml](file:///d:/project_new/School-Class-System/database_schema.xml)
