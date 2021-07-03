-- phpMyAdmin SQL Dump
-- version 4.6.4
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1
-- Время создания: Июл 03 2021 г., 20:12
-- Версия сервера: 5.7.15
-- Версия PHP: 7.0.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `parcer.rad`
--

-- --------------------------------------------------------

--
-- Структура таблицы `our_u_options`
--

CREATE TABLE `our_u_options` (
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `key` varchar(30) NOT NULL,
  `value` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `our_u_roles`
--

CREATE TABLE `our_u_roles` (
  `id` smallint(5) UNSIGNED NOT NULL,
  `role` varchar(30) NOT NULL,
  `description` tinytext NOT NULL,
  `level` tinyint(3) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `our_u_roles`
--

INSERT INTO `our_u_roles` (`id`, `role`, `description`, `level`) VALUES
(1, 'view_debug_info', 'видеть отладочную информацию', 100),
(2, 'edit_users', 'управлять пользователями', 100),
(3, 'edit_settings', 'управлять настройками сайта', 100),
(4, 'ignore_max_token_remember', 'Игнорирование ограничения по количеству запомненных устройств', 100);

-- --------------------------------------------------------

--
-- Структура таблицы `our_u_tokens`
--

CREATE TABLE `our_u_tokens` (
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `token` varbinary(32) NOT NULL,
  `user_agent` varbinary(20) NOT NULL,
  `time_start` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `time_end` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `our_u_users`
--

CREATE TABLE `our_u_users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `login` varchar(30) NOT NULL,
  `password` varbinary(20) NOT NULL,
  `email` tinytext NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `level` smallint(6) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `our_u_users`
--

INSERT INTO `our_u_users` (`id`, `login`, `password`, `email`, `date`, `level`) VALUES
(1, 'radiofan', 0xaaf4c61ddcc5e8a2dabede0f3b482cd9aea9434d, 'radiofan22@mail.ru', '2021-06-30 12:35:52', 100);

-- --------------------------------------------------------

--
-- Структура таблицы `our_u_users_roles`
--

CREATE TABLE `our_u_users_roles` (
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `role_id` smallint(5) UNSIGNED NOT NULL,
  `start_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `end_time` timestamp NULL DEFAULT NULL,
  `work_time` varchar(255) NOT NULL DEFAULT 'INF' COMMENT 'sql time INTERVAL / "INF"',
  `action_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `parameters`
--

CREATE TABLE `parameters` (
  `id` bigint(20) NOT NULL,
  `key` tinytext NOT NULL,
  `value` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `parameters`
--

INSERT INTO `parameters` (`id`, `key`, `value`) VALUES
(1, 'update_interval_groups', 's:5:"1 DAY";'),
(2, 'update_interval_timetable', 's:5:"1 DAY";'),
(3, 'update_interval_first_week_day', 'i:30;'),
(4, 'first_week_day', 'O:8:"DateTime":3:{s:4:"date";s:26:"2020-09-14 00:00:00.000000";s:13:"timezone_type";i:3;s:8:"timezone";s:12:"Asia/Barnaul";}');

-- --------------------------------------------------------

--
-- Структура таблицы `stud_cabinets`
--

CREATE TABLE `stud_cabinets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `cabinet` varchar(10) NOT NULL,
  `additive` tinytext NOT NULL,
  `building` tinytext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `stud_faculties`
--

CREATE TABLE `stud_faculties` (
  `id` varchar(5) NOT NULL,
  `name` tinytext NOT NULL,
  `abbr` tinytext NOT NULL,
  `last_reload` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `stud_groups`
--

CREATE TABLE `stud_groups` (
  `id` varchar(30) NOT NULL,
  `name` tinytext NOT NULL,
  `faculty_id` varchar(5) NOT NULL,
  `last_reload` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `stud_teachers`
--

CREATE TABLE `stud_teachers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `fio` tinytext NOT NULL,
  `additive` tinytext NOT NULL,
  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `stud_timetable`
--

CREATE TABLE `stud_timetable` (
  `week` tinyint(4) NOT NULL,
  `day` tinyint(4) NOT NULL,
  `time` tinyint(4) NOT NULL,
  `group_id` varchar(30) NOT NULL,
  `lesson` tinytext NOT NULL,
  `lesson_type` tinytext NOT NULL,
  `cabinet_id` bigint(20) UNSIGNED NOT NULL,
  `teacher_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `our_u_options`
--
ALTER TABLE `our_u_options`
  ADD PRIMARY KEY (`user_id`,`key`),
  ADD KEY `user_id_idx` (`user_id`);

--
-- Индексы таблицы `our_u_roles`
--
ALTER TABLE `our_u_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role` (`role`);

--
-- Индексы таблицы `our_u_tokens`
--
ALTER TABLE `our_u_tokens`
  ADD PRIMARY KEY (`user_id`,`token`),
  ADD KEY `user_id_idx` (`user_id`);

--
-- Индексы таблицы `our_u_users`
--
ALTER TABLE `our_u_users`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `our_u_users_roles`
--
ALTER TABLE `our_u_users_roles`
  ADD KEY `user_id_idx` (`user_id`),
  ADD KEY `role_id_idx` (`role_id`);

--
-- Индексы таблицы `parameters`
--
ALTER TABLE `parameters`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `stud_cabinets`
--
ALTER TABLE `stud_cabinets`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `stud_faculties`
--
ALTER TABLE `stud_faculties`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `stud_groups`
--
ALTER TABLE `stud_groups`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `stud_teachers`
--
ALTER TABLE `stud_teachers`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `our_u_roles`
--
ALTER TABLE `our_u_roles`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT для таблицы `our_u_users`
--
ALTER TABLE `our_u_users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT для таблицы `parameters`
--
ALTER TABLE `parameters`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT для таблицы `stud_cabinets`
--
ALTER TABLE `stud_cabinets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=329;
--
-- AUTO_INCREMENT для таблицы `stud_teachers`
--
ALTER TABLE `stud_teachers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=654;
--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `our_u_options`
--
ALTER TABLE `our_u_options`
  ADD CONSTRAINT `user_id_key_opt` FOREIGN KEY (`user_id`) REFERENCES `our_u_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `our_u_tokens`
--
ALTER TABLE `our_u_tokens`
  ADD CONSTRAINT `user_id_key_tokens` FOREIGN KEY (`user_id`) REFERENCES `our_u_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `our_u_users_roles`
--
ALTER TABLE `our_u_users_roles`
  ADD CONSTRAINT `role_id_key` FOREIGN KEY (`role_id`) REFERENCES `our_u_roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `user_id_key` FOREIGN KEY (`user_id`) REFERENCES `our_u_users` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
