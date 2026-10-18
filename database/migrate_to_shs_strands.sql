-- Convert college course data (BSIT/BSCS, 1st–4th Year) to senior high strands (STRAND-GRADE+SECTION).
-- Old test data mapping: BSIT -> STEM, BSCS -> ABM; 1st Year -> Grade 11, 2nd–4th Year -> Grade 12.
-- Safe to re-run: rows already in the new format are untouched.

START TRANSACTION;

-- Students: strand + grade level
UPDATE students SET course = 'STEM' WHERE course = 'BSIT';
UPDATE students SET course = 'ABM'  WHERE course = 'BSCS';
UPDATE students SET year_level = 'Grade 11' WHERE year_level = '1st Year';
UPDATE students SET year_level = 'Grade 12' WHERE year_level IN ('2nd Year', '3rd Year', '4th Year');

-- Section codes, e.g. BSIT-1A -> STEM-11A, BSIT-2B -> STEM-12B
UPDATE staff_advising
SET class_advising = CONCAT(
        CASE SUBSTRING_INDEX(class_advising, '-', 1) WHEN 'BSIT' THEN 'STEM' ELSE 'ABM' END,
        '-', IF(SUBSTRING(class_advising, 6, 1) = '1', '11', '12'), RIGHT(class_advising, 1))
WHERE class_advising REGEXP '^(BSIT|BSCS)-[1-4][A-Z]$';

UPDATE classes
SET name = CONCAT(
        CASE SUBSTRING_INDEX(name, '-', 1) WHEN 'BSIT' THEN 'STEM' ELSE 'ABM' END,
        '-', IF(SUBSTRING(name, 6, 1) = '1', '11', '12'), RIGHT(name, 1))
WHERE name REGEXP '^(BSIT|BSCS)-[1-4][A-Z]$';

UPDATE announcements
SET class_name = CONCAT(
        CASE SUBSTRING_INDEX(class_name, '-', 1) WHEN 'BSIT' THEN 'STEM' ELSE 'ABM' END,
        '-', IF(SUBSTRING(class_name, 6, 1) = '1', '11', '12'), RIGHT(class_name, 1))
WHERE class_name REGEXP '^(BSIT|BSCS)-[1-4][A-Z]$';

-- Subjects: course -> strand, year_level "1A" -> "11A"
UPDATE subjects
SET course = CASE course WHEN 'BSIT' THEN 'STEM' ELSE 'ABM' END,
    year_level = CONCAT(IF(LEFT(year_level, 1) = '1', '11', '12'), RIGHT(year_level, 1))
WHERE course IN ('BSIT', 'BSCS') AND year_level REGEXP '^[1-4][A-Z]$';

-- Teachers: department -> strand
UPDATE staff_accounts SET department = 'STEM' WHERE department = 'Department of Information Technology';
UPDATE staff_accounts SET department = 'ABM'  WHERE department = 'Department of Computer Science';

COMMIT;
