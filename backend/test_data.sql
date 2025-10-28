-- Create tables
CREATE TABLE IF NOT EXISTS "user" (
    id SERIAL PRIMARY KEY,
    email VARCHAR(180) NOT NULL UNIQUE,
    roles JSON NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS tag (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    color VARCHAR(7) NOT NULL,
    user_id INT NOT NULL REFERENCES "user"(id) ON DELETE CASCADE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(name, user_id)
);

CREATE TABLE IF NOT EXISTS task (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    priority VARCHAR(20) NOT NULL DEFAULT 'medium',
    start_date TIMESTAMP,
    due_date TIMESTAMP,
    is_archived BOOLEAN NOT NULL DEFAULT FALSE,
    sort_order INT NOT NULL DEFAULT 0,
    user_id INT NOT NULL REFERENCES "user"(id) ON DELETE CASCADE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS task_tag (
    task_id INT NOT NULL REFERENCES task(id) ON DELETE CASCADE,
    tag_id INT NOT NULL REFERENCES tag(id) ON DELETE CASCADE,
    PRIMARY KEY (task_id, tag_id)
);

CREATE TABLE IF NOT EXISTS subtask (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    is_completed BOOLEAN NOT NULL DEFAULT FALSE,
    task_id INT NOT NULL REFERENCES task(id) ON DELETE CASCADE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Insert test user (password: admin123 - bcrypt hashed)
INSERT INTO "user" (email, roles, password, created_at, updated_at) VALUES
('admin@example.com', '["ROLE_USER", "ROLE_ADMIN"]', '$2y$13$QZvXFgKmVQZXBfKlYQpUOeJ5xYYxQYYxQYYxQYYxQYYxQYYxQYY', NOW(), NOW())
ON CONFLICT (email) DO NOTHING;

-- Insert test tags
INSERT INTO tag (name, color, user_id, created_at, updated_at) VALUES
('Work', '#3B82F6', 1, NOW(), NOW()),
('Personal', '#10B981', 1, NOW(), NOW()),
('Urgent', '#EF4444', 1, NOW(), NOW()),
('Meeting', '#F59E0B', 1, NOW(), NOW()),
('Learning', '#8B5CF6', 1, NOW(), NOW())
ON CONFLICT (name, user_id) DO NOTHING;

-- Insert test tasks with different statuses and dates
INSERT INTO task (title, description, status, priority, start_date, due_date, user_id, created_at, updated_at) VALUES
-- Overdue tasks
('Complete quarterly report', 'Need to finish Q4 financial report with all metrics and charts', 'in_progress', 'high', NOW() - INTERVAL '5 days', NOW() - INTERVAL '2 days', 1, NOW() - INTERVAL '5 days', NOW()),
('Fix critical bug in production', 'Users reported authentication issues', 'pending', 'urgent', NOW() - INTERVAL '3 days', NOW() - INTERVAL '1 day', 1, NOW() - INTERVAL '3 days', NOW()),

-- Today tasks
('Team standup meeting', 'Daily sync with development team', 'pending', 'medium', NOW(), NOW() + INTERVAL '2 hours', 1, NOW(), NOW()),
('Review pull requests', 'Check and approve pending PRs from team members', 'in_progress', 'high', NOW(), NOW() + INTERVAL '8 hours', 1, NOW(), NOW()),
('Update project documentation', 'Add new API endpoints to docs', 'pending', 'low', NOW(), NOW() + INTERVAL '10 hours', 1, NOW(), NOW()),

-- Tomorrow tasks
('Prepare presentation', 'Create slides for stakeholder meeting', 'pending', 'high', NOW() + INTERVAL '1 day', NOW() + INTERVAL '1 day' + INTERVAL '4 hours', 1, NOW(), NOW()),
('Code review session', 'Review architecture decisions with senior dev', 'pending', 'medium', NOW() + INTERVAL '1 day', NOW() + INTERVAL '1 day' + INTERVAL '2 hours', 1, NOW(), NOW()),

-- Future tasks
('Plan sprint activities', 'Define tasks and goals for next sprint', 'pending', 'medium', NOW() + INTERVAL '3 days', NOW() + INTERVAL '5 days', 1, NOW(), NOW()),
('Conduct user interviews', 'Gather feedback from 5 key users', 'pending', 'high', NOW() + INTERVAL '4 days', NOW() + INTERVAL '7 days', 1, NOW(), NOW()),
('Optimize database queries', 'Improve performance of slow queries', 'pending', 'medium', NOW() + INTERVAL '5 days', NOW() + INTERVAL '10 days', 1, NOW(), NOW()),

-- Completed tasks
('Setup CI/CD pipeline', 'Configure GitHub Actions for automated deployments', 'completed', 'high', NOW() - INTERVAL '7 days', NOW() - INTERVAL '5 days', 1, NOW() - INTERVAL '7 days', NOW() - INTERVAL '5 days'),
('Write unit tests', 'Add test coverage for user service', 'completed', 'medium', NOW() - INTERVAL '4 days', NOW() - INTERVAL '3 days', 1, NOW() - INTERVAL '4 days', NOW() - INTERVAL '3 days');

-- Link tags to tasks
INSERT INTO task_tag (task_id, tag_id) VALUES
(1, 1), (1, 3),  -- Complete quarterly report: Work, Urgent
(2, 1), (2, 3),  -- Fix critical bug: Work, Urgent
(3, 1), (3, 4),  -- Team standup: Work, Meeting
(4, 1),          -- Review PRs: Work
(5, 1),          -- Update docs: Work
(6, 1), (6, 4),  -- Prepare presentation: Work, Meeting
(7, 1),          -- Code review: Work
(8, 1),          -- Plan sprint: Work
(9, 1),          -- User interviews: Work
(10, 1), (10, 5), -- Optimize DB: Work, Learning
(11, 1),         -- Setup CI/CD: Work
(12, 1);         -- Write tests: Work

-- Add subtasks to some tasks
INSERT INTO subtask (title, is_completed, task_id, created_at, updated_at) VALUES
('Gather financial data', true, 1, NOW(), NOW()),
('Create charts and graphs', true, 1, NOW(), NOW()),
('Write executive summary', false, 1, NOW(), NOW()),
('Get approval from CFO', false, 1, NOW(), NOW()),

('Reproduce the bug', true, 2, NOW(), NOW()),
('Identify root cause', false, 2, NOW(), NOW()),
('Implement fix', false, 2, NOW(), NOW()),
('Test in staging', false, 2, NOW(), NOW()),

('Create presentation outline', true, 6, NOW(), NOW()),
('Design slides', false, 6, NOW(), NOW()),
('Add data visualizations', false, 6, NOW(), NOW()),
('Practice delivery', false, 6, NOW(), NOW());

