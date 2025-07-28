-- Create attendance_modifications table to track attendance changes
CREATE TABLE IF NOT EXISTS `attendance_modifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `table_name` varchar(100) NOT NULL,
  `attendance_date` date NOT NULL,
  `session` varchar(20) NOT NULL,
  `faculty_name` varchar(100) NOT NULL,
  `modification_reason` text NOT NULL,
  `modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_table_date_session` (`table_name`, `attendance_date`, `session`),
  KEY `idx_faculty` (`faculty_name`),
  KEY `idx_modified_at` (`modified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; 