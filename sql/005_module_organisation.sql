-- DemoIT CRM — "Organisation & Substitutes" module registration (v1.18)
-- Idempotent, phpMyAdmin-runnable. Tables already exist (system_org_units, system_substitutes from 001_core_schema.sql);
-- this migration only registers the module in the catalogue so clients can activate it like any other module.

SET NAMES utf8mb4;

INSERT IGNORE INTO system_modules (module_key, name, description, status, is_demo_available)
VALUES ('organisation', 'Organisation & Substitutes', 'A-F organisation hierarchy and temporary substitute management.', 'active', 1);

INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.18', 'Registered Organisation & Substitutes as an activatable module instead of always-on panel menu items.', NOW());
