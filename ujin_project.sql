-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1:3306
-- Время создания: Апр 28 2025 г., 03:15
-- Версия сервера: 5.5.62
-- Версия PHP: 7.4.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `ujin_project`
--

-- --------------------------------------------------------

--
-- Структура таблицы `requests`
--

CREATE TABLE `requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `request_number` varchar(20) NOT NULL,
  `title` varchar(100) NOT NULL,
  `address` text NOT NULL,
  `phone` varchar(20) NOT NULL,
  `service_date` date NOT NULL,
  `service_time` time NOT NULL,
  `service_type` varchar(50) NOT NULL,
  `service_description` text,
  `payment_method` varchar(20) NOT NULL,
  `status` enum('new','in_progress','completed','rejected') DEFAULT 'new',
  `rejection_reason` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `cancel_reason` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `requests`
--

INSERT INTO `requests` (`id`, `user_id`, `request_number`, `title`, `address`, `phone`, `service_date`, `service_time`, `service_type`, `service_description`, `payment_method`, `status`, `rejection_reason`, `created_at`, `cancel_reason`) VALUES
(1, 1, 'R202504265435', 'Заявка номер 1(тестовая)', 'Бульвар Гагарина 107/4 кв 44', '+7(996)323-94-77', '2025-04-28', '10:00:00', 'repair', '', 'cash', 'completed', NULL, '2025-04-26 17:16:35', NULL),
(2, 1, 'R202504265285', 'заявка номер 2', 'студенческая 5', '+7(996)323-94-77', '2025-04-28', '10:00:00', 'delivery', '', 'card', 'completed', NULL, '2025-04-26 17:19:41', NULL),
(3, 1, 'R202504269206', 'заказ на оборудование для умного дома', 'Пушкина 107а', '+7(996)323-94-77', '2025-04-28', '10:00:00', 'other', 'Установка оборудования для умного домафона', 'card', 'rejected', 'нормально напиши имбицил', '2025-04-26 19:26:13', NULL),
(4, 4, 'R202504269831', 'заказ на оборудование для умного дома для компании чунга чанга', 'Пушкина 107а', '+7(996)323-94-77', '2026-05-12', '09:50:00', 'other', 'system call generate thermal element form element arroy shape dishgarge', 'card', 'in_progress', '', '2025-04-26 19:51:27', NULL),
(6, 1, 'R202504282236', 'lawlawha', 'aenlNLNE', '+7(999)999-99-99', '1212-02-12', '12:00:00', 'cleaning', '', 'cash', 'rejected', 'илилилилил', '2025-04-27 22:58:52', NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `fullname`, `phone`, `username`, `password`, `created_at`) VALUES
(1, 'Шлыков Павел Александрович', '+79963239477', 'pavel', '$2y$10$DjJYfaU2TPFWqDOiqM8z1OicxY4ns8tf.VcKdCC377XzC/cpMbH62', '2025-04-26 12:26:56'),
(3, 'Больнов Виктор Геннадьевич', '+71234789121', 'victor2025', '$2y$10$qbPKA2HvcYZKvuqr.ewPS.oOHEJEr0RP7GKVamlcjrvoujS2y/Go.', '2025-04-26 12:30:46'),
(4, 'Больнов Виктор Геннадьевич', '+79963239477', 'pasha', '$2y$10$5jvxEadlOfoiuRo0rF8/mO654im3HzPaQwu5ozuWbiIc3UlUWu2Ie', '2025-04-26 17:27:29');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `requests`
--
ALTER TABLE `requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `request_number` (`request_number`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `requests`
--
ALTER TABLE `requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `requests`
--
ALTER TABLE `requests`
  ADD CONSTRAINT `requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
