
UPDATE tbloptions SET value = '1' WHERE name = 'cron_has_run_from_cli';
UPDATE tbloptions SET value = UNIX_TIMESTAMP() WHERE name = 'last_cron_run';
