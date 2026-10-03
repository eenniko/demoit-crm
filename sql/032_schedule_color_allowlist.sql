UPDATE employee_schedule_templates
SET color_hex = CASE UPPER(color_hex)
    WHEN '#2563EB' THEN '#0000FF'
    WHEN '#0F766E' THEN '#008000'
    WHEN '#7C3AED' THEN '#4B0082'
    WHEN '#D97706' THEN '#FFA500'
    WHEN '#475569' THEN '#000000'
    WHEN '#64748B' THEN '#000000'
    ELSE '#0000FF'
END
WHERE UPPER(color_hex) NOT IN (
    '#FF0000', '#FFA500', '#FFFF00', '#008000', '#0000FF',
    '#4B0082', '#EE82EE', '#000000', '#FFFFFF'
);

INSERT IGNORE INTO system_version_logs (version, description, released_at)
VALUES ('1.45', 'Restrict schedule template colors to the named rainbow, black and white choices.', NOW());
