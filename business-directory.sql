-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Mar 24, 2026 at 04:45 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `business-directory`
--

-- --------------------------------------------------------

--
-- Table structure for table `businesses`
--

CREATE TABLE `businesses` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `category_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `businesses`
--

INSERT INTO `businesses` (`id`, `name`, `category_id`, `user_id`, `description`, `address`, `phone`, `email`, `website`, `image`, `status`, `admin_notes`, `created_at`, `updated_at`) VALUES
(1, 'total petrol station', 4, 1, 'quality fuel engine care durable car life', 'maseno busia road', '0788665543', '', '', 'business_69af9feb1b3da.jpg', 'approved', NULL, '2026-03-10 04:36:59', '2026-03-10 04:37:09'),
(2, 'Goodies grocery', 3, 1, 'find the best and freshest fruits in the market here at affordable prices', 'marikiti', '+254112453623', 'marikiti@grocery.com', '', 'business_69b41001d5578.jpg', 'approved', NULL, '2026-03-13 13:24:17', '2026-03-13 14:15:52'),
(3, 'croton motors', 7, 1, 'custom made, new cars, second-hand cars, quality vehicles at affordable prices', 'kisumu', '+254113431474', 'croton@motors.com', '', 'business_69b422e74f75a.jpg', 'approved', NULL, '2026-03-13 14:44:55', '2026-03-13 15:05:33'),
(4, 'cartmart motors', 7, 1, 'best car dealers in town financial Sells new and used vehicles, offers financing and trade-ins', 'nairobi 77 road', '0786574301', 'cartmart@motors.com', '', 'business_69b4240e9aff8.jpg', 'approved', NULL, '2026-03-13 14:49:50', '2026-03-13 15:05:46'),
(5, 'motor residence', 7, 1, 'Stocks genuine & aftermarket vehicle spare parts & accessories', 'kakamega', '07622345622', 'motor@residence.com', '', 'business_69b4262adec45.jpg', 'approved', NULL, '2026-03-13 14:58:50', '2026-03-13 15:05:50'),
(6, 'ahad motors', 7, 1, 'Ahad Car Motors\r\nYour Trusted Automotive Partner\r\nAhad Car Motors is a premier automotive dealership offering a wide selection of quality new and used vehicles to suit every budget and lifestyle. Located in the heart of the community, we specialize in sourcing reliable Japanese, European and locally available vehicles with full inspection and warranty assurance.', 'mombasa', '+254700022255', 'ahad@motors.com', '', 'business_69b426dbb4890.jpg', 'approved', NULL, '2026-03-13 15:01:47', '2026-03-13 15:05:53'),
(7, 'weka mawe sport cars', 7, 1, 'Classic and vintage cars hold a timeless charm, making them a top choice for car enthusiasts and collectors. If you’re passionate about owning a piece of automotive history, Kenya offers a vibrant market filled with opportunities.\r\nLet me take you through everything you need to know about buying classic cars in Kenya, from listings to tips for finding the perfect ride.', 'nakuru', '+254113151513', 'wekamawe@sportcars.com', '', 'business_69b4294064da8.jpg', 'approved', NULL, '2026-03-13 15:12:00', '2026-03-13 15:12:08'),
(8, 'hotbakers', 12, 1, 'find the best savories in town that will leave your mouth salivating, call visit to place your orders. free samples every second weekend of the month', 'maseno siriba road', '+254777788822', 'hotbakers@bakery.com', '', 'biz_8_1773416544.jpg', 'approved', NULL, '2026-03-13 15:16:34', '2026-03-13 15:42:33'),
(9, 'TopV bakers', 12, 1, 'quality bakes at our joint, freshness and quality is our priority', 'Mombasa', '+254799888111', 'TopV@bakers.com', '', 'business_69b431d869dae.jpg', 'approved', NULL, '2026-03-13 15:48:40', '2026-03-13 15:55:53'),
(10, 'Tamu bakers', 12, 1, 'we grace all your special events with our professional services, be it birthday, graduation, weddings, anniversaries. visit our shop to place your orders', 'Kisumu', '0754545454', 'Tamu@bakers.com', '', 'business_69b432b44f457.jpg', 'approved', NULL, '2026-03-13 15:52:20', '2026-03-13 15:55:49'),
(11, 'comrade Bakers', 12, 1, 'find the best bakes around campus, we offer samples every once in a while. we value our client`s preferences', 'Maseno', '+254112345678', 'Comrade@bakers.com', '', 'business_69b4337b4c807.jpg', 'approved', NULL, '2026-03-13 15:55:39', '2026-03-13 15:55:46'),
(12, 'Beach View hotel', 2, 1, 'find the best scenaries where you experience cool theraputic atmosphere', 'mombasa', '+254766655444', '', '', 'business_69b4344385aaf.jpg', 'approved', NULL, '2026-03-13 15:58:59', '2026-03-13 15:59:30'),
(13, 'Lake Basin hotel', 2, 1, 'lake basin hotel is the best experience you will get along the lake basin all at an affordable price', 'kisumu', '+254733222000', 'lakebasin@hotel.com', '', 'business_69b435091fe94.jpg', 'approved', NULL, '2026-03-13 16:02:17', '2026-03-13 16:12:57'),
(14, 'Real xp Hotel', 2, 1, 'the best way to enjoy your weekend, vacation is to visit xp come relax, refresh', 'kakamega', '+254700333122', 'realex@hotel.com', '', 'business_69b436020c388.jpg', 'approved', NULL, '2026-03-13 16:06:26', '2026-03-13 16:12:50'),
(15, 'big gem hotel', 2, 1, 'A sanctuary built upon the \"Living Coral Cliffs\" where the structure itself is woven from enchanted bamboo and salt-resistant dragon-silk. come experience the breath taking scenary', 'Lela', '+254712123434', 'biggem@hotel.com', '', 'business_69b4375b131b6.jpg', 'approved', NULL, '2026-03-13 16:12:11', '2026-03-13 16:12:42'),
(16, 'The Star', 2, 1, 'Experience the ultimate retreat in a room designed for rest. The breathable canopy and plush linens ensure a cool, serene atmosphere, while the natural wood textures bring a sense of organic luxury to your stay.', 'nairobi', '+254707060504', 'thestar@hotel.com', '', 'business_69b43a6084f86.jpg', 'approved', NULL, '2026-03-13 16:25:04', '2026-03-13 16:27:31'),
(17, 'cozzy hotel', 2, 1, 'nice rooms for comfortable stay and quality services', 'nairobi', '+254700999111', 'cozzy@hotels.com', '', 'business_69b43ae765201.jpg', 'approved', NULL, '2026-03-13 16:27:19', '2026-03-13 16:27:26'),
(18, 'KCB bank', 9, 1, 'banking services brought closer we are open from 8am to 4pm mon-fri and 9am to 1pm on saturday', 'Nairobi upperhill', '+254766654449', 'kcb@banks.com', '', 'business_69b43c07f0679.jpg', 'approved', NULL, '2026-03-13 16:32:07', '2026-03-13 16:35:17'),
(19, 'SBM banks', 9, 1, 'family banking open accounts savings accounts', 'Kisumu', '+254113345645', 'sbm@banks.com', '', 'business_69b43cb985b32.jpg', 'approved', NULL, '2026-03-13 16:35:05', '2026-03-13 16:38:48'),
(20, 'Central Bank', 9, 1, 'the home for your finances the heart of every coin', 'nairobi', '0700033322', 'centralbank@banks.com', '', 'business_69b43d845821d.jpg', 'approved', NULL, '2026-03-13 16:38:28', '2026-03-13 16:39:00'),
(21, 'Equity bank', 9, 1, 'Bringing equity and access to banking services to all people regardless of class', 'luanda', '+25423000000', 'equity@bank.com', '', 'business_69b43eaeda229.jpg', 'approved', NULL, '2026-03-13 16:43:26', '2026-03-13 16:48:29'),
(22, 'co-operative bank', 9, 1, 'we listen more to you and we work together to make it work', 'Maseno', '+254700011133', 'co-op@banks.com', '', 'business_69b43f2a4cdf5.jpg', 'approved', NULL, '2026-03-13 16:45:30', '2026-03-13 16:48:25'),
(23, 'i&M Bank', 9, 1, 'banking made easier and faster more effective', 'Mombasa', '0722233100', 'i&M@banks.com', '', 'business_69b43fd09f12a.jpg', 'approved', NULL, '2026-03-13 16:48:16', '2026-03-13 16:48:22'),
(24, 'cyd beauty and wellness', 11, 1, 'at cyd`s we make your nails shine like a gem', 'Kisumu', '+25467783920514', 'cydparlour@beauty.com', '', 'business_69bb10c7c28c9.jpg', 'approved', NULL, '2026-03-18 20:53:27', '2026-03-18 20:53:47'),
(25, 'Qatar farmers shop', 3, 2, 'find all fertilizers, seeds, insecticides, pesticides and all you need for your crops and animals', 'kakamega', '+254777755520', 'mkulima@shop.com', '', 'business_69bb8bf10adea.jpg', 'approved', NULL, '2026-03-19 05:38:57', '2026-03-19 05:54:20'),
(26, 'changamka academy', 5, 2, 'we commit to excellence', 'maseno', '+254755432698', 'changamkacademy@gmail.com', '', 'business_69bb8df6e9ec7.jpg', 'approved', NULL, '2026-03-19 05:47:34', '2026-03-19 05:54:17'),
(27, 'Mjengo Hardware', 3, 2, 'all building materials are found here', 'nakuru', '+2547766893455', 'mjengo@hardware.com', '', 'business_69bb8e5c1b803.jpg', 'approved', NULL, '2026-03-19 05:49:16', '2026-03-19 05:54:13'),
(28, 'ujenzi bora hardware', 3, 2, 'every tool and equipment for your construction, farm, household can be found here all at affordable prices', 'luanda', '+254113456700', 'ujenzi@tools.com', '', 'business_69bb8efac2876.jpg', 'approved', NULL, '2026-03-19 05:51:54', '2026-03-19 05:54:10'),
(29, 'Ace Hardware shop', 3, 2, 'the aids to your tasks and all necessary machinery at affordable prices', 'Nairobi', '+254112121212', 'AceHardware@gmail.com', '', 'business_69bb8f6d78829.jpg', 'approved', NULL, '2026-03-19 05:53:49', '2026-03-19 05:54:07'),
(30, 'Omo fun city', 8, 10, 'enjoy life live to the fullest', 'kisumu', '0722333001', 'omofun@city.com', '', 'business_69bb91f65814a.jpg', 'approved', NULL, '2026-03-19 06:04:38', '2026-03-19 06:04:59'),
(31, 'Jenga gym', 11, 1, 'qualified trainers and plenty gym equipment at your disposal', 'maseno', '+254776777879', 'Jengagym@gmail.com', '', 'business_69bb959950db8.jpg', 'approved', NULL, '2026-03-19 06:20:09', '2026-03-19 06:20:15'),
(32, 'Msanii Cradle', 8, 10, 'we record the best music come give it a try quality guaranteed', 'nairobi', '+254711234561', 'music@email.com', '', 'business_69bbbd512e524.png', 'approved', NULL, '2026-03-19 09:09:37', '2026-03-19 09:10:11'),
(33, 'wakili advocates', 10, 1, 'Stay informed with our expert legal insights, industry updates, and actionable knowledge', 'mombasa', '+254765065000', 'wakili@advocates.com', '', 'business_69bbc3a90b4cb.jpg', 'approved', NULL, '2026-03-19 09:36:41', '2026-03-19 09:53:19'),
(34, 'Maskani Architects', 10, 1, 'team of consulting architects in the field of design, construction and architectural branding', 'Nairobi', '+254700077707', 'architects@gmail.com', '', 'business_69bbc6b4d24a9.jpg', 'approved', NULL, '2026-03-19 09:49:40', '2026-03-19 09:53:15'),
(35, 'Financial Advisors', 10, 1, 'issues on money finance and accounting visit us call us we offer best advise', 'Nakuru', '+254788909090', 'Accountants@gmail.com', '', 'business_69bbc780464e4.jpg', 'approved', NULL, '2026-03-19 09:53:04', '2026-03-19 09:53:12'),
(36, 'Eateries', 1, 1, 'best dishes in town visit for best dishes served hot', 'Maseno', '0700001111', 'foodie@restaurant.com', '', 'business_69bbc858a9e44.jpg', 'approved', NULL, '2026-03-19 09:56:40', '2026-03-19 10:02:04'),
(37, 'nyama baze', 1, 1, 'best choma with drinks of every kind every weekday', 'Nakuru drive 55', '+254788966754', 'choma@gmail.com', '', 'business_69bbc903cce84.jpg', 'approved', NULL, '2026-03-19 09:59:31', '2026-03-19 10:02:01'),
(38, 'foodie restaurants', 1, 1, 'all snacks, fast foods available here healthy practises prioritized', 'Lela', '0711122233', 'foodie@restaurant.com', '', 'business_69bbc98564889.jpg', 'approved', NULL, '2026-03-19 10:01:41', '2026-03-19 10:01:55'),
(39, 'hello learners', 5, 1, 'the best way for your kid to start your schooling journey', 'luanda', '0712343412', 'learners@gmail.com', '', 'business_69bbcaf56a28c.jpg', 'approved', NULL, '2026-03-19 10:07:49', '2026-03-19 10:38:57'),
(40, 'Elimu stars', 5, 1, 'quality development journey for your young stars', 'Maseno', '0711202030', 'Elimustars@gmail.com', '', 'business_69bbcb8e2a774.jpg', 'approved', NULL, '2026-03-19 10:10:22', '2026-03-19 10:38:53'),
(41, 'MegaLife Hospital', 6, 1, 'your health our priority quality services 24/7', 'kisumu', '0776544332', 'megahospital@gmail.com', '', 'business_69bbd13b595cc.jpg', 'approved', NULL, '2026-03-19 10:34:35', '2026-03-19 10:38:49'),
(42, 'Goodwill Hospital', 6, 1, 'professionalism at practice driven by the fear of God', 'Nakuru', '0788999777', 'GoodwillH@gmail.com', '', 'business_69bbd22e51997.jpg', 'approved', NULL, '2026-03-19 10:38:38', '2026-03-19 10:38:45'),
(43, 'NoraSupermarket', 3, 1, 'Find all you need at top quality, affordable prices and market breaking offers', 'Nakuru', '+254112000377', 'Supermarket@gmail.com', '', 'business_69bc31b1486bd.jpg', 'approved', NULL, '2026-03-19 17:26:09', '2026-03-19 17:26:18'),
(44, 'ncarwash', 4, 1, 'we leave your car sparkling clean', 'Maseno', '+254788877764', 'Ncarwash@gmail.com', '', 'business_69bc331c3afc7.jpg', 'approved', NULL, '2026-03-19 17:32:12', '2026-03-19 17:32:17'),
(45, 'Gwash', 4, 1, 'machine driven carwash that leaves every part of your vehicle cleaned', 'kisumu', '+254700989898', 'Gwash@gmail.com', '', 'business_69bc349f9feba.jpg', 'rejected', NULL, '2026-03-19 17:38:39', '2026-03-24 10:59:47');

