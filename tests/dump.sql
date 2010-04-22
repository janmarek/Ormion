SET NAMES utf8;
SET foreign_key_checks = 0;
SET time_zone = 'SYSTEM';
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `comments`;
CREATE TABLE `comments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `page` int(10) unsigned NOT NULL,
  `text` text COLLATE utf8_czech_ci NOT NULL,
  `name` varchar(50) COLLATE utf8_czech_ci NOT NULL,
  `mail` varchar(50) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `page` (`page`),
  CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`page`) REFERENCES `pages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `connections`;
CREATE TABLE `connections` (
  `tagId` int(10) unsigned NOT NULL,
  `pageId` int(10) unsigned NOT NULL,
  PRIMARY KEY (`tagId`,`pageId`),
  KEY `pageId` (`pageId`),
  CONSTRAINT `connections_ibfk_3` FOREIGN KEY (`tagId`) REFERENCES `tags` (`id`),
  CONSTRAINT `connections_ibfk_4` FOREIGN KEY (`pageId`) REFERENCES `pages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `pages`;
CREATE TABLE `pages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8_czech_ci NOT NULL,
  `description` tinytext COLLATE utf8_czech_ci,
  `text` text COLLATE utf8_czech_ci NOT NULL,
  `visits` int(10) unsigned NOT NULL,
  `created` datetime NOT NULL,
  `allowed` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `table`;
CREATE TABLE `table` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;


DROP TABLE IF EXISTS `tags`;
CREATE TABLE `tags` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8_czech_ci NOT NULL,
  `url` varchar(50) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;