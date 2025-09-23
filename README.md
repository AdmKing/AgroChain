# AgroChain Backend
## Overview
This repository contains the backend of the AgroChain project, built with PHP following an MVC structure. Currently, the backend provides a user registration API. It handles:
- Receiving registration requests from a frontend or API client.
- Validating submitted user data.
- Hashing passwords securely using bcrypt.
- Storing new users in a MySQL database.
- Returning JSON responses indicating success, incomplete data, or duplicate email.

## Folder Structure
backend/
├── config/
│   └── db.php            # Database connection
├── controller/
│   └── UserController.php  # Handles user registration logic
├── data/                 # Any seed data or example data
├── helpers/              # Helper functions
├── models/
│   └── User.php          # User model
├── routes/
│   └── web.php           # Defines routes for the backend
└── index.php             # Entry point for backend requests

## Prerequisites

PHP >= 8.0
MySQL (XAMPP or other local server)
Postman (for testing APIs)

## Setup Instructions
1. Clone the repository
```
git clone <repository-url>
cd agrochain
```

2. Setup MySQL Database

Open phpMyAdmin (or any MySQL client).
Create a database, e.g., `myfirstdatabase.`
Create the `users` table:
```sql
CREATE TABLE users (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    firstname VARCHAR(50) NOT NULL,
    lastname VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    pwd VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

Update database credentials in backend/config/db.php if needed.

3. Start Local Server
- Start Apache and MySQL via XAMPP.
- Place the backend folder under your web root (e.g., C:\xampp\htdocs\AgroChain\backend).
- Access the backend via URL:
  http://localhost/AgroChain/backend/index.php?route=register

## API Endpoints
User Registration

URL: /backend/index.php?route=register
Method: POST
Content-Type: application/json or form-data

### Request Body:
```json
{
    "firstname": "John",
    "lastname": "Doe",
    "email": "john@example.com",
    "phone": "08012345678",
    "pwd": "securepassword"
}
```

### Response Examples:
Success:
```json
{
    "message": "User registered successfully"
}


Missing data:
{
    "message": "Incomplete data"
}


Duplicate email:
{
    "message": "Email already exists"
}
```

## Notes
- Passwords are hashed using bcrypt.
- The backend currently handles registration only. Login and other endpoints will be added later.
- The frontend interacts with the backend via HTTP requests (AJAX/fetch).

## Future Improvements
- Add login API.
- Add JWT authentication.
- Connect frontend forms to backend APIs.
- Expand database models and relationships.