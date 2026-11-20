-- Migration 013: Create Work Shifts Table
CREATE TABLE IF NOT EXISTS work_shifts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id VARCHAR(36) NOT NULL,
    restaurant_id INT NOT NULL,
    shift_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME,
    shift_type VARCHAR(20) DEFAULT 'server',
    status VARCHAR(20) DEFAULT 'scheduled',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
    UNIQUE (user_id, shift_date, start_time)
);
CREATE INDEX IF NOT EXISTS idx_work_shifts_restaurant_id ON work_shifts(restaurant_id);
CREATE INDEX IF NOT EXISTS idx_work_shifts_shift_date ON work_shifts(shift_date);
CREATE INDEX IF NOT EXISTS idx_work_shifts_user_id ON work_shifts(user_id);
CREATE INDEX IF NOT EXISTS idx_work_shifts_status ON work_shifts(status);
