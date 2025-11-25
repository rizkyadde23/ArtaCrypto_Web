-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 25, 2025 at 04:54 AM
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
-- Database: `artacrypto`
--

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `coin_id` varchar(50) DEFAULT NULL,
  `type` enum('buy','sell') NOT NULL,
  `amount` decimal(18,8) NOT NULL,
  `price` decimal(18,8) NOT NULL,
  `total_value` decimal(18,8) GENERATED ALWAYS AS (`amount` * `price`) STORED,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `user_id`, `coin_id`, `type`, `amount`, `price`, `created_at`) VALUES
(8, 4, 'bitcoin', 'buy', 1.00000000, 86573.00000000, '2025-11-25 03:04:01'),
(9, 4, 'bitcoin', 'sell', 1.00000000, 86573.00000000, '2025-11-25 03:14:47'),
(10, 4, 'ethereum', 'buy', 1.00000000, 2834.52000000, '2025-11-25 03:18:14'),
(11, 4, 'ethereum', 'sell', 1.00000000, 2834.52000000, '2025-11-25 03:26:33'),
(12, 4, 'bitcoin', 'buy', 1.00000000, 86573.00000000, '2025-11-25 03:32:09'),
(13, 4, 'bitcoin', 'sell', 1.00000000, 86573.00000000, '2025-11-25 03:33:52'),
(14, 4, 'ethereum', 'buy', 3.52793418, 2834.52000000, '2025-11-25 03:34:02'),
(15, 4, 'ethereum', 'buy', 3.52793418, 2834.52000000, '2025-11-25 03:34:29');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `coin_id` (`coin_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`coin_id`) REFERENCES `coins` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
