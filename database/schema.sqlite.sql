PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    role TEXT NOT NULL CHECK (role IN ('student', 'teacher')),
    username TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS academic_years (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    is_active INTEGER NOT NULL DEFAULT 0 CHECK (is_active IN (0, 1)),
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS students (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NULL UNIQUE,
    student_number TEXT NOT NULL UNIQUE CHECK (student_number GLOB '[0-9][0-9][0-9][0-9][0-9]'),
    full_name TEXT NOT NULL,
    class TEXT NOT NULL,
    room TEXT NOT NULL,
    academic_year_id INTEGER NOT NULL,
    drops INTEGER NOT NULL DEFAULT 0 CHECK (drops >= 0),
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS drop_transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    student_id INTEGER NOT NULL,
    teacher_id INTEGER NOT NULL,
    amount INTEGER NOT NULL CHECK (amount > 0),
    type TEXT NOT NULL CHECK (type IN ('add', 'subtract')),
    reason TEXT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS activity_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    actor_user_id INTEGER NULL,
    action TEXT NOT NULL,
    entity_type TEXT NOT NULL,
    entity_id INTEGER NULL,
    metadata TEXT NOT NULL DEFAULT '{}',
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_students_class_room ON students(class, room);
CREATE INDEX IF NOT EXISTS idx_students_academic_year_id ON students(academic_year_id);
CREATE INDEX IF NOT EXISTS idx_students_drops ON students(drops);
CREATE INDEX IF NOT EXISTS idx_drop_transactions_student_id ON drop_transactions(student_id);
CREATE INDEX IF NOT EXISTS idx_drop_transactions_teacher_id ON drop_transactions(teacher_id);
CREATE INDEX IF NOT EXISTS idx_activity_logs_actor_user_id ON activity_logs(actor_user_id);
CREATE INDEX IF NOT EXISTS idx_activity_logs_entity ON activity_logs(entity_type, entity_id);
