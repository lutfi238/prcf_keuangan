-- Migration: Add Project Codes Hierarchical Structure
-- Description: Creates tables to store project-specific place codes and expense codes in a hierarchical structure
-- Date: 2025-10-29

-- Create project_code_categories table
CREATE TABLE IF NOT EXISTS `project_code_categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `kode_proyek` VARCHAR(20) NOT NULL,
  `category_number` VARCHAR(10) NOT NULL COMMENT 'e.g., 1, 2, 3, 5, 11',
  `category_name` VARCHAR(255) NOT NULL COMMENT 'e.g., Forest Governance, Forest Protection',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`kode_proyek`) REFERENCES `proyek`(`kode_proyek`) ON DELETE CASCADE,
  INDEX `idx_kode_proyek` (`kode_proyek`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Project code categories (top level hierarchy)';

-- Create project_code_subcategories table
CREATE TABLE IF NOT EXISTS `project_code_subcategories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT UNSIGNED NOT NULL,
  `subcategory_number` VARCHAR(10) NOT NULL COMMENT 'e.g., 101, 102, 201, 202',
  `subcategory_name` VARCHAR(255) NOT NULL COMMENT 'e.g., Forest Management Institution, Legal Recognition',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `project_code_categories`(`id`) ON DELETE CASCADE,
  INDEX `idx_category_id` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Project code subcategories (second level hierarchy)';

-- Create project_codes table (actual place and expense codes)
CREATE TABLE IF NOT EXISTS `project_codes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `subcategory_id` INT UNSIGNED NOT NULL,
  `kode_proyek` VARCHAR(20) NOT NULL,
  `place_code` VARCHAR(50) NOT NULL COMMENT 'Full code e.g., 10101-PR-01, 20208-NJ-01',
  `exp_code` VARCHAR(20) NOT NULL COMMENT 'Expense code part e.g., 10101, 20208',
  `activity_code` VARCHAR(10) NOT NULL COMMENT 'Activity code part e.g., PR, NJ, RJ',
  `description` TEXT COMMENT 'Activity description',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`subcategory_id`) REFERENCES `project_code_subcategories`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`kode_proyek`) REFERENCES `proyek`(`kode_proyek`) ON DELETE CASCADE,
  UNIQUE KEY `unique_place_code_per_project` (`kode_proyek`, `place_code`),
  INDEX `idx_kode_proyek` (`kode_proyek`),
  INDEX `idx_place_code` (`place_code`),
  INDEX `idx_exp_code_project` (`kode_proyek`, `exp_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Project-specific place codes and expense codes';

-- Sample data seeding for one example project (update kode_proyek as needed)
-- You can run this after creating a project to populate codes

-- Category 1: Forest Governance
SET @example_project = 'PRJ001'; -- Replace with your actual project code
SET @cat1 = NULL;
INSERT INTO project_code_categories (kode_proyek, category_number, category_name) 
VALUES (@example_project, '1', 'Forest Governance');
SET @cat1 = LAST_INSERT_ID();

-- Subcategory 1.01: Forest Management Institution
SET @subcat101 = NULL;
INSERT INTO project_code_subcategories (category_id, subcategory_number, subcategory_name)
VALUES (@cat1, '101', 'Forest Management Institution');
SET @subcat101 = LAST_INSERT_ID();

-- Place codes for subcategory 1.01
INSERT INTO project_codes (subcategory_id, kode_proyek, place_code, exp_code, activity_code, description) VALUES
(@subcat101, @example_project, '10101-PR-01', '10101', 'PR', 'Sosialisasi / Presentasi / Seminar / Workshop'),
(@subcat101, @example_project, '10101-RJ-01', '10101', 'RJ', 'Rapat Lapangan'),
(@subcat101, @example_project, '10101-RJ-02', '10101', 'RJ', 'Rapat Internal'),
(@subcat101, @example_project, '10101-RJ-03', '10101', 'RJ', 'Rapat Koordinasi');

-- Subcategory 1.02: Legal Recognition and Identity
SET @subcat102 = NULL;
INSERT INTO project_code_subcategories (category_id, subcategory_number, subcategory_name)
VALUES (@cat1, '102', 'Legal Recognition and Identity');
SET @subcat102 = LAST_INSERT_ID();

