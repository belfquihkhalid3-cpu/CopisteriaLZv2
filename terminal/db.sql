-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : sam. 13 sep. 2025 à 21:20
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `copisteria_db`
--

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `active_orders`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `active_orders` (
`id` int(11)
,`user_id` int(11)
,`order_number` varchar(50)
,`status` enum('DRAFT','PENDING','CONFIRMED','PROCESSING','PRINTING','READY','COMPLETED','CANCELLED')
,`payment_method` enum('BANK_TRANSFER','CREDIT_CARD','PAYPAL','STORE_PAYMENT')
,`payment_status` enum('PENDING','PAID','FAILED','REFUNDED')
,`stripe_payment_intent_id` varchar(255)
,`stripe_session_id` varchar(255)
,`total_price` decimal(10,2)
,`total_pages` int(11)
,`total_files` int(11)
,`pickup_code` varchar(10)
,`estimated_completion` datetime
,`completed_at` datetime
,`print_config` longtext
,`customer_notes` text
,`admin_notes` text
,`created_at` timestamp
,`updated_at` timestamp
,`first_name` varchar(100)
,`last_name` varchar(100)
,`email` varchar(255)
,`phone` varchar(20)
);

-- --------------------------------------------------------

--
-- Structure de la table `files`
--

CREATE TABLE `files` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `original_name` varchar(255) NOT NULL,
  `stored_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `file_hash` varchar(64) DEFAULT NULL,
  `page_count` int(11) DEFAULT 1,
  `thumbnail_path` varchar(500) DEFAULT NULL,
  `status` enum('UPLOADING','READY','PROCESSING','ERROR') DEFAULT 'READY',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `finishing_costs`
--

CREATE TABLE `finishing_costs` (
  `id` int(11) NOT NULL,
  `service_type` enum('BINDING','LAMINATING','PERFORATION') NOT NULL,
  `service_name` varchar(100) NOT NULL,
  `cost` decimal(8,2) NOT NULL,
  `cost_type` enum('FIXED','PER_PAGE','PER_DOCUMENT') DEFAULT 'FIXED',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `notification_type` enum('ORDER_CREATED','ORDER_STATUS_CHANGED','ORDER_READY','PAYMENT_RECEIVED','GENERAL') DEFAULT 'GENERAL',
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `status` enum('DRAFT','PENDING','CONFIRMED','PROCESSING','PRINTING','READY','COMPLETED','CANCELLED') DEFAULT 'DRAFT',
  `payment_method` enum('BANK_TRANSFER','CREDIT_CARD','PAYPAL','STORE_PAYMENT') DEFAULT 'BANK_TRANSFER',
  `payment_status` enum('PENDING','PAID','FAILED','REFUNDED') DEFAULT 'PENDING',
  `stripe_payment_intent_id` varchar(255) DEFAULT NULL,
  `stripe_session_id` varchar(255) DEFAULT NULL,
  `total_price` decimal(10,2) DEFAULT 0.00,
  `total_pages` int(11) DEFAULT 0,
  `total_files` int(11) DEFAULT 0,
  `pickup_code` varchar(10) DEFAULT NULL,
  `estimated_completion` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `print_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`print_config`)),
  `customer_notes` text DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `source_type` enum('ONLINE','TERMINAL') DEFAULT 'ONLINE',
  `terminal_id` varchar(10) DEFAULT NULL,
  `terminal_ip` varchar(45) DEFAULT NULL,
  `is_guest` tinyint(1) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déclencheurs `orders`
--
DELIMITER $$
CREATE TRIGGER `generate_order_number` BEFORE INSERT ON `orders` FOR EACH ROW BEGIN
    IF NEW.order_number IS NULL OR NEW.order_number = '' THEN
        SET NEW.order_number = CONCAT('COP-', YEAR(NOW()), '-', LPAD(LAST_INSERT_ID() + 1, 6, '0'));
    END IF;
    
    IF NEW.pickup_code IS NULL THEN
        SET NEW.pickup_code = CONCAT(
            CHAR(65 + FLOOR(RAND() * 26)),
            CHAR(65 + FLOOR(RAND() * 26)),
            CHAR(65 + FLOOR(RAND() * 26)),
            LPAD(FLOOR(RAND() * 1000), 3, '0')
        );
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_original_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `page_count` int(11) DEFAULT 1,
  `paper_size` enum('A3','A4','A5','LETTER') DEFAULT 'A4',
  `paper_weight` enum('80g','90g','160g','200g','250g','280g') DEFAULT '80g',
  `color_mode` enum('BW','COLOR') DEFAULT 'BW',
  `orientation` enum('PORTRAIT','LANDSCAPE') DEFAULT 'PORTRAIT',
  `sides` enum('SINGLE','DOUBLE') DEFAULT 'SINGLE',
  `binding` enum('NONE','STAPLE','SPIRAL','THERMAL') DEFAULT 'NONE',
  `binding_color` varchar(50) DEFAULT NULL,
  `copies` int(11) DEFAULT 1,
  `unit_price` decimal(8,4) NOT NULL,
  `binding_cost` decimal(8,2) DEFAULT 0.00,
  `item_total` decimal(10,2) NOT NULL,
  `processing_status` enum('PENDING','PROCESSING','PRINTED','FINISHED') DEFAULT 'PENDING',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déclencheurs `order_items`
--
DELIMITER $$
CREATE TRIGGER `update_order_totals` AFTER INSERT ON `order_items` FOR EACH ROW BEGIN
    UPDATE orders 
    SET 
        total_price = (
            SELECT COALESCE(SUM(item_total), 0) 
            FROM order_items 
            WHERE order_id = NEW.order_id
        ),
        total_pages = (
            SELECT COALESCE(SUM(page_count * copies), 0) 
            FROM order_items 
            WHERE order_id = NEW.order_id
        ),
        total_files = (
            SELECT COUNT(*) 
            FROM order_items 
            WHERE order_id = NEW.order_id
        )
    WHERE id = NEW.order_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `pricing`
