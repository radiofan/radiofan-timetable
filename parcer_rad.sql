-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1:3306
-- Время создания: Май 25 2022 г., 22:57
-- Версия сервера: 5.7.33
-- Версия PHP: 7.1.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
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
-- Структура таблицы `log_events`
--

CREATE TABLE `log_events` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `type` tinyint(4) NOT NULL COMMENT '0-без типа; 1-парсинг',
  `message` text NOT NULL,
  `addition` mediumtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

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
(1, 'view_debug_info', 'видеть отладочную информацию', 101),
(2, 'edit_users', 'управлять пользователями', 100),
(3, 'edit_settings', 'управлять настройками сайта', 100),
(4, 'ignore_max_token_remember', 'Игнорирование ограничения по количеству запомненных устройств', 100),
(5, 'view_db_stat', 'Видеть статистику базы данных', 101);

-- --------------------------------------------------------

--
-- Структура таблицы `our_u_tokens`
--

CREATE TABLE `our_u_tokens` (
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `token` varbinary(32) NOT NULL,
  `user_agent` varbinary(20) NOT NULL,
  `time_start` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `time_end` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `time_work` tinytext NOT NULL COMMENT 'DateTimeInterval, время на которое будет обновляться токен если сессия активна'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `our_u_users`
--

CREATE TABLE `our_u_users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `login` varchar(30) NOT NULL,
  `password` varbinary(32) NOT NULL,
  `email` tinytext NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `level` smallint(6) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

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
(1, 'update_interval_groups', 's:5:\"1 DAY\";'),
(2, 'update_interval_timetable', 's:5:\"1 DAY\";'),
(3, 'update_interval_first_week_day', 'i:30;'),
(4, 'first_week_day', 'O:8:\"DateTime\":3:{s:4:\"date\";s:26:\"2020-09-14 00:00:00.000000\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:12:\"Asia/Barnaul\";}');

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

--
-- Дамп данных таблицы `stud_faculties`
--

INSERT INTO `stud_faculties` (`id`, `name`, `abbr`, `last_reload`) VALUES
('02', 'Факультет энергомашиностроения и автомобильного транспорта', 'ФЭАТ', '2021-09-01 03:31:53'),
('03', 'Строительно-технологический факультет', 'СТФ', '2021-09-01 03:31:58'),
('07', 'Энергетический факультет', 'ЭФ', '2021-09-01 03:32:01'),
('18', 'Заочный институт', '', '2021-09-01 03:31:34'),
('20', 'Университетский технологический колледж', '', '2021-09-01 03:31:34'),
('32', 'Институт архитектуры и дизайна', 'ИнАрхДиз', '2021-09-01 03:32:15'),
('42', 'Институт развития дополнительного профессионального образования', '', '2021-09-01 03:31:34'),
('70', 'Факультет информационных технологий', 'ФИТ', '2021-09-01 03:32:26'),
('72', 'Факультет специальных технологий', 'ФСТ', '2021-09-01 03:32:30'),
('73', 'Институт экономики и управления', '', '2021-09-01 03:31:34'),
('74', 'Институт биотехнологии, пищевой и химической инженерии', 'ИнБиоХим', '2021-09-01 03:32:41'),
('77', 'Аспирантура', '', '2021-09-01 03:31:34');

-- --------------------------------------------------------

--
-- Структура таблицы `stud_groups`
--

CREATE TABLE `stud_groups` (
  `id` varchar(30) NOT NULL,
  `name` tinytext NOT NULL,
  `faculty_id` varchar(5) NOT NULL,
  `last_reload` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` tinyint(4) NOT NULL DEFAULT '1',
  `data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `stud_lessons`
--

CREATE TABLE `stud_lessons` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `parse_text` text NOT NULL,
  `alias` text NOT NULL COMMENT 'если пусто то используется parse_text',
  `data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `stud_lesson_times`
--

CREATE TABLE `stud_lesson_times` (
  `id` int(10) UNSIGNED NOT NULL,
  `type` tinyint(4) NOT NULL,
  `time_start` mediumint(9) NOT NULL COMMENT 'количество секунд [0; 86400)',
  `time_end` mediumint(9) NOT NULL COMMENT 'количество секунд (0; 86400]'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `stud_lesson_times`
--

INSERT INTO `stud_lesson_times` (`id`, `type`, `time_start`, `time_end`) VALUES
(1, 1, 29700, 35100),
(2, 1, 35700, 41100),
(3, 1, 41700, 47100),
(4, 1, 48900, 54300),
(5, 1, 54900, 60300),
(6, 1, 60900, 66300),
(7, 1, 66900, 72300),
(8, 1, 72900, 78300);

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
  `date` date NOT NULL,
  `week` tinyint(4) NOT NULL,
  `time` tinyint(4) NOT NULL,
  `group_id` varchar(30) NOT NULL,
  `lesson_id` bigint(20) UNSIGNED NOT NULL,
  `lesson_type` tinytext NOT NULL,
  `cabinet_id` bigint(20) UNSIGNED DEFAULT NULL,
  `teacher_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `log_events`
--
ALTER TABLE `log_events`
  ADD PRIMARY KEY (`id`);

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
  ADD PRIMARY KEY (`id`),
  ADD KEY `faculty_id_idx` (`faculty_id`);

--
-- Индексы таблицы `stud_lessons`
--
ALTER TABLE `stud_lessons`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `stud_lesson_times`
--
ALTER TABLE `stud_lesson_times`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `stud_teachers`
--
ALTER TABLE `stud_teachers`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `stud_timetable`
--
ALTER TABLE `stud_timetable`
  ADD KEY `group_id_idx` (`group_id`),
  ADD KEY `lesson_id_idx` (`lesson_id`),
  ADD KEY `teacher_id_idx` (`teacher_id`),
  ADD KEY `cabinet_id_idx` (`cabinet_id`) USING BTREE;

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `log_events`
--
ALTER TABLE `log_events`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `our_u_roles`
--
ALTER TABLE `our_u_roles`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT для таблицы `our_u_users`
--
ALTER TABLE `our_u_users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `parameters`
--
ALTER TABLE `parameters`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT для таблицы `stud_cabinets`
--
ALTER TABLE `stud_cabinets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `stud_lessons`
--
ALTER TABLE `stud_lessons`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `stud_lesson_times`
--
ALTER TABLE `stud_lesson_times`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT для таблицы `stud_teachers`
--
ALTER TABLE `stud_teachers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

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
  ADD CONSTRAINT `user_id_key` FOREIGN KEY (`user_id`) REFERENCES `our_u_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `stud_groups`
--
ALTER TABLE `stud_groups`
  ADD CONSTRAINT `faculty_id_key` FOREIGN KEY (`faculty_id`) REFERENCES `stud_faculties` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `stud_timetable`
--
ALTER TABLE `stud_timetable`
  ADD CONSTRAINT `tmt_cabinet_id_key` FOREIGN KEY (`cabinet_id`) REFERENCES `stud_cabinets` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `tmt_group_id_key` FOREIGN KEY (`group_id`) REFERENCES `stud_groups` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `tmt_lesson_id_key` FOREIGN KEY (`lesson_id`) REFERENCES `stud_lessons` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `tmt_teacher_id_ley` FOREIGN KEY (`teacher_id`) REFERENCES `stud_teachers` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
