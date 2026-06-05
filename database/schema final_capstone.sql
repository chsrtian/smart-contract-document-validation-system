-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 07, 2026 at 12:21 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `final_capstone`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_notifications`
--

CREATE TABLE `admin_notifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `type` varchar(255) NOT NULL,
  `severity` varchar(255) NOT NULL DEFAULT 'info',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `related_id` bigint(20) UNSIGNED DEFAULT NULL,
  `related_type` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_name` varchar(255) DEFAULT NULL,
  `action_type` varchar(255) NOT NULL,
  `target_entity_type` varchar(255) DEFAULT NULL,
  `target_entity_id` bigint(20) UNSIGNED DEFAULT NULL,
  `previous_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `severity` enum('info','warning','critical') NOT NULL DEFAULT 'info',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `backups`
--

CREATE TABLE `backups` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `type` enum('manual','scheduled') NOT NULL DEFAULT 'manual',
  `description` varchar(255) DEFAULT NULL,
  `status` enum('pending','in_progress','completed','failed') NOT NULL DEFAULT 'pending',
  `initiated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `started_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `checksum` varchar(64) DEFAULT NULL,
  `includes_database` tinyint(1) NOT NULL DEFAULT 1,
  `includes_documents` tinyint(1) NOT NULL DEFAULT 1,
  `includes_audit_logs` tinyint(1) NOT NULL DEFAULT 1,
  `failure_reason` text DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `correction_records`
--

CREATE TABLE `correction_records` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `scan_id` bigint(20) UNSIGNED NOT NULL,
  `reference_tx_hash` varchar(66) NOT NULL,
  `corrected_field` varchar(255) NOT NULL,
  `previous_value` text NOT NULL,
  `new_value` text NOT NULL,
  `correction_reason` text NOT NULL,
  `corrected_by_staff` bigint(20) UNSIGNED NOT NULL,
  `approved_by_supervisor` bigint(20) UNSIGNED NOT NULL,
  `correction_tx_hash` varchar(66) DEFAULT NULL,
  `correction_document_hash` varchar(66) DEFAULT NULL,
  `blockchain_status` enum('pending','confirmed','failed') NOT NULL DEFAULT 'pending',
  `blockchain_submitted_at` timestamp NULL DEFAULT NULL,
  `blockchain_confirmed_at` timestamp NULL DEFAULT NULL,
  `blockchain_metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`blockchain_metadata`)),
  `correction_request_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `correction_requests`
--

CREATE TABLE `correction_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `scan_id` bigint(20) UNSIGNED NOT NULL,
  `original_tx_hash` varchar(66) NOT NULL,
  `field_name` varchar(255) NOT NULL,
  `current_value` text NOT NULL,
  `proposed_value` text NOT NULL,
  `reason` text NOT NULL,
  `requested_by` bigint(20) UNSIGNED NOT NULL,
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `reviewed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `override_by` bigint(20) UNSIGNED DEFAULT NULL,
  `override_at` timestamp NULL DEFAULT NULL,
  `override_justification` text DEFAULT NULL,
  `override_type` enum('force_approve','force_reject') DEFAULT NULL,
  `escalated_at` timestamp NULL DEFAULT NULL,
  `escalated_reason` text DEFAULT NULL,
  `assigned_to` bigint(20) UNSIGNED DEFAULT NULL,
  `escalation_status` enum('pending','assigned','resolved') DEFAULT NULL,
  `correction_record_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `legal_correction_petitions`
--

CREATE TABLE `legal_correction_petitions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `petition_number` varchar(255) NOT NULL,
  `scan_id` bigint(20) UNSIGNED NOT NULL,
  `petition_type` varchar(255) NOT NULL,
  `legal_basis` varchar(255) NOT NULL,
  `petitioner_name` varchar(255) NOT NULL,
  `petitioner_address` text DEFAULT NULL,
  `petitioner_relationship` varchar(255) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `supporting_affidavit` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejected_by` bigint(20) UNSIGNED DEFAULT NULL,
  `rejected_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `marginal_annotations`