-- --------------------------------------------------------

--
-- Table structure for table `business_status_logs`
--

CREATE TABLE `business_status_logs` (
  `id` int(11) NOT NULL,
  `business_id` int(11) NOT NULL,
  `old_status` varchar(20) DEFAULT NULL,
  `new_status` varchar(20) NOT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `icon`, `created_at`) VALUES
(1, 'Restaurants', 'fa-utensils', '2026-03-10 03:04:50'),
(2, 'Hotels', 'fa-hotel', '2026-03-10 03:04:50'),
(3, 'Shopping', 'fa-shopping-bag', '2026-03-10 03:04:50'),
(4, 'Services', 'fa-wrench', '2026-03-10 03:04:50'),
(5, 'Education', 'fa-graduation-cap', '2026-03-10 03:04:50'),
(6, 'Health & Medical', 'fa-hospital', '2026-03-10 03:04:50'),
(7, 'Automotive', 'fa-car', '2026-03-10 03:04:50'),
(8, 'Entertainment', 'fa-film', '2026-03-10 03:04:50'),
(9, 'Banks', NULL, '2026-03-13 14:06:17'),
(10, 'Professional Services', NULL, '2026-03-13 14:06:17'),
(11, 'Beauty & Wellness', NULL, '2026-03-13 14:06:17'),
(12, 'Bakery', NULL, '2026-03-13 14:20:07');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `status` enum('open','closed') DEFAULT 'open',
  `email_verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `phone`, `address`, `profile_image`, `role`, `status`, `email_verified`, `created_at`, `updated_at`, `last_login`) VALUES
(1, 'admin', 'admin@business-directory.com', '$2y$10$.5nLcI46fBBuxclmKA4HM.g8qP7i9W1ZUcB8CzWurixM3c.MFXQaO', 'Administrator', NULL, NULL, NULL, 'admin', 'open', 0, '2026-03-10 03:04:50', '2026-03-10 03:33:40', NULL),
(2, '', 'kiki@user.com', '$2y$10$sCDwCWunbgPlo3QXK2A0e.xlnQbMOfyQJAhdc8N8wJRclT6sPC8F2', 'kiki', '', NULL, NULL, 'user', 'open', 0, '2026-03-10 03:48:29', '2026-03-10 03:48:29', NULL),
(10, 'omo', 'businessowner1@email.com', '$2y$10$B38Vrp5JnBd.K88nuHBlduRvabgmvgZkI17l0sNqCU/NNHxevZn5u', 'businessowner1', '', NULL, NULL, 'user', 'open', 0, '2026-03-19 06:02:57', '2026-03-19 07:18:35', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `businesses`
--
ALTER TABLE `businesses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `business_status_logs`
--
ALTER TABLE `business_status_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `business_id` (`business_id`),
  ADD KEY `changed_by` (`changed_by`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `businesses`
--
ALTER TABLE `businesses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `business_status_logs`
--
ALTER TABLE `business_status_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
