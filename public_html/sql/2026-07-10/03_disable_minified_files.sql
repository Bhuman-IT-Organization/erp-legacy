-- Use non-minified JS (for main.js discount fix to work)
UPDATE tbloptions SET value = '0' WHERE name = 'use_minified_files';
