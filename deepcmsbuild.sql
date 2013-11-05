-- phpMyAdmin SQL Dump
-- version 3.3.7deb7
-- http://www.phpmyadmin.net
--
-- Хост: localhost
-- Время создания: Ноя 05 2013 г., 05:06
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
-- Структура таблицы `documents`
--

DROP TABLE IF EXISTS `documents`;
CREATE TABLE IF NOT EXISTS `documents` (
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
  `search_priority` double DEFAULT NULL,
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
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=18 ;

--
-- Дамп данных таблицы `documents`
--

INSERT INTO `documents` (`id`, `parent_id`, `lvl`, `lk`, `rk`, `prototype`, `children_prototype`, `author`, `modified_author`, `last_modified`, `creation_date`, `is_publish`, `node_name`, `in_sitemap`, `layout`, `page_alias`, `permanent_redirect`, `change_freq`, `search_priority`, `page_title`, `page_h1`, `meta_keywords`, `meta_description`, `page_text`) VALUES
(1, 0, 1, 1, 6, 'simplePage', 'simplePage', 0, 0, '2013-07-08 00:32:46', '2013-07-07 23:38:04', 1, 'Главная страница', 1, 'simple-page.html', '/', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '\r\n                <p>\r\n                    С определенной поры телеэфир в России\r\n                    стали заполнять зарубежные фильмы\r\n                    с весьма привычным теперь сюжетом – главный герой,\r\n                    не обязательно сильный и ловкий,\r\n                    но чаще всего именно такой, попадает в передрягу,\r\n                    погоня, стрельба, разбитые машины,\r\n                    полиция и... много погибших.\r\n                    Герой, как правило, выживает.\r\n                </p>\r\n\r\n                <p>\r\n                    Или еще интересное развитие событий –\r\n                    несколько незнакомых людей в одном помещении.\r\n                    Необходимо испытать все муки ада,\r\n                    чтобы выбраться из лап очередного психопата.\r\n                </p>\r\n\r\n                <h2>h2 заголовок</h2>\r\n\r\n                <p>\r\n                    К чему, собственно, всё это?\r\n                </p>\r\n\r\n                <p>\r\n                    Смерть в кино <a href="#">стала для нас настолько</a> привычной,\r\n                    что мы только сопереживаем одному или нескольким героям.\r\n                    В то же время нам совершенно не жалко “плохих парней” -\r\n                    они же плохие, пусть умирают.\r\n                </p>\r\n\r\n                <p>\r\n                    Мы никогда не думаем о том,\r\n                    что же ощущает герой или анти-герой,\r\n                    когда его пытают, жгут, режут, бьют.\r\n                    Мы смотрим на это спокойно или почти спокойно.\r\n                    Особо нервные или брезгливые отворачиваются,\r\n                    но чаще по причине отвращения, а не сопереживания.\r\n                    Между тем каждый человек испытывает, к примеру,\r\n                    головную боль или крайне неприятное ощущение от мозоли,\r\n                    появившейся в результате ношения новой обуви –\r\n                    вроде бы и не страшно, а жизнь портит.\r\n                </p>\r\n\r\n                <h2>Еще один h2 заголовок</h2>\r\n\r\n                <p>\r\n                    Царствующая в кино смерть обесценивает боль окружающих\r\n                    в реальной жизни, умаляет понимание ценности\r\n                    каждого живущего человека с его проблемами,\r\n                    мировоззрением и характером.\r\n                </p>\r\n\r\n                <p>\r\n                    Царствующая в кино смерть, как ни банально,\r\n                    убивает в человеке доброту, отзывчивость и милосердие.\r\n                    И в первую очередь, влияет на детей и подростков.\r\n                </p>\r\n\r\n                <h3>h3 заголовок</h3>\r\n\r\n                <p>\r\n                    И уж точно неправда утверждение о том,\r\n                    что просмотр жестоких фильмов помогают\r\n                    выплеснуть негатив от повседневной жизни.\r\n                    Просмотр жестоких фильмов вдобавок\r\n                    к негативному воздействию на психику\r\n                    помогает отложить лишние жирки в запас к имеющимся\r\n                    (гормон стресса вместе с приемом пищи\r\n                    при просмотре творят ненужные чудеса).\r\n                    От негатива и жирков помогает избавиться\r\n                    прогулка на свежем воздухе, мытье посуды, занятия спортом.\r\n                    А если подключить к этому семью и друзей,\r\n                    то еще и улучшение коммуникативных способностей\r\n                    да повышение уровня взаимопонимания в подарок!\r\n                </p>\r\n\r\n                <ul>\r\n                    <li>Просмотр жестоких фильмов</li>\r\n                    <li>Царствующая в кино смерть</li>\r\n                    <li>От негатива и жирков помогает</li>\r\n                    <li>И уж точно неправда утверждение</li>\r\n                    <li>А если подключить к этому семью и друзей</li>\r\n                </ul>\r\n\r\n                <p>\r\n                    Вывод-то весьма прост: не зомбироваться телевизором,\r\n                    не смотреть дрянь и не тратить на это время.\r\n                    Вместо гадости – спорт. И жизнь станет лучше. Или нет?\r\n                </p>\r\n\r\n'),
(15, 1, 2, 2, 5, 'simplePage', 'simplePage', 0, 0, '2013-11-05 03:17:44', '2013-11-05 03:17:44', 1, 'sdfg sdfg sdfg', 0, 'page.html', '/sdfg-sdfg-sdfg', '', NULL, NULL, '', '', '', '', '<p>sdfgsdfgdsf g</p>'),
(16, 15, 3, 3, 4, 'simplePage', 'simplePage', 0, 0, '2013-11-05 04:35:39', '2013-11-05 03:32:49', 1, 'Привет «Васечка»!!!', 0, 'page.html', '/%D0%9F%D1%80%D0%B8%D0%B2%D0%B5%D1%82-%D0%92%D0%B0%D1%81%D0%B5%D1%87%D0%BA%D0%B0%21%21%21', '', 'never', 0.2, '', '', '', '', '<p>Хау дук ма&nbsp;й&nbsp;дук!</p>');

-- --------------------------------------------------------

--
-- Структура таблицы `document_features`
--

DROP TABLE IF EXISTS `document_features`;
CREATE TABLE IF NOT EXISTS `document_features` (
  `document_id` bigint(20) NOT NULL,
  `feature_id` bigint(20) NOT NULL,
  `feature_value` mediumtext NOT NULL,
  KEY `document_id` (`document_id`),
  KEY `feature_id` (`feature_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `document_features`
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
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

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
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

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
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

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
(0, 21);

-- --------------------------------------------------------

--
-- Структура таблицы `images`
--

DROP TABLE IF EXISTS `images`;
CREATE TABLE IF NOT EXISTS `images` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `document_id` bigint(20) NOT NULL,
  `is_master` tinyint(1) NOT NULL DEFAULT '0',
  `name` char(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `document_id` (`document_id`),
  KEY `is_master` (`is_master`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5 ;

--
-- Дамп данных таблицы `images`
--

INSERT INTO `images` (`id`, `document_id`, `is_master`, `name`) VALUES
(3, 1, 1, '0e8db76b87ddc7ebf5df9ad191ce2d34.jpg');

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
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

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
  `document_id` bigint(20) NOT NULL,
  KEY `menu_id` (`menu_id`),
  KEY `document_id` (`document_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `menu_items`
--

INSERT INTO `menu_items` (`menu_id`, `document_id`) VALUES
(2, 1),
(1, 1),
(2, 2),
(1, 16);

-- --------------------------------------------------------

--
-- Структура таблицы `permissions`
--

DROP TABLE IF EXISTS `permissions`;
CREATE TABLE IF NOT EXISTS `permissions` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` char(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=22 ;

--
-- Дамп данных таблицы `permissions`
--

INSERT INTO `permissions` (`id`, `name`) VALUES
(1, 'admin_access'),
(2, 'documents_manage'),
(3, 'documents_create'),
(4, 'documents_delete'),
(5, 'documents_edit'),
(6, 'events_view'),
(7, 'groups_manage'),
(8, 'groups_create'),
(9, 'groups_delete'),
(10, 'groups_edit'),
(11, 'menu_manage'),
(12, 'menu_create'),
(13, 'menu_delete'),
(14, 'menu_edit'),
(15, 'preferences_manage'),
(16, 'preferences_recalc'),
(17, 'preferences_reset'),
(18, 'users_manage'),
(19, 'users_create'),
(20, 'users_delete'),
(21, 'users_edit');

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
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `group_id`, `status`, `language`, `timezone`, `avatar`, `login`, `password`, `email`, `hash`, `last_ip`, `registration_date`, `last_visit`, `about`, `working_cache`) VALUES
(0, 0, 0, NULL, NULL, NULL, 'root', 'c56d0e9a7ccec67b4ea131655038d604', 'support@deep-cms.ru', 'aed2e0fe6319b3f9ae8ac9711058fd42', '127.0.0.1', '2013-11-01 07:32:17', '2013-11-05 05:00:39', '', '{"__stored_images":[],"__stored_features":[]}');