--

CREATE TABLE `pricing` (
  `id` int(11) NOT NULL,
  `paper_size` enum('A3','A4','A5','LETTER') NOT NULL,
  `paper_weight` enum('80g','90g','160g','200g','250g','280g') NOT NULL,
  `color_mode` enum('BW','COLOR') NOT NULL,
  `price_per_page` decimal(8,4) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `valid_from` date DEFAULT curdate(),
  `valid_until` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `terminal_activity`
--

CREATE TABLE `terminal_activity` (
  `id` int(11) NOT NULL,
  `terminal_id` varchar(10) NOT NULL,
  `terminal_ip` varchar(45) NOT NULL,
  `activity_type` enum('ORDER_CREATED','LOGIN','LOGOUT','ERROR') NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `is_admin` tinyint(1) DEFAULT 0,
  `email_verified` tinyint(1) DEFAULT 0,
  `login_attempts` int(11) DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `verification_token` varchar(255) DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expires` datetime DEFAULT NULL,
  `stripe_customer_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `user_stats`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `user_stats` (
`id` int(11)
,`first_name` varchar(100)
,`last_name` varchar(100)
,`email` varchar(255)
,`total_orders` bigint(21)
,`total_spent` decimal(32,2)
,`total_pages_printed` decimal(32,0)
,`last_order_date` timestamp
);

-- --------------------------------------------------------

--
-- Structure de la vue `active_orders`
--
DROP TABLE IF EXISTS `active_orders`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `active_orders`  AS SELECT `o`.`id` AS `id`, `o`.`user_id` AS `user_id`, `o`.`order_number` AS `order_number`, `o`.`status` AS `status`, `o`.`payment_method` AS `payment_method`, `o`.`payment_status` AS `payment_status`, `o`.`stripe_payment_intent_id` AS `stripe_payment_intent_id`, `o`.`stripe_session_id` AS `stripe_session_id`, `o`.`total_price` AS `total_price`, `o`.`total_pages` AS `total_pages`, `o`.`total_files` AS `total_files`, `o`.`pickup_code` AS `pickup_code`, `o`.`estimated_completion` AS `estimated_completion`, `o`.`completed_at` AS `completed_at`, `o`.`print_config` AS `print_config`, `o`.`customer_notes` AS `customer_notes`, `o`.`admin_notes` AS `admin_notes`, `o`.`created_at` AS `created_at`, `o`.`updated_at` AS `updated_at`, `u`.`first_name` AS `first_name`, `u`.`last_name` AS `last_name`, `u`.`email` AS `email`, `u`.`phone` AS `phone` FROM (`orders` `o` join `users` `u` on(`o`.`user_id` = `u`.`id`)) WHERE `o`.`status` in ('PENDING','CONFIRMED','PROCESSING','PRINTING') ORDER BY `o`.`created_at` ASC ;

-- --------------------------------------------------------

--
-- Structure de la vue `user_stats`
--
DROP TABLE IF EXISTS `user_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `user_stats`  AS SELECT `u`.`id` AS `id`, `u`.`first_name` AS `first_name`, `u`.`last_name` AS `last_name`, `u`.`email` AS `email`, count(`o`.`id`) AS `total_orders`, coalesce(sum(`o`.`total_price`),0) AS `total_spent`, coalesce(sum(`o`.`total_pages`),0) AS `total_pages_printed`, max(`o`.`created_at`) AS `last_order_date` FROM (`users` `u` left join `orders` `o` on(`u`.`id` = `o`.`user_id` and `o`.`status` <> 'CANCELLED')) GROUP BY `u`.`id` ;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `files`
--
ALTER TABLE `files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `idx_user_files` (`user_id`),
  ADD KEY `idx_file_hash` (`file_hash`),
  ADD KEY `idx_files_user_status` (`user_id`,`status`);

--
-- Index pour la table `finishing_costs`
--
ALTER TABLE `finishing_costs`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `idx_user_notifications` (`user_id`,`is_read`);

--
-- Index pour la table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `idx_user_orders` (`user_id`),
  ADD KEY `idx_order_status` (`status`),
  ADD KEY `idx_payment_status` (`payment_status`),
  ADD KEY `idx_orders_status_date` (`status`,`created_at`),
  ADD KEY `idx_source_type` (`source_type`),
  ADD KEY `idx_terminal_id` (`terminal_id`),
  ADD KEY `idx_is_guest` (`is_guest`);

--
-- Index pour la table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_items` (`order_id`);

--
-- Index pour la table `pricing`
--
ALTER TABLE `pricing`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_pricing` (`paper_size`,`paper_weight`,`color_mode`,`valid_from`),
  ADD KEY `idx_pricing_lookup` (`paper_size`,`paper_weight`,`color_mode`,`is_active`);

--
-- Index pour la table `terminal_activity`
--
ALTER TABLE `terminal_activity`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_terminal_id` (`terminal_id`),
  ADD KEY `idx_activity_type` (`activity_type`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `fk_terminal_order` (`order_id`),
  ADD KEY `fk_terminal_user` (`user_id`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `files`
--
ALTER TABLE `files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `finishing_costs`
--
ALTER TABLE `finishing_costs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `pricing`
--
ALTER TABLE `pricing`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `terminal_activity`
--
ALTER TABLE `terminal_activity`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `files`
--
ALTER TABLE `files`
  ADD CONSTRAINT `files_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `files_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `terminal_activity`
--
ALTER TABLE `terminal_activity`
  ADD CONSTRAINT `fk_terminal_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_terminal_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