INSERT INTO project_codes (subcategory_id, kode_proyek, place_code, exp_code, activity_code, description) VALUES
(@subcat102, @example_project, '10201-NJ-01', '10201', 'NJ', 'Penyusunan Dokumen Notarisasi');

-- Category 2: Forest Protection
SET @cat2 = NULL;
INSERT INTO project_code_categories (kode_proyek, category_number, category_name) 
VALUES (@example_project, '2', 'Forest Protection');
SET @cat2 = LAST_INSERT_ID();

-- Subcategory 2.02: Area Management
SET @subcat202 = NULL;
INSERT INTO project_code_subcategories (category_id, subcategory_number, subcategory_name)
VALUES (@cat2, '202', 'Area Management');
SET @subcat202 = LAST_INSERT_ID();

INSERT INTO project_codes (subcategory_id, kode_proyek, place_code, exp_code, activity_code, description) VALUES
(@subcat202, @example_project, '20208-PR-01', '20208', 'PR', 'Sosialisasi / Presentasi / Seminar / Workshop'),
(@subcat202, @example_project, '20208-NJ-01', '20208', 'NJ', 'Penyusunan Dokumen Notarisasi'),
(@subcat202, @example_project, '20208-RJ-01', '20208', 'RJ', 'Rapat Lapangan'),
(@subcat202, @example_project, '20208-NJ-02', '20208', 'NJ', 'Penyusunan Dokumen Notarisasi (Opsional)');

-- Category 3: Sustainable Economic Development
SET @cat3 = NULL;
INSERT INTO project_code_categories (kode_proyek, category_number, category_name) 
VALUES (@example_project, '3', 'Sustainable Economic Development');
SET @cat3 = LAST_INSERT_ID();

-- Subcategory 3.02: Value Chain Development
SET @subcat302 = NULL;
INSERT INTO project_code_subcategories (category_id, subcategory_number, subcategory_name)
VALUES (@cat3, '302', 'Value Chain Development');
SET @subcat302 = LAST_INSERT_ID();

INSERT INTO project_codes (subcategory_id, kode_proyek, place_code, exp_code, activity_code, description) VALUES
(@subcat302, @example_project, '30201-PR-01', '30201', 'PR', 'Sosialisasi / Presentasi / Seminar / Workshop'),
(@subcat302, @example_project, '30201-RJ-01', '30201', 'RJ', 'Rapat Lapangan');

-- Category 5: Gender and Social Inclusion
SET @cat5 = NULL;
INSERT INTO project_code_categories (kode_proyek, category_number, category_name) 
VALUES (@example_project, '5', 'Gender and Social Inclusion');
SET @cat5 = LAST_INSERT_ID();

-- Subcategory 5.01: Gender Equality and Social Inclusion
SET @subcat501 = NULL;
INSERT INTO project_code_subcategories (category_id, subcategory_number, subcategory_name)
VALUES (@cat5, '501', 'Gender Equality and Social Inclusion');
SET @subcat501 = LAST_INSERT_ID();

INSERT INTO project_codes (subcategory_id, kode_proyek, place_code, exp_code, activity_code, description) VALUES
(@subcat501, @example_project, '50101-PR-01', '50101', 'PR', 'Sosialisasi / Presentasi / Seminar / Workshop');

-- Category 11: Community Benefits
SET @cat11 = NULL;
INSERT INTO project_code_categories (kode_proyek, category_number, category_name) 
VALUES (@example_project, '11', 'Community Benefits');
SET @cat11 = LAST_INSERT_ID();

-- Subcategory 11.01: Community Benefits
SET @subcat1101 = NULL;
INSERT INTO project_code_subcategories (category_id, subcategory_number, subcategory_name)
VALUES (@cat11, '1101', 'Community Benefits');
SET @subcat1101 = LAST_INSERT_ID();

INSERT INTO project_codes (subcategory_id, kode_proyek, place_code, exp_code, activity_code, description) VALUES
(@subcat1101, @example_project, '110101-PR-01', '110101', 'PR', 'Sosialisasi / Presentasi / Seminar / Workshop'),
(@subcat1101, @example_project, '110101-RJ-01', '110101', 'RJ', 'Rapat Lapangan');

-- Note: This sample data is for demonstration purposes.
-- In production, create a PHP interface in the admin panel to manage project codes per project.
