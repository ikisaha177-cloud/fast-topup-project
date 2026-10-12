-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 08, 2026 at 10:01 PM
-- Server version: 5.7.33
-- PHP Version: 7.4.19

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_topup_market`
--

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `game_titles`
--

CREATE TABLE `game_titles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `icon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `game_titles`
--

INSERT INTO `game_titles` (`id`, `name`, `slug`, `category`, `icon`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Mobile Legends', 'mobile-legends', 'MOBA', 'images/games/mobile-legends.svg', 1, '2026-10-08 13:33:26', '2026-10-08 14:57:00'),
(2, 'Free Fire', 'free-fire', 'Battle Royale', 'images/games/free-fire.png', 1, '2026-10-08 13:33:26', '2026-10-08 14:57:00'),
(3, 'PUBG Mobile', 'pubg-mobile', 'Battle Royale', 'images/games/pubg-mobile.png', 1, '2026-10-08 13:33:26', '2026-10-08 14:57:00'),
(4, 'Valorant', 'valorant', 'FPS', 'images/games/valorant.svg', 1, '2026-10-08 13:33:27', '2026-10-08 14:57:00');

-- --------------------------------------------------------

--
-- Table structure for table `marketplace_orders`
--

CREATE TABLE `marketplace_orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `total_amount` bigint(20) UNSIGNED NOT NULL,
  `payment_method` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `marketplace_order_items`
--

CREATE TABLE `marketplace_order_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `marketplace_order_id` bigint(20) UNSIGNED NOT NULL,
  `marketplace_product_id` bigint(20) UNSIGNED NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` bigint(20) UNSIGNED NOT NULL,
  `subtotal` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `marketplace_products`
--

CREATE TABLE `marketplace_products` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `price` bigint(20) UNSIGNED NOT NULL,
  `stock` int(11) NOT NULL DEFAULT '0',
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `marketplace_products`
--

