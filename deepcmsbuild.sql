-- phpMyAdmin SQL Dump
-- version 3.3.7deb7
-- http://www.phpmyadmin.net
--
-- Хост: localhost
-- Время создания: Ноя 06 2013 г., 00:39
-- Версия сервера: 5.1.66
-- Версия PHP: 5.3.3-7+squeeze15

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- База данных: `deepcmsbuild`
--

-- --------------------------------------------------------

--
-- Структура таблицы `features`
--

DROP TABLE IF EXISTS `features`;
CREATE TABLE IF NOT EXISTS `features` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` char(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

--
-- Дамп данных таблицы `features`
--


-- --------------------------------------------------------

--
-- Структура таблицы `groups`
--

DROP TABLE IF EXISTS `groups`;
CREATE TABLE IF NOT EXISTS `groups` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `priority` bigint(20) NOT NULL,
  `name` char(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- Дамп данных таблицы `groups`
--

INSERT INTO `groups` (`id`, `priority`, `name`) VALUES
(0, 0, 'root');

-- --------------------------------------------------------

--
-- Структура таблицы `group_permissions`
--

DROP TABLE IF EXISTS `group_permissions`;
CREATE TABLE IF NOT EXISTS `group_permissions` (
  `group_id` bigint(20) NOT NULL,
  `permission_id` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `group_permissions`
--

INSERT INTO `group_permissions` (`group_id`, `permission_id`) VALUES
(0, 1),
(0, 2),
(0, 3),
(0, 4),
(0, 5),
(0, 6),
(0, 7),
(0, 8),
(0, 9),
(0, 10),
(0, 11),
(0, 12),
(0, 13),
(0, 14),
(0, 15),
(0, 16),
(0, 17),
(0, 18),
(0, 19),
(0, 20),
(0, 21),
(0, 22);

-- --------------------------------------------------------

--
-- Структура таблицы `images`
--

DROP TABLE IF EXISTS `images`;
CREATE TABLE IF NOT EXISTS `images` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `node_id` bigint(20) NOT NULL,
  `is_master` tinyint(1) NOT NULL DEFAULT '0',
  `name` char(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `is_master` (`is_master`),
  KEY `node_id` (`node_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=9 ;

--
-- Дамп данных таблицы `images`
--


-- --------------------------------------------------------

--
-- Структура таблицы `menu`
--

