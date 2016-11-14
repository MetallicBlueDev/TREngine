-- phpMyAdmin SQL Dump
-- version 4.5.5.1
-- http://www.phpmyadmin.net
--
-- Client :  127.0.0.1
-- Généré le :  Lun 14 Novembre 2016 à 16:50
-- Version du serveur :  5.7.11
-- Version de PHP :  7.0.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données :  `trancers_db`
--

-- --------------------------------------------------------

--
-- Structure de la table `tr_banned`
--

CREATE TABLE IF NOT EXISTS `tr_banned` (
  `ban_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip` varchar(50) NOT NULL,
  `name` varchar(45) NOT NULL,
  `mail` varchar(80) DEFAULT NULL,
  `reason` text NOT NULL,
  `banishment_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `type` tinyint(1) UNSIGNED NOT NULL,
  PRIMARY KEY (`ban_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `tr_blocks`
--

CREATE TABLE IF NOT EXISTS `tr_blocks` (
  `block_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `side` tinyint(1) UNSIGNED NOT NULL,
  `position` tinyint(2) UNSIGNED NOT NULL,
  `title` varchar(45) NOT NULL,
  `content` text,
  `type` varchar(45) NOT NULL,
  `rank` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `mods` text NOT NULL,
  PRIMARY KEY (`block_id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `tr_configs`
--

CREATE TABLE IF NOT EXISTS `tr_configs` (
  `config_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`config_id`)
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `tr_menus`
--

CREATE TABLE IF NOT EXISTS `tr_menus` (
  `menu_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `block_id` int(10) UNSIGNED NOT NULL,
  `parent_id` int(10) UNSIGNED DEFAULT NULL,
  `content` text,
  `sublevel` smallint(10) UNSIGNED NOT NULL DEFAULT '0',
  `position` smallint(10) UNSIGNED NOT NULL DEFAULT '0',
  `rank` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`menu_id`)
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `tr_modules`
--

CREATE TABLE IF NOT EXISTS `tr_modules` (
  `mod_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL,
  `rank` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `configs` text,
  `count` int(11) DEFAULT NULL,
  PRIMARY KEY (`mod_id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `tr_project`
--

CREATE TABLE IF NOT EXISTS `tr_project` (
  `projectid` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `language` varchar(5) NOT NULL,
  `sourcelink` text NOT NULL,
  `binairelink` text NOT NULL,
  `description` text NOT NULL,
  `img` text NOT NULL,
  `progress` tinyint(1) NOT NULL,
  `website` text NOT NULL,
  PRIMARY KEY (`projectid`)
) ENGINE=MyISAM AUTO_INCREMENT=18 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `tr_users`
--

CREATE TABLE IF NOT EXISTS `tr_users` (
  `user_id` varchar(20) NOT NULL,
  `name` varchar(45) NOT NULL,
  `mail` varchar(80) NOT NULL,
  `pass` varchar(80) NOT NULL,
  `rank` tinyint(1) UNSIGNED DEFAULT NULL,
  `registration_date` datetime NOT NULL,
  `last_connect` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `avatar` varchar(100) DEFAULT NULL,
  `website` varchar(100) DEFAULT NULL,
  `signature` varchar(100) DEFAULT NULL,
  `template` varchar(30) DEFAULT NULL,
  `langue` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `tr_users_rights`
--

CREATE TABLE IF NOT EXISTS `tr_users_rights` (
  `right_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` varchar(20) NOT NULL,
  `zone` varchar(10) DEFAULT NULL,
  `page` varchar(10) DEFAULT NULL,
  `identifiant` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`right_id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
