-- Migration: Add project_village_expcodes table
-- Date: 2026-01-07

CREATE TABLE IF NOT EXISTS `project_village_expcodes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode_proyek` varchar(50) NOT NULL,
  `id_village` int(11) NOT NULL,
  `exp_code` varchar(20) NOT NULL COMMENT 'Expense code, e.g. 10101',
  `place_code` varchar(10) NOT NULL COMMENT 'Same as village_abbr, e.g. NJ, SW',
  `description` varchar(255) DEFAULT NULL COMMENT 'Description for this exp code allocation',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_project_village_exp` (`kode_proyek`, `id_village`, `exp_code`),
  KEY `fk_pve_proyek` (`kode_proyek`),
  KEY `fk_pve_village` (`id_village`),
  CONSTRAINT `fk_pve_proyek` FOREIGN KEY (`kode_proyek`) REFERENCES `proyek` (`kode_proyek`) ON DELETE CASCADE,
  CONSTRAINT `fk_pve_village` FOREIGN KEY (`id_village`) REFERENCES `villages` (`id_village`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Assignment of villages and exp codes to projects';
