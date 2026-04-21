-- MySQL Schema for Events Meets World Project

CREATE DATABASE IF NOT EXISTS `events_meets_world`;
USE `events_meets_world`;

-- Customer Table
CREATE TABLE IF NOT EXISTS `Customer` (
    `CustomerID` INT AUTO_INCREMENT,
    `CustomerName` VARCHAR(255) NOT NULL,
    `Password` VARCHAR(255) NOT NULL,  -- Password column added
    `Email` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`CustomerID`)
);

-- Vendor Table
CREATE TABLE IF NOT EXISTS `Vendor` (
    `VendorID` INT AUTO_INCREMENT,
    `VendorName` VARCHAR(255) NOT NULL,
    `VendorEmail` VARCHAR(255) NOT NULL, -- VendorEmail column added
    `VendorPassword` VARCHAR(255) NOT NULL,  -- VendorPassword column added
    PRIMARY KEY (`VendorID`)
);

-- Other tables and relationships can be defined below...
