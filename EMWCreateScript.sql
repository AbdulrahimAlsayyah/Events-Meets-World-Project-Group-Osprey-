CREATE TABLE IF NOT EXISTS CustomerAddress (
    CustomerAddressID BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    HouseNumber VARCHAR(5) NOT NULL,
    Street VARCHAR(25) NOT NULL,
    City VARCHAR(25) NOT NULL,
    PostCode VARCHAR(10) NOT NULL
);
 
CREATE TABLE IF NOT EXISTS Membership (
    MembershipID BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    Description VARCHAR(250) NOT NULL,
    MembershipStates ENUM('Inactive', 'Active', 'Cancelled') NOT NULL DEFAULT 'Inactive',
    StartDate DATE NULL DEFAULT CURDATE(),
    EndDate DATE NULL DEFAULT CURDATE()
);
 
CREATE TABLE IF NOT EXISTS Customer (
    CustomerID BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    CustomerAddressFK BIGINT UNSIGNED NOT NULL,
    MembershipFK BIGINT UNSIGNED NOT NULL,
    FirstName VARCHAR(255) NOT NULL,
    LastName VARCHAR(255) NOT NULL,
    Email VARCHAR(255) NOT NULL UNIQUE,
    Password VARCHAR(255) NOT NULL UNIQUE,
    RegistrationDate DATE DEFAULT CURDATE(),
    ContactNumber VARCHAR(15) NOT NULL,
    FOREIGN KEY (CustomerAddressFK) REFERENCES CustomerAddress(CustomerAddressID),
    FOREIGN KEY (MembershipFK) REFERENCES Membership(MembershipID)
);
 
CREATE TABLE IF NOT EXISTS VendorType (
    VendorTypeID BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    VendorType ENUM('Catering', 'Entertainment', 'Photography') NOT NULL
);
  
CREATE TABLE IF NOT EXISTS VendorLocation (
    VendorLocationID BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    VendorLocation ENUM('North London','East London','Central London','South London','West London', 'Maidstone', 'Rochester', 'Medway', 'Chelmsford', 'Colchester', 'Southend-on-Sea', 'Canterbury') NOT NULL,
    VendorStreetName VARCHAR(25) NOT NULL,
    VendorBuildingNumber VARCHAR(5) NOT NULL
);
  
CREATE TABLE IF NOT EXISTS VendorContact (
    VendorContactID BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ContactFirstName VARCHAR(50) NULL,
    ContactLastName VARCHAR(50) NULL,
    ContactNumber VARCHAR(20) NOT NULL,
    AlternativeNumber VARCHAR(20) NULL
);
 
CREATE TABLE IF NOT EXISTS Vendor (
    VendorID BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    VendorTypeFK BIGINT UNSIGNED NOT NULL,
    VendorLocationFK BIGINT UNSIGNED NOT NULL,
    VendorContactFK BIGINT UNSIGNED NOT NULL,
    VendorName VARCHAR(100) NOT NULL,
    VendorEmail VARCHAR(255) NOT NULL UNIQUE,
    VendorPassword VARCHAR(255) NOT NULL UNIQUE,
    Description TEXT NOT NULL,
    FOREIGN KEY (VendorTypeFK) REFERENCES VendorType(VendorTypeID),
    FOREIGN KEY (VendorLocationFK) REFERENCES VendorLocation(VendorLocationID),
    FOREIGN KEY (VendorContactFK) REFERENCES VendorContact(VendorContactID)
);
 
CREATE TABLE IF NOT EXISTS Review (
    ReviewID BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    CustomerFK BIGINT UNSIGNED NOT NULL,
    VendorFK BIGINT UNSIGNED NOT NULL,
    ReviewContent TEXT NULL,
    Rating INT NOT NULL CHECK (Rating >= 1 AND Rating <= 5),
    ReviewDate DATE DEFAULT CURDATE(),
    FOREIGN KEY (CustomerFK) REFERENCES Customer(CustomerID),
    FOREIGN KEY (VendorFK) REFERENCES Vendor(VendorID)
);
  
CREATE TABLE IF NOT EXISTS Refund (
    RefundID BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    RefundStatus TINYINT(1) NOT NULL,
    RefundAmount DOUBLE NULL,
    RefundDate DATE NULL
);
  
CREATE TABLE IF NOT EXISTS Payment (
    PaymentID BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    CustomerFK BIGINT UNSIGNED NOT NULL,
    RefundFK BIGINT UNSIGNED NULL,
    TotalPrice DOUBLE NOT NULL,
    TransactionAlerts VARCHAR(255) NULL,
    PaymentSuccessful BOOLEAN NOT NULL,
    TransactionDate DATE NOT NULL,
    FOREIGN KEY (CustomerFK) REFERENCES Customer(CustomerID),
    FOREIGN KEY (RefundFK) REFERENCES Refund(RefundID)
);
  
CREATE TABLE IF NOT EXISTS Event (
    EventID BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    PaymentFK BIGINT UNSIGNED NOT NULL,
    VendorFK BIGINT UNSIGNED NOT NULL,
    EventDate DATE NOT NULL,
    EventStates ENUM('Scheduled', 'Completed', 'Cancelled') NOT NULL DEFAULT 'Scheduled',
    EventType ENUM('Birthday Party', 'Wedding', 'Anniversary') NOT NULL,
    FOREIGN KEY (PaymentFK) REFERENCES Payment(PaymentID),
    FOREIGN KEY (VendorFK) REFERENCES Vendor(VendorID)
);
