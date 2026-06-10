CREATE TABLE migrations (
    `version` VARCHAR(255) PRIMARY KEY,
    `executed_at` TIMESTAMP NOT NULL DEFAULT NOW()
);