--

CREATE TABLE `marginal_annotations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `petition_id` bigint(20) UNSIGNED NOT NULL,
  `scan_id` bigint(20) UNSIGNED NOT NULL,
  `annotation_text` text NOT NULL,
  `annotation_type` varchar(255) NOT NULL DEFAULT 'correction',
  `legal_reference` varchar(255) DEFAULT NULL,
  `lcro_decision_number` varchar(255) DEFAULT NULL,
  `annotation_date` date DEFAULT NULL,
  `annotated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `model_has_permissions`
--

CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `model_has_roles`
--

CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `petition_attachments`
--

CREATE TABLE `petition_attachments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `petition_id` bigint(20) UNSIGNED NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `file_type` varchar(255) NOT NULL,
  `mime_type` varchar(255) DEFAULT NULL,
  `file_size` bigint(20) UNSIGNED DEFAULT NULL,
  `description` text DEFAULT NULL,
  `uploaded_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `petition_field_changes`
--

CREATE TABLE `petition_field_changes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `petition_id` bigint(20) UNSIGNED NOT NULL,
  `field_name` varchar(255) NOT NULL,
  `field_label` varchar(255) DEFAULT NULL,
  `current_value` text DEFAULT NULL,
  `proposed_value` text NOT NULL,
  `justification` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `psa_forwarding_logs`
--

CREATE TABLE `psa_forwarding_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `petition_id` bigint(20) UNSIGNED NOT NULL,
  `forwarding_reference` varchar(255) NOT NULL,
  `forwarding_status` varchar(255) NOT NULL DEFAULT 'pending',
  `forwarded_at` timestamp NULL DEFAULT NULL,
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `psa_remarks` text DEFAULT NULL,
  `transmittal_details` text DEFAULT NULL,
  `forwarded_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `role_has_permissions`
--

CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `scans`
--

CREATE TABLE `scans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `document_id` varchar(255) NOT NULL,
  `document_type` enum('birth_certificate','death_certificate','marriage_certificate','cenomar','affidavit','court_document','contract','other') NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_size` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'File size in bytes',
  `file_mime_type` varchar(100) DEFAULT NULL COMMENT 'MIME type (image/jpeg, application/pdf, etc.)',
  `original_filename` varchar(255) DEFAULT NULL COMMENT 'Original uploaded filename',
  `file_hash` varchar(255) DEFAULT NULL,
  `document_hash` varchar(255) DEFAULT NULL,
  `blockchain_enabled` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Flag indicating if blockchain anchoring is enabled for this document',
  `blockchain_tx_hash` varchar(255) DEFAULT NULL,
  `blockchain_from_address` varchar(42) DEFAULT NULL COMMENT 'Sender address (Ganache account)',
  `blockchain_to_address` varchar(42) DEFAULT NULL COMMENT 'Recipient/contract address',
  `blockchain_gas_used` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'Actual gas consumed by transaction',
  `blockchain_gas_price` varchar(30) DEFAULT NULL COMMENT 'Gas price in Wei (smallest ETH unit)',
  `blockchain_gas_limit` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'Maximum gas allowed for transaction',
  `blockchain_nonce` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'Transaction sequence number for sender',
  `blockchain_status_code` varchar(10) DEFAULT NULL COMMENT 'Transaction receipt status (0x0=failed, 0x1=success)',
  `blockchain_network_id` varchar(20) DEFAULT NULL COMMENT 'Network ID (1=mainnet, 5777=Ganache default)',
  `blockchain_confirmations` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Number of block confirmations',
  `blockchain_block_hash` varchar(66) DEFAULT NULL COMMENT 'Hash of block containing transaction',
  `blockchain_transaction_index` int(10) UNSIGNED DEFAULT NULL COMMENT 'Position of transaction within block',
  `blockchain_input_data` text DEFAULT NULL COMMENT 'Transaction input data (encoded function call)',
  `blockchain_value` varchar(30) NOT NULL DEFAULT '0' COMMENT 'ETH value sent with transaction (in Wei)',
  `blockchain_failure_reason` text DEFAULT NULL,
  `locked` tinyint(1) NOT NULL DEFAULT 0,
  `locked_by` bigint(20) UNSIGNED DEFAULT NULL,
  `locked_at` timestamp NULL DEFAULT NULL,
  `lock_reason` text DEFAULT NULL,
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  `archived_by` bigint(20) UNSIGNED DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `archive_reason` text DEFAULT NULL,
  `flagged` tinyint(1) NOT NULL DEFAULT 0,
  `flagged_by` bigint(20) UNSIGNED DEFAULT NULL,
  `flagged_at` timestamp NULL DEFAULT NULL,
  `flag_notes` text DEFAULT NULL,
  `blockchain_block_number` varchar(255) DEFAULT NULL,
  `blockchain_status` enum('pending','confirmed','failed') DEFAULT NULL,
  `blockchain_hash` varchar(255) DEFAULT NULL,
  `blockchain_submitted_at` timestamp NULL DEFAULT NULL,
  `blockchain_confirmed_at` timestamp NULL DEFAULT NULL,
  `blockchain_metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`blockchain_metadata`)),
  `ocr_confidence` int(11) NOT NULL DEFAULT 0,
  `manual_completion_score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `validation_score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `blockchain_eligible` tinyint(1) NOT NULL DEFAULT 0,
  `verification_status` enum('draft','pending','completed','rejected') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `ocr_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ocr_data`)),
  `extracted_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`extracted_fields`)),
  `notes` text DEFAULT NULL,
  `processed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `verified_by` bigint(20) UNSIGNED DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `reviewed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_notifications_type_index` (`type`),
  ADD KEY `admin_notifications_severity_index` (`severity`),
  ADD KEY `admin_notifications_is_read_index` (`is_read`),
  ADD KEY `admin_notifications_related_type_related_id_index` (`related_type`,`related_id`),
  ADD KEY `admin_notifications_created_at_index` (`created_at`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `audit_logs_user_id_index` (`user_id`),
  ADD KEY `audit_logs_target_entity_id_index` (`target_entity_id`),
  ADD KEY `audit_logs_timestamp_index` (`timestamp`),
  ADD KEY `audit_logs_action_type_index` (`action_type`),
  ADD KEY `audit_logs_severity_index` (`severity`);

--
-- Indexes for table `backups`
--
ALTER TABLE `backups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `backups_status_index` (`status`),
  ADD KEY `backups_type_index` (`type`),
  ADD KEY `backups_created_at_index` (`created_at`),
  ADD KEY `backups_initiated_by_foreign` (`initiated_by`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `correction_records`
--
ALTER TABLE `correction_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `correction_records_corrected_by_staff_foreign` (`corrected_by_staff`),
  ADD KEY `correction_records_approved_by_supervisor_foreign` (`approved_by_supervisor`),
  ADD KEY `correction_records_correction_request_id_foreign` (`correction_request_id`),
  ADD KEY `correction_records_scan_id_corrected_field_index` (`scan_id`,`corrected_field`),
  ADD KEY `correction_records_reference_tx_hash_index` (`reference_tx_hash`),
  ADD KEY `correction_records_correction_tx_hash_index` (`correction_tx_hash`),
  ADD KEY `correction_records_scan_id_created_at_index` (`scan_id`,`created_at`);

--
-- Indexes for table `correction_requests`
--
ALTER TABLE `correction_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `correction_requests_reviewed_by_foreign` (`reviewed_by`),
  ADD KEY `correction_requests_scan_id_status_index` (`scan_id`,`status`),
  ADD KEY `correction_requests_status_requested_at_index` (`status`,`requested_at`),
  ADD KEY `correction_requests_original_tx_hash_index` (`original_tx_hash`),
  ADD KEY `correction_requests_requested_by_index` (`requested_by`),
  ADD KEY `correction_requests_correction_record_id_foreign` (`correction_record_id`),
  ADD KEY `correction_requests_override_by_index` (`override_by`),
  ADD KEY `correction_requests_override_at_index` (`override_at`),
  ADD KEY `correction_requests_escalated_at_index` (`escalated_at`),
  ADD KEY `correction_requests_assigned_to_index` (`assigned_to`),
  ADD KEY `correction_requests_escalation_status_index` (`escalation_status`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `legal_correction_petitions`
--
ALTER TABLE `legal_correction_petitions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `legal_correction_petitions_petition_number_unique` (`petition_number`),
  ADD KEY `legal_correction_petitions_scan_id_foreign` (`scan_id`),
  ADD KEY `legal_correction_petitions_approved_by_foreign` (`approved_by`),
  ADD KEY `legal_correction_petitions_rejected_by_foreign` (`rejected_by`),
  ADD KEY `legal_correction_petitions_petition_number_index` (`petition_number`),
  ADD KEY `legal_correction_petitions_status_index` (`status`),
  ADD KEY `legal_correction_petitions_petition_type_index` (`petition_type`),
  ADD KEY `legal_correction_petitions_created_by_index` (`created_by`);

--
-- Indexes for table `marginal_annotations`
--
ALTER TABLE `marginal_annotations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `marginal_annotations_annotated_by_foreign` (`annotated_by`),
  ADD KEY `marginal_annotations_petition_id_index` (`petition_id`),
  ADD KEY `marginal_annotations_scan_id_index` (`scan_id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  ADD KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  ADD KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `petition_attachments`
--
ALTER TABLE `petition_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `petition_attachments_uploaded_by_foreign` (`uploaded_by`),
  ADD KEY `petition_attachments_petition_id_index` (`petition_id`);

--
-- Indexes for table `petition_field_changes`
--
ALTER TABLE `petition_field_changes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `petition_field_changes_petition_id_index` (`petition_id`);

--
-- Indexes for table `psa_forwarding_logs`
--
ALTER TABLE `psa_forwarding_logs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `psa_forwarding_logs_forwarding_reference_unique` (`forwarding_reference`),
  ADD KEY `psa_forwarding_logs_forwarded_by_foreign` (`forwarded_by`),
  ADD KEY `psa_forwarding_logs_petition_id_index` (`petition_id`),
  ADD KEY `psa_forwarding_logs_forwarding_reference_index` (`forwarding_reference`),
  ADD KEY `psa_forwarding_logs_forwarding_status_index` (`forwarding_status`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`role_id`),
  ADD KEY `role_has_permissions_role_id_foreign` (`role_id`);

--
-- Indexes for table `scans`
--
ALTER TABLE `scans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `scans_document_id_unique` (`document_id`),
  ADD KEY `scans_verified_by_foreign` (`verified_by`),
  ADD KEY `idx_doc_type_status` (`document_type`,`verification_status`),
  ADD KEY `idx_processed_by_date` (`processed_by`,`created_at`),
  ADD KEY `idx_status_date` (`verification_status`,`created_at`),
  ADD KEY `idx_blockchain_status` (`blockchain_status`,`blockchain_submitted_at`),
  ADD KEY `scans_user_id_created_at_index` (`user_id`,`created_at`),
  ADD KEY `scans_created_by_created_at_index` (`created_by`,`created_at`),
  ADD KEY `scans_reviewed_by_foreign` (`reviewed_by`),
  ADD KEY `idx_model_a_eligibility` (`validation_score`,`blockchain_eligible`),
  ADD KEY `idx_scans_file_path` (`file_path`),
  ADD KEY `idx_scans_blockchain_tx_hash` (`blockchain_tx_hash`),
  ADD KEY `idx_scans_blockchain_status` (`blockchain_status`),
  ADD KEY `scans_verification_status_blockchain_status_index` (`verification_status`,`blockchain_status`),
  ADD KEY `scans_locked_index` (`locked`),
  ADD KEY `scans_archived_index` (`archived`),
  ADD KEY `scans_flagged_index` (`flagged`),
  ADD KEY `scans_locked_by_index` (`locked_by`),
  ADD KEY `scans_archived_by_index` (`archived_by`),
  ADD KEY `scans_flagged_by_index` (`flagged_by`),
  ADD KEY `scans_blockchain_failure_reason_index` (`blockchain_failure_reason`(768));

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD KEY `users_status_index` (`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `backups`
--
ALTER TABLE `backups`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `correction_records`
--
ALTER TABLE `correction_records`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `correction_requests`
--
ALTER TABLE `correction_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `legal_correction_petitions`
--
ALTER TABLE `legal_correction_petitions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `marginal_annotations`
--
ALTER TABLE `marginal_annotations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `petition_attachments`
--
ALTER TABLE `petition_attachments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `petition_field_changes`
--
ALTER TABLE `petition_field_changes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `psa_forwarding_logs`
--
ALTER TABLE `psa_forwarding_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `scans`
--
ALTER TABLE `scans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `backups`
--
ALTER TABLE `backups`
  ADD CONSTRAINT `backups_initiated_by_foreign` FOREIGN KEY (`initiated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `correction_records`
--
ALTER TABLE `correction_records`
  ADD CONSTRAINT `correction_records_approved_by_supervisor_foreign` FOREIGN KEY (`approved_by_supervisor`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `correction_records_corrected_by_staff_foreign` FOREIGN KEY (`corrected_by_staff`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `correction_records_correction_request_id_foreign` FOREIGN KEY (`correction_request_id`) REFERENCES `correction_requests` (`id`),
  ADD CONSTRAINT `correction_records_scan_id_foreign` FOREIGN KEY (`scan_id`) REFERENCES `scans` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `correction_requests`
--
ALTER TABLE `correction_requests`
  ADD CONSTRAINT `correction_requests_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `correction_requests_correction_record_id_foreign` FOREIGN KEY (`correction_record_id`) REFERENCES `correction_records` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `correction_requests_override_by_foreign` FOREIGN KEY (`override_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `correction_requests_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `correction_requests_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `correction_requests_scan_id_foreign` FOREIGN KEY (`scan_id`) REFERENCES `scans` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `legal_correction_petitions`
--
ALTER TABLE `legal_correction_petitions`
  ADD CONSTRAINT `legal_correction_petitions_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `legal_correction_petitions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `legal_correction_petitions_rejected_by_foreign` FOREIGN KEY (`rejected_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `legal_correction_petitions_scan_id_foreign` FOREIGN KEY (`scan_id`) REFERENCES `scans` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `marginal_annotations`
--
ALTER TABLE `marginal_annotations`
  ADD CONSTRAINT `marginal_annotations_annotated_by_foreign` FOREIGN KEY (`annotated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `marginal_annotations_petition_id_foreign` FOREIGN KEY (`petition_id`) REFERENCES `legal_correction_petitions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `marginal_annotations_scan_id_foreign` FOREIGN KEY (`scan_id`) REFERENCES `scans` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `petition_attachments`
--
ALTER TABLE `petition_attachments`
  ADD CONSTRAINT `petition_attachments_petition_id_foreign` FOREIGN KEY (`petition_id`) REFERENCES `legal_correction_petitions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `petition_attachments_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `petition_field_changes`
--
ALTER TABLE `petition_field_changes`
  ADD CONSTRAINT `petition_field_changes_petition_id_foreign` FOREIGN KEY (`petition_id`) REFERENCES `legal_correction_petitions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `psa_forwarding_logs`
--
ALTER TABLE `psa_forwarding_logs`
  ADD CONSTRAINT `psa_forwarding_logs_forwarded_by_foreign` FOREIGN KEY (`forwarded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `psa_forwarding_logs_petition_id_foreign` FOREIGN KEY (`petition_id`) REFERENCES `legal_correction_petitions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `scans`
--
ALTER TABLE `scans`
  ADD CONSTRAINT `scans_archived_by_foreign` FOREIGN KEY (`archived_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `scans_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `scans_flagged_by_foreign` FOREIGN KEY (`flagged_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `scans_locked_by_foreign` FOREIGN KEY (`locked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `scans_processed_by_foreign` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `scans_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `scans_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `scans_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
