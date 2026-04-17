-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 17, 2026 at 05:19 AM
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
-- Database: `phone_shop`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `session_id` varchar(100) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `storage` varchar(50) DEFAULT '',
  `color` varchar(50) DEFAULT '',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`) VALUES
(1, 'Apple'),
(2, 'Samsung'),
(3, 'Xiaomi'),
(4, 'Huawei'),
(5, 'OnePlus'),
(6, 'Google');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_no` varchar(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','paid','shipped','completed','cancelled') DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_no`, `user_id`, `total_amount`, `status`, `created_at`) VALUES
(1, 'ORD20240001', 2, 6499.00, 'paid', '2026-04-15 16:46:08'),
(2, 'ORD20240002', 2, 3999.00, 'shipped', '2026-04-15 16:46:08'),
(3, 'ORD20240003', 2, 1599.00, 'pending', '2026-04-15 16:46:08'),
(4, 'ORD202604174967', 3, 4299.00, 'cancelled', '2026-04-17 01:56:04'),
(5, 'ORD202604177015', 3, 4299.00, 'pending', '2026-04-17 02:43:15'),
(6, 'ORD202604174643', 3, 3499.00, 'pending', '2026-04-17 03:00:13'),
(7, 'ORD202604173566', 3, 1599.00, 'shipped', '2026-04-17 03:07:11'),
(8, 'ORD202604176520', 5, 107475.00, 'pending', '2026-04-17 04:29:50'),
(9, 'ORD202604175163', 5, 49990.00, 'pending', '2026-04-17 04:31:20');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(1, 1, 1, 1, 6499.00),
(2, 2, 6, 1, 3999.00),
(3, 3, 11, 1, 1599.00),
(4, 4, 10, 1, 4299.00),
(5, 5, 10, 1, 4299.00),
(6, 6, 7, 1, 3499.00),
(7, 7, 11, 1, 1599.00),
(8, 8, 10, 25, 4299.00),
(9, 9, 9, 10, 4999.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `processor` varchar(100) DEFAULT NULL,
  `ram` int(11) DEFAULT NULL,
  `storage` varchar(50) DEFAULT NULL,
  `colors` text DEFAULT NULL,
  `variant_prices` text DEFAULT NULL,
  `battery` int(11) DEFAULT NULL,
  `camera` varchar(100) DEFAULT NULL,
  `display` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `category_id` int(11) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `processor`, `ram`, `storage`, `colors`, `variant_prices`, `battery`, `camera`, `display`, `price`, `stock`, `category_id`, `image_path`, `created_at`) VALUES
(1, 'iPhone 15 Pro Max', 'Apple flagship with titanium design', 'A17 Pro', 8, '256GB,512GB,1TB', 'Natural Titanium,Blue Titanium,White Titanium,Black Titanium', '{\"256GB\":6499,\"512GB\":7499,\"1TB\":8499}', 4442, '48MP Main + 12MP Ultra Wide + 12MP Telephoto', '6.7\" Super Retina XDR OLED, 120Hz', 6499.00, 30, 1, '69e14d94e99bb.png', '2026-04-15 16:46:08'),
(2, 'iPhone 15 Pro', 'Powerful A17 Pro chip', 'A17 Pro', 8, '128GB,256GB,512GB,1TB', 'Natural Titanium,Blue Titanium,White Titanium,Black Titanium', '{\"128GB\":5499,\"256GB\":6499,\"512GB\":7499,\"1TB\":8499}', 3274, '48MP Main + 12MP Ultra Wide + 12MP Telephoto', '6.1\" Super Retina XDR OLED, 120Hz', 5499.00, 25, 1, '69e14d8b2f15b.png', '2026-04-15 16:46:08'),
(3, 'Samsung Galaxy S24 Ultra', 'AI phone with S Pen', 'Snapdragon 8 Gen 3', 12, '256GB,512GB,1TB', 'Titanium Black,Titanium Gray,Titanium Violet,Titanium Yellow', '{\"256GB\":6499,\"512GB\":7199,\"1TB\":8199}', 5000, '200MP Main + 50MP Telephoto + 12MP Ultra Wide', '6.8\" Dynamic AMOLED 2X, 120Hz', 6499.00, 15, 2, '69e14d8032978.jpg', '2026-04-15 16:46:08'),
(4, 'Samsung Galaxy S24 Plus', 'Compact AI flagship', 'Snapdragon 8 Gen 3', 12, '256GB,512GB', 'Onyx Black,Marble Gray,Cobalt Violet,Amber Yellow', '{\"256GB\":4999,\"512GB\":5699}', 4900, '50MP Main + 12MP Ultra Wide + 10MP Telephoto', '6.7\" Dynamic AMOLED 2X, 120Hz', 4999.00, 20, 2, '69e14d7704972.webp', '2026-04-15 16:46:08'),
(5, 'Xiaomi 14 Ultra', 'Leica professional camera', 'Snapdragon 8 Gen 3', 16, '512GB,1TB', 'Black,White', '{\"512GB\":5999,\"1TB\":6999}', 5300, '50MP Main + 50MP Ultra Wide + 50MP Telephoto', '6.73\" AMOLED, 120Hz', 5999.00, 10, 3, '69e14d6c90607.jpg', '2026-04-15 16:46:08'),
(6, 'Xiaomi 14', 'Leica Summilux lens', 'Snapdragon 8 Gen 3', 12, '256GB,512GB', 'Black,White,Jade Green,Pink', '{\"256GB\":3999,\"512GB\":4599}', 4610, '50MP Light Fusion 900 + 50MP Ultra Wide + 50MP Telephoto', '6.36\" AMOLED, 120Hz', 3999.00, 25, 3, '69e14d64174ce.webp', '2026-04-15 16:46:08'),
(7, 'OnePlus 12', 'Hasselblad camera system', 'Snapdragon 8 Gen 3', 12, '256GB,512GB', 'Flowy Emerald,Silky Black', '{\"256GB\":3499,\"512GB\":4099}', 5400, '50MP Main + 48MP Ultra Wide + 64MP Telephoto', '6.82\" 2K 120Hz ProXDR', 3499.00, 19, 5, '69e14d57941e1.png', '2026-04-15 16:46:08'),
(8, 'Google Pixel 8 Pro', 'Google AI experience', 'Google Tensor G3', 12, '128GB,256GB,512GB', 'Obsidian,Porcelain,Bay', '{\"128GB\":4999,\"256GB\":5599,\"512GB\":6299}', 5050, '50MP Main + 48MP Ultra Wide + 48MP Telephoto', '6.7\" Super Actua Display, 120Hz', 4999.00, 15, 6, '69e14d4cb6021.jpg', '2026-04-15 16:46:08'),
(9, 'Huawei P60 Pro', 'Ultra lighting camera', 'Snapdragon 8+ Gen 1', 8, '256GB,512GB', 'Black,White,Green,Rose Gold', '{\"256GB\":4999,\"512GB\":5699}', 4815, '48MP Ultra Lighting + 48MP Telephoto + 13MP Ultra Wide', '6.67\" LTPO OLED, 120Hz', 4999.00, 4, 4, '69e14d354e5bb.jpg', '2026-04-15 16:46:08'),
(10, 'iPhone 15', 'A16 Bionic with Dynamic Island', 'A16 Bionic', 6, '128GB,256GB,512GB', 'Black,Blue,Green,Yellow,Pink', '{\"128GB\":4299,\"256GB\":4999,\"512GB\":5799}', 3349, '48MP Main + 12MP Ultra Wide', '6.1\" Super Retina XDR OLED', 4299.00, 3, 1, '69e14dcec2b0b.webp', '2026-04-15 16:46:08'),
(11, 'Samsung Galaxy A54', 'Best mid-range phone', 'Exynos 1380', 6, '128GB,256GB', 'Awesome Lime,Awesome Graphite,Awesome Violet', '{\"128GB\":1599,\"256GB\":1899}', 5000, '50MP Main + 12MP Ultra Wide + 5MP Macro', '6.4\" Super AMOLED, 120Hz', 1599.00, 39, 2, '69e14d1d3a1cd.jpg', '2026-04-15 16:46:08');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `security_question` varchar(200) DEFAULT NULL,
  `security_answer` varchar(100) DEFAULT NULL,
  `role` enum('admin','member') DEFAULT 'member',
  `login_attempts` int(11) DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `is_blocked` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `security_question`, `security_answer`, `role`, `login_attempts`, `locked_until`, `is_blocked`, `created_at`) VALUES
