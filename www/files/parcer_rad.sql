-- phpMyAdmin SQL Dump
-- version 4.6.4
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1
-- Время создания: Сен 06 2020 г., 10:35
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
-- Структура таблицы `our_users`
--

CREATE TABLE `our_users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `login` varchar(30) NOT NULL,
  `password` varbinary(20) NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `level` smallint(6) NOT NULL DEFAULT '1',
  `roles` text NOT NULL,
  `options` mediumtext NOT NULL
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
(2, 'update_interval_timetable', 's:5:"1 DAY";');

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
-- Индексы таблицы `our_users`
--
ALTER TABLE `our_users`
  ADD PRIMARY KEY (`id`);

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
-- AUTO_INCREMENT для таблицы `our_users`
--
ALTER TABLE `our_users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT для таблицы `parameters`
--
ALTER TABLE `parameters`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT для таблицы `stud_cabinets`
--
ALTER TABLE `stud_cabinets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=275;
--
-- AUTO_INCREMENT для таблицы `stud_teachers`
--
ALTER TABLE `stud_teachers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=598;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
