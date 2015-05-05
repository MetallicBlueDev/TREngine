-- phpMyAdmin SQL Dump
-- version 4.1.4
-- http://www.phpmyadmin.net
--
-- Client :  127.0.0.1
-- Généré le :  Dim 18 Mai 2014 à 11:58
-- Version du serveur :  5.6.15-log
-- Version de PHP :  5.5.8

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Base de données :  `trancers_db`
--

-- --------------------------------------------------------

--
-- Structure de la table `tr_banned`
--

CREATE TABLE IF NOT EXISTS `tr_banned` (
  `ban_id` int(10) NOT NULL AUTO_INCREMENT,
  `ip` varchar(50) NOT NULL,
  `name` varchar(45) NOT NULL,
  `mail` varchar(80) NOT NULL,
  `reason` text NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `type` tinyint(1) NOT NULL,
  PRIMARY KEY (`ban_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- Structure de la table `tr_blocks`
--

CREATE TABLE IF NOT EXISTS `tr_blocks` (
  `block_id` int(10) NOT NULL AUTO_INCREMENT,
  `side` tinyint(1) unsigned NOT NULL,
  `position` tinyint(1) unsigned NOT NULL,
  `title` varchar(45) NOT NULL,
  `content` text NOT NULL,
  `type` varchar(45) NOT NULL,
  `rank` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `mods` text NOT NULL,
  PRIMARY KEY (`block_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=7 ;

-- --------------------------------------------------------

--
-- Structure de la table `tr_configs`
--

CREATE TABLE IF NOT EXISTS `tr_configs` (
  `name` varchar(45) NOT NULL,
  `value` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `tr_menus`
--

CREATE TABLE IF NOT EXISTS `tr_menus` (
  `menu_id` int(10) NOT NULL AUTO_INCREMENT,
  `block_id` int(10) NOT NULL,
  `parent_id` int(10) NOT NULL DEFAULT '0',
  `content` text NOT NULL,
  `sublevel` smallint(1) unsigned NOT NULL DEFAULT '0',
  `position` smallint(1) unsigned NOT NULL DEFAULT '0',
  `rank` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`menu_id`),
  KEY `block_id` (`block_id`),
  KEY `parent_id` (`parent_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=12 ;

-- --------------------------------------------------------

--
-- Structure de la table `tr_modules`
--

CREATE TABLE IF NOT EXISTS `tr_modules` (
  `mod_id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL,
  `rank` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `configs` text NOT NULL,
  `count` int(11) DEFAULT NULL,
  PRIMARY KEY (`mod_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=7 ;

-- --------------------------------------------------------

--
-- Structure de la table `tr_project`
--

CREATE TABLE IF NOT EXISTS `tr_project` (
  `project_id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `language` varchar(5) NOT NULL,
  `source_link` text NOT NULL,
  `binaire_link` text NOT NULL,
  `description` text NOT NULL,
  `img` text NOT NULL,
  `progress` tinyint(1) unsigned DEFAULT '0',
  `website` text NOT NULL,
  PRIMARY KEY (`project_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=18 ;

-- --------------------------------------------------------

--
-- Structure de la table `tr_users`
--

CREATE TABLE IF NOT EXISTS `tr_users` (
  `user_id` varchar(20) NOT NULL,
  `name` varchar(45) NOT NULL,
  `mail` varchar(80) NOT NULL,
  `pass` varchar(80) NOT NULL,
  `rank` tinyint(1) unsigned DEFAULT '0',
  `date` varchar(30) NOT NULL,
  `last_connect` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `avatar` varchar(100) NOT NULL,
  `website` varchar(100) NOT NULL,
  `signature` varchar(100) NOT NULL,
  `template` varchar(30) NOT NULL,
  `langue` varchar(30) NOT NULL,
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `tr_users_rights`
--

CREATE TABLE IF NOT EXISTS `tr_users_rights` (
  `right_id` int(10) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(20) NOT NULL,
  `name` varchar(45) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`right_id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
