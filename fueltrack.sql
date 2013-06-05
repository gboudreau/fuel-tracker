-- phpMyAdmin SQL Dump
-- version 3.5.0
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jun 05, 2013 at 10:30 AM
-- Server version: 5.5.28-log
-- PHP Version: 5.3.25

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `fueltrack`
--

-- --------------------------------------------------------

--
-- Table structure for table `cars`
--

CREATE TABLE IF NOT EXISTS `cars` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `make` tinytext NOT NULL,
  `model` tinytext NOT NULL,
  `year` tinytext NOT NULL,
  `owner` tinytext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `data`
--

CREATE TABLE IF NOT EXISTS `data` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `date` date DEFAULT NULL,
  `car_id` mediumint(8) unsigned NOT NULL,
  `liters` float(7,3) NOT NULL,
  `price_per_liter` float(5,3) NOT NULL,
  `distance_since_last_entry` float(6,1) NOT NULL,
  `fuel_consumption` float(6,3) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 PACK_KEYS=1;

--
-- Triggers `data`
--
DROP TRIGGER IF EXISTS `fueltrack_data_fuel_consumption`;
DELIMITER //
CREATE TRIGGER `fueltrack_data_fuel_consumption` BEFORE INSERT ON `data`
 FOR EACH ROW set new.fuel_consumption = new.liters / (new.distance_since_last_entry/100.0)
//
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `next_service`
--

CREATE TABLE IF NOT EXISTS `next_service` (
  `car_id` mediumint(8) unsigned NOT NULL,
  `km` mediumint(8) unsigned NOT NULL,
  PRIMARY KEY (`car_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