(1, 'Admin', 'admin@gmail.com', '5baa61e4c9b93f3f0682250b6cf8331b7ee68fd8', NULL, NULL, 'admin', 0, NULL, 0, '2026-04-15 16:46:08'),
(2, 'John Doe', 'john@gmail.com', '5baa61e4c9b93f3f0682250b6cf8331b7ee68fd8', NULL, NULL, 'member', 0, NULL, 0, '2026-04-15 16:46:08'),
(3, 'Jason', 'jason@gmail.com', '7110eda4d09e062aa5e4a390b0a572ac0d2c0220', NULL, NULL, 'member', 1, NULL, 0, '2026-04-17 01:55:17'),
(4, 'alex', 'alex@gmail.com', 'd5f12e53a182c062b6bf30c1445153faff12269a', NULL, NULL, 'member', 0, NULL, 0, '2026-04-17 03:52:04'),
(5, 'kenneth', 'kenneth@gmail.com', '7d695548f82a9589a5b09da95040ad6930ce8b86', NULL, NULL, 'member', 0, NULL, 0, '2026-04-17 04:06:58'),
(6, 'ali', 'ali@gmail.com', '4c1b52409cf6be3896cf163fa17b32e4da293f2e', 'What is your favorite food?', 'nasi lemak', 'member', 0, NULL, 0, '2026-04-17 11:17:29');

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `idx_session` (`session_id`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_no` (`order_no`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_wishlist` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