INSERT INTO `marketplace_products` (`id`, `user_id`, `name`, `slug`, `description`, `price`, `stock`, `image`, `category`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 2, 'Voucher Game Rp 50.000', 'voucher-game-rp-50000', 'Voucher saldo game senilai Rp 50.000.', 48000, 25, 'images/products/voucher-50k.svg', 'Voucher', 1, '2026-10-08 13:41:35', '2026-10-08 14:57:01'),
(2, 2, 'Voucher Game Rp 100.000', 'voucher-game-rp-100000', 'Voucher saldo game senilai Rp 100.000.', 95000, 15, 'images/products/voucher-100k.svg', 'Voucher', 1, '2026-10-08 13:41:35', '2026-10-08 14:57:01'),
(3, 2, 'Skin Epic Random', 'skin-epic-random', 'Skin kualitas epic acak untuk akun kamu.', 75000, 20, 'images/products/skin-epic.svg', 'Skin', 1, '2026-10-08 13:41:35', '2026-10-08 14:57:01'),
(4, 2, 'Skin Legend Random', 'skin-legend-random', 'Skin kualitas legend acak.', 250000, 8, 'images/products/skin-legend.svg', 'Skin', 1, '2026-10-08 13:41:35', '2026-10-08 14:57:01'),
(5, 3, 'Akun Sultan Level 80', 'akun-sultan-level-80', 'Akun game level tinggi dengan banyak koleksi.', 500000, 3, 'images/products/akun-sultan.svg', 'Akun', 1, '2026-10-08 13:41:35', '2026-10-08 14:57:01'),
(6, 3, 'Akun Starter Level 30', 'akun-starter-level-30', 'Akun starter siap pakai untuk bermain.', 120000, 10, 'images/products/akun-starter.svg', 'Akun', 1, '2026-10-08 13:41:35', '2026-10-08 14:57:01'),
(7, 3, '1000 Koin In-Game', '1000-koin-in-game', 'Paket 1000 koin untuk kebutuhan in-game.', 100000, 30, 'images/products/koin.svg', 'Item', 1, '2026-10-08 13:41:35', '2026-10-08 14:57:01'),
(8, 3, 'Battle Pass Season Ini', 'battle-pass-season-ini', 'Battle pass season berjalan.', 150000, 18, 'images/products/battle-pass.svg', 'Item', 1, '2026-10-08 13:41:35', '2026-10-08 14:57:01');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2014_10_12_000000_create_users_table', 1),
(2, '2014_10_12_100000_create_password_resets_table', 1),
(3, '2019_08_19_000000_create_failed_jobs_table', 1),
(4, '2019_12_14_000001_create_personal_access_tokens_table', 1),
(5, '2026_10_09_000001_create_game_titles_table', 2),
(6, '2026_10_09_000002_create_products_table', 2),
(7, '2026_10_09_000003_create_transactions_table', 3),
(8, '2026_10_09_000004_create_marketplace_products_table', 3),
(9, '2026_10_09_000005_create_marketplace_orders_table', 3),
(10, '2026_10_09_000006_create_marketplace_order_items_table', 3),
(11, '2026_10_09_000007_add_role_to_users_table', 3);

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `game_title_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `price` bigint(20) UNSIGNED NOT NULL,
  `stock` int(11) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `game_title_id`, `name`, `description`, `price`, `stock`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, '86 Diamonds', '86 Diamonds + 8 Bonus', 22000, 100, 1, '2026-10-08 13:33:26', '2026-10-08 13:33:26'),
(2, 1, '172 Diamonds', '172 Diamonds + 16 Bonus', 44000, 100, 1, '2026-10-08 13:33:26', '2026-10-08 13:33:26'),
(3, 1, '257 Diamonds', '257 Diamonds + 23 Bonus', 65000, 100, 1, '2026-10-08 13:33:26', '2026-10-08 13:33:26'),
(6, 2, '100 Diamonds', '100 Diamonds', 15000, 100, 1, '2026-10-08 13:33:26', '2026-10-08 13:33:26'),
(7, 2, '310 Diamonds', '310 Diamonds', 45000, 100, 1, '2026-10-08 13:33:26', '2026-10-08 13:33:26'),
(8, 2, '520 Diamonds', '520 Diamonds', 73000, 100, 1, '2026-10-08 13:33:26', '2026-10-08 13:33:26'),
(9, 2, '1060 Diamonds', '1060 Diamonds', 145000, 100, 1, '2026-10-08 13:33:26', '2026-10-08 13:33:26'),
(10, 2, '2180 Diamonds', '2180 Diamonds', 290000, 100, 1, '2026-10-08 13:33:26', '2026-10-08 13:33:26'),
(11, 3, '60 UC', '60 Unknown Cash', 15000, 100, 1, '2026-10-08 13:33:26', '2026-10-08 13:33:26'),
(12, 3, '325 UC', '325 Unknown Cash', 75000, 100, 1, '2026-10-08 13:33:26', '2026-10-08 13:33:26'),
(13, 3, '660 UC', '660 Unknown Cash', 149000, 100, 1, '2026-10-08 13:33:26', '2026-10-08 13:33:26'),
(14, 3, '1800 UC', '1800 Unknown Cash', 399000, 100, 1, '2026-10-08 13:33:26', '2026-10-08 13:33:26'),
(15, 3, '3850 UC', '3850 Unknown Cash', 799000, 100, 1, '2026-10-08 13:33:26', '2026-10-08 13:33:26'),
(16, 4, '475 Points', '475 Valorant Points', 50000, 100, 1, '2026-10-08 13:33:27', '2026-10-08 13:33:27'),
(17, 4, '1000 Points', '1000 Valorant Points', 100000, 100, 1, '2026-10-08 13:33:27', '2026-10-08 13:33:27'),
(18, 4, '2050 Points', '2050 Valorant Points', 200000, 100, 1, '2026-10-08 13:33:27', '2026-10-08 13:33:27'),
(19, 4, '5350 Points', '5350 Valorant Points', 500000, 100, 1, '2026-10-08 13:33:27', '2026-10-08 13:33:27'),
(20, 4, '11000 Points', '11000 Valorant Points', 1000000, 100, 1, '2026-10-08 13:33:27', '2026-10-08 13:33:27'),
(21, 1, '706 Diamonds', '706 Diamonds + 63 Bonus', 175000, 100, 1, '2026-10-08 14:37:40', '2026-10-08 14:37:40'),
(22, 1, '1412 Diamonds', '1412 Diamonds + 126 Bonus', 350000, 100, 1, '2026-10-08 14:37:40', '2026-10-08 14:37:40');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `transaction_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `game_title_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `user_game_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `server_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_method` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` bigint(20) UNSIGNED NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `transaction_code`, `user_id`, `game_title_id`, `product_id`, `user_game_id`, `server_id`, `payment_method`, `amount`, `status`, `created_at`, `updated_at`) VALUES
(2, 'TXN-20261008-VNP7CK', 9, 3, 13, '123456', NULL, 'Bank Transfer', 149000, 'success', '2026-10-08 14:12:14', '2026-10-08 14:12:14');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'user',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `role`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Admin TopUpMarket', 'admin@topupmarket.test', NULL, '$2y$10$fX1GeBjvKjQX2xynfqIGJuUO50x9cRF7emqUKFksPljRe/q7pqUeC', 'user', NULL, '2026-10-08 13:41:34', '2026-10-08 14:37:40'),
(2, 'GameStore ID', 'seller1@topupmarket.test', NULL, '$2y$10$qJ3ZwNyZ4kDwMhJrHECjf.n/an4/.GL1IWdPJ0qywjQDZ3pXGaIzi', 'user', NULL, '2026-10-08 13:41:34', '2026-10-08 13:41:34'),
(3, 'Sultan Gaming', 'seller2@topupmarket.test', NULL, '$2y$10$XaiaN02juE.DT1WIAufY/enUKGxN4mQO.vtElydmOmXTV62nNhkkO', 'user', NULL, '2026-10-08 13:41:34', '2026-10-08 13:41:34'),
(4, 'User Demo', 'user@topupmarket.test', NULL, '$2y$10$rU20PvG9NNLD2szWMoUGTehTBH9/ztUrYKWixZ7SQ71CwUi.05nGS', 'user', NULL, '2026-10-08 13:41:34', '2026-10-08 13:41:34'),
(9, 'nino', 'Nino12@gmail.com', NULL, '$2y$10$LKxAkBAZXgg9kk.hCk8jxemMbo7BeEl7pwAmtYxiIzgKYWAgIxVVC', 'user', NULL, '2026-10-08 14:03:17', '2026-10-08 14:03:17'),
(10, 'Admin TopUpMarket', 'admin123@gmail.com', NULL, '$2y$10$TRTXie14nPj7kYyBxkuo9.LDBSvIr0Y3f7AN.6LB4rusMZwj2/aCO', 'admin', NULL, '2026-10-08 14:36:31', '2026-10-08 14:36:31');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `game_titles`
--
ALTER TABLE `game_titles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `game_titles_slug_unique` (`slug`);

