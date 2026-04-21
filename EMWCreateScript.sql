-- Script to create and populate tables for Events Meets World Project 

CREATE TABLE IF NOT EXISTS Customer (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(255) NOT NULL,
    Email VARCHAR(255) NOT NULL UNIQUE,
    Password VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS Vendor (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    CompanyName VARCHAR(255) NOT NULL,
    VendorEmail VARCHAR(255) NOT NULL,
    VendorPassword VARCHAR(255) NOT NULL
);

-- Other original SQL comments and code would go here.

