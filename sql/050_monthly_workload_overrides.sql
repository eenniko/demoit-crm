CREATE TABLE IF NOT EXISTS employment_contract_monthly_workloads (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL,
    contract_id INT UNSIGNED NOT NULL,
    workload_month DATE NOT NULL,
    workload_id INT UNSIGNED NOT NULL,
    workload_percent DECIMAL(5,2) NOT NULL,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_employment_contract_monthly_workload (client_id, contract_id, workload_month),
    KEY idx_employment_contract_monthly_workload_month (client_id, workload_month),
    CONSTRAINT fk_employment_contract_monthly_workloads_client FOREIGN KEY (client_id) REFERENCES system_clients (id) ON DELETE CASCADE,
    CONSTRAINT fk_employment_contract_monthly_workloads_contract FOREIGN KEY (client_id, contract_id) REFERENCES employment_contracts (client_id, id) ON DELETE CASCADE,
    CONSTRAINT fk_employment_contract_monthly_workloads_workload FOREIGN KEY (client_id, workload_id) REFERENCES employment_workloads (client_id, id) ON DELETE RESTRICT,
    CONSTRAINT fk_employment_contract_monthly_workloads_created_by FOREIGN KEY (created_by) REFERENCES system_users (id) ON DELETE SET NULL,
    CONSTRAINT fk_employment_contract_monthly_workloads_updated_by FOREIGN KEY (updated_by) REFERENCES system_users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO system_translation_keys (translation_key, module_context) VALUES
    ('panel.monthly_workload', 'panel'),
    ('panel.contract_default_workload', 'panel'),
    ('panel.monthly_workload_saved', 'panel'),
    ('panel.monthly_workload_reset', 'panel'),
    ('panel.choose_active_primary_contract', 'panel'),
    ('panel.primary_contract_month_coverage', 'panel'),
    ('panel.choose_month_workload', 'panel'),
    ('panel.monthly_workload_save_error', 'panel');

INSERT IGNORE INTO system_translation_values (translation_key_id, language_id, value, status)
SELECT translation_key_row.id, languages.id, seed.value, 'active'
FROM (
    SELECT 'panel.monthly_workload' AS translation_key, 'en' AS language_code, 'Monthly workload' AS value
    UNION ALL SELECT 'panel.monthly_workload', 'et', 'Kuu koormus'
    UNION ALL SELECT 'panel.monthly_workload', 'ru', 'Нагрузка на месяц'
    UNION ALL SELECT 'panel.contract_default_workload', 'en', 'Contract default'
    UNION ALL SELECT 'panel.contract_default_workload', 'et', 'Lepingu koormus'
    UNION ALL SELECT 'panel.contract_default_workload', 'ru', 'Нагрузка по договору'
    UNION ALL SELECT 'panel.monthly_workload_saved', 'en', 'Monthly workload saved.'
    UNION ALL SELECT 'panel.monthly_workload_saved', 'et', 'Kuu koormus salvestati.'
    UNION ALL SELECT 'panel.monthly_workload_saved', 'ru', 'Нагрузка на месяц сохранена.'
    UNION ALL SELECT 'panel.monthly_workload_reset', 'en', 'Monthly workload reset to the contract default.'
    UNION ALL SELECT 'panel.monthly_workload_reset', 'et', 'Kuu koormus lähtestati lepingu koormusele.'
    UNION ALL SELECT 'panel.monthly_workload_reset', 'ru', 'Нагрузка на месяц сброшена до значения по договору.'
    UNION ALL SELECT 'panel.choose_active_primary_contract', 'en', 'Choose an active primary contract.'
    UNION ALL SELECT 'panel.choose_active_primary_contract', 'et', 'Vali aktiivne põhileping.'
    UNION ALL SELECT 'panel.choose_active_primary_contract', 'ru', 'Выберите действующий основной договор.'
    UNION ALL SELECT 'panel.primary_contract_month_coverage', 'en', 'The primary contract does not cover this month.'
    UNION ALL SELECT 'panel.primary_contract_month_coverage', 'et', 'Põhileping ei kehti sel kuul.'
    UNION ALL SELECT 'panel.primary_contract_month_coverage', 'ru', 'Основной договор не действует в этом месяце.'
    UNION ALL SELECT 'panel.choose_month_workload', 'en', 'Choose a valid month and workload.'
    UNION ALL SELECT 'panel.choose_month_workload', 'et', 'Vali kehtiv kuu ja koormus.'
    UNION ALL SELECT 'panel.choose_month_workload', 'ru', 'Выберите допустимый месяц и нагрузку.'
    UNION ALL SELECT 'panel.monthly_workload_save_error', 'en', 'Could not save the monthly workload.'
    UNION ALL SELECT 'panel.monthly_workload_save_error', 'et', 'Kuu koormust ei saanud salvestada.'
    UNION ALL SELECT 'panel.monthly_workload_save_error', 'ru', 'Не удалось сохранить нагрузку на месяц.'
) AS seed
INNER JOIN system_translation_keys translation_key_row ON translation_key_row.translation_key = seed.translation_key
INNER JOIN system_languages languages ON languages.language_code = seed.language_code AND languages.is_active = 1;

INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.63', 'Add primary-contract monthly workload overrides for schedule required-hours and Tri-OT calculations.', NOW());