--
-- Indexes for table `marketplace_orders`
--
ALTER TABLE `marketplace_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `marketplace_orders_order_code_unique` (`order_code`),
  ADD KEY `marketplace_orders_user_id_foreign` (`user_id`);

--
-- Indexes for table `marketplace_order_items`
--
ALTER TABLE `marketplace_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `marketplace_order_items_marketplace_order_id_foreign` (`marketplace_order_id`),
  ADD KEY `marketplace_order_items_marketplace_product_id_foreign` (`marketplace_product_id`);

--
-- Indexes for table `marketplace_products`
--
ALTER TABLE `marketplace_products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `marketplace_products_slug_unique` (`slug`),
  ADD KEY `marketplace_products_user_id_foreign` (`user_id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD KEY `password_resets_email_index` (`email`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `products_game_title_id_foreign` (`game_title_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transactions_transaction_code_unique` (`transaction_code`),
  ADD KEY `transactions_user_id_foreign` (`user_id`),
  ADD KEY `transactions_game_title_id_foreign` (`game_title_id`),
  ADD KEY `transactions_product_id_foreign` (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `game_titles`
--
ALTER TABLE `game_titles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `marketplace_orders`
--
ALTER TABLE `marketplace_orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `marketplace_order_items`
--
ALTER TABLE `marketplace_order_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `marketplace_products`
--
ALTER TABLE `marketplace_products`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `marketplace_orders`
--
ALTER TABLE `marketplace_orders`
  ADD CONSTRAINT `marketplace_orders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `marketplace_order_items`
--
ALTER TABLE `marketplace_order_items`
  ADD CONSTRAINT `marketplace_order_items_marketplace_order_id_foreign` FOREIGN KEY (`marketplace_order_id`) REFERENCES `marketplace_orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `marketplace_order_items_marketplace_product_id_foreign` FOREIGN KEY (`marketplace_product_id`) REFERENCES `marketplace_products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `marketplace_products`
--
ALTER TABLE `marketplace_products`
  ADD CONSTRAINT `marketplace_products_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_game_title_id_foreign` FOREIGN KEY (`game_title_id`) REFERENCES `game_titles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_game_title_id_foreign` FOREIGN KEY (`game_title_id`) REFERENCES `game_titles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transactions_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transactions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