DROP TABLE IF EXISTS `menu`;
CREATE TABLE IF NOT EXISTS `menu` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `parent_id` bigint(20) NOT NULL DEFAULT '0',
  `name` char(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

--
-- Дамп данных таблицы `menu`
--

INSERT INTO `menu` (`id`, `parent_id`, `name`) VALUES
(1, 0, 'Верхнее меню'),
(2, 0, 'Нижнее меню');

-- --------------------------------------------------------

--
-- Структура таблицы `menu_items`
--

DROP TABLE IF EXISTS `menu_items`;
CREATE TABLE IF NOT EXISTS `menu_items` (
  `menu_id` bigint(20) NOT NULL,
  `node_id` bigint(20) NOT NULL,
  KEY `menu_id` (`menu_id`),
  KEY `node_id` (`node_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `menu_items`
--

INSERT INTO `menu_items` (`menu_id`, `node_id`) VALUES
(2, 2);

-- --------------------------------------------------------

--
-- Структура таблицы `permissions`
--

DROP TABLE IF EXISTS `permissions`;
CREATE TABLE IF NOT EXISTS `permissions` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` char(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=23 ;

--
-- Дамп данных таблицы `permissions`
--

INSERT INTO `permissions` (`id`, `name`) VALUES
(1, 'admin_access'),
(2, 'events_view'),
(3, 'groups_manage'),
(4, 'group_create'),
(5, 'group_delete'),
(6, 'group_edit'),
(7, 'menu_manage'),
(8, 'menu_create'),
(9, 'menu_delete'),
(10, 'menu_edit'),
(11, 'documents_tree_manage'),
(12, 'preferences_manage'),
(13, 'preferences_recalc'),
(14, 'preferences_reset'),
(15, 'preferences_clear_cache'),
(16, 'node_create'),
(17, 'node_delete'),
(18, 'node_edit'),
(19, 'users_manage'),
(20, 'user_create'),
(21, 'user_delete'),
(22, 'user_edit');

-- --------------------------------------------------------

--
-- Структура таблицы `tree`
--

DROP TABLE IF EXISTS `tree`;
CREATE TABLE IF NOT EXISTS `tree` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `parent_id` bigint(20) NOT NULL,
  `lvl` tinyint(3) unsigned NOT NULL,
  `lk` bigint(20) unsigned NOT NULL,
  `rk` bigint(20) unsigned NOT NULL,
  `prototype` char(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `children_prototype` char(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `author` bigint(20) NOT NULL,
  `modified_author` bigint(20) NOT NULL,
  `last_modified` datetime NOT NULL,
  `creation_date` datetime NOT NULL,
  `is_publish` tinyint(1) NOT NULL,
  `node_name` char(255) NOT NULL,
  `in_sitemap` tinyint(1) NOT NULL,
  `layout` char(255) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `page_alias` char(255) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `permanent_redirect` char(255) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `change_freq` char(7) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `searchers_priority` double DEFAULT NULL,
  `page_title` mediumtext,
  `page_h1` mediumtext,
  `meta_keywords` mediumtext,
  `meta_description` mediumtext,
  `page_text` longtext,
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`),
  KEY `lvl` (`lvl`),
  KEY `lk` (`lk`),
  KEY `rk` (`rk`),
  KEY `author` (`author`),
  KEY `modified_author` (`modified_author`),
  KEY `page_alias` (`page_alias`),
  KEY `prototype` (`prototype`),
  KEY `is_publish` (`is_publish`),
  KEY `in_sitemap` (`in_sitemap`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=29 ;

--
-- Дамп данных таблицы `tree`
--

INSERT INTO `tree` (`id`, `parent_id`, `lvl`, `lk`, `rk`, `prototype`, `children_prototype`, `author`, `modified_author`, `last_modified`, `creation_date`, `is_publish`, `node_name`, `in_sitemap`, `layout`, `page_alias`, `permanent_redirect`, `change_freq`, `searchers_priority`, `page_title`, `page_h1`, `meta_keywords`, `meta_description`, `page_text`) VALUES
(26, 0, 1, 1, 6, 'simplePage', 'simplePage', 0, 0, '2013-11-05 07:50:09', '2013-11-05 07:50:09', 1, '1', 0, 'page.html', '/1', '', NULL, NULL, '', '', '', '', ''),
(27, 26, 2, 2, 5, 'simplePage', 'simplePage', 0, 0, '2013-11-05 07:52:40', '2013-11-05 07:52:40', 1, '2rerrtопм', 0, 'page.html', '/1/2rerrt%D0%BE%D0%BF%D0%BC', '', NULL, NULL, '', '', '', '', ''),
(28, 27, 3, 3, 4, 'simplePage', 'simplePage', 0, 0, '2013-11-05 07:53:25', '2013-11-05 07:53:00', 1, '3', 0, 'simple-page.html', '/1/2rerrt%D0%BE%D0%BF%D0%BC/3', '', NULL, NULL, '', '', '', '', '');

-- --------------------------------------------------------

--
-- Структура таблицы `tree_features`
--

DROP TABLE IF EXISTS `tree_features`;
CREATE TABLE IF NOT EXISTS `tree_features` (
  `node_id` bigint(20) NOT NULL,
  `feature_id` bigint(20) NOT NULL,
  `feature_value` mediumtext NOT NULL,
  KEY `feature_id` (`feature_id`),
  KEY `node_id` (`node_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `tree_features`
--


-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `group_id` bigint(20) DEFAULT NULL,
  `status` int(1) NOT NULL DEFAULT '0',
  `language` char(16) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `timezone` char(8) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `avatar` char(128) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `login` char(128) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `password` char(128) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `email` char(255) NOT NULL,
  `hash` char(128) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `last_ip` char(15) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '0.0.0.0',
  `registration_date` datetime NOT NULL,
  `last_visit` datetime NOT NULL,
  `about` text,
  `working_cache` longtext CHARACTER SET utf8 COLLATE utf8_bin,
  PRIMARY KEY (`id`),
  KEY `group_id` (`group_id`),
  KEY `status` (`status`),
  KEY `hash` (`hash`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `group_id`, `status`, `language`, `timezone`, `avatar`, `login`, `password`, `email`, `hash`, `last_ip`, `registration_date`, `last_visit`, `about`, `working_cache`) VALUES
(0, 0, 0, NULL, NULL, NULL, 'root', 'c56d0e9a7ccec67b4ea131655038d604', 'support@deep-cms.ru', 'aed2e0fe6319b3f9ae8ac9711058fd42', '127.0.0.1', '2013-11-01 07:32:17', '2013-11-06 00:36:55', '', '{"__stored_images":[],"__stored_features":[]}');
