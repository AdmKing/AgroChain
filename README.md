# AgroChain

Backend system for AgroChain — an agricultural management platform.

Developed by **Adams Raphael Muzan**

## 📄 Project Description
This project is designed to streamline agricultural operations. It allows multiple user roles (farmers, admins, and procurement officers) to manage production plans, track production stages, log harvest/delivery statuses, and handle procurement operations.

## 👥 User Roles
- Admin – oversees the system, manages users, reviews production and procurement data.
- Farmer – submits production plans, tracks stages of crop/animal production, logs harvest and delivery status.
- Procurement Officer – manages procurement operations (to be added).

## ✅ Current Functionalities
- Authentication & Authorization with role-based access.
- Production Plan Submission by Farmers.
- Production Stage Tracking (Farmers/Admin).
- Harvest/Delivery Logging (Farmers).
- Admin can view production data per farmer.

## 🔜 Planned Functionalities
- Procurement operations for Procurement Officers.
- Enhanced analytics and dashboards.
- Notifications & reporting.

## 📁 Project Structure
agrochain/
│
├── backend/
│   ├── index.php                # Main entry point and router
│   ├── controllers/             # Controllers (ProductionController.php, AuthController.php, etc.)
│   ├── models/                  # Database logic
│   ├── config/                  # Database connection (db.php)
│
└── README.md                    # This file

## 🔗 API Endpoints (curent and proposed)
### Auth (Login & Roles)
Method: POST
Endpoint: /login
Description: Authenticate user and return token
		
### Production (Farmers)
1. Create Production
- **Method**:	POST
- **Endpoint**:	/create_production
- **Description**: Farmer submits production plan
		
2. Update Production stage/status   
- **Method**:	POST
- **Endpoint**:	/update_production_stage
- **Description**: Farmer logs production stage update

3. Log harvest delivery
- **Method**:	POST
- **Endpoint**: /log_harvest_delivery
- **Description**: Farmer logs harvest/delivery status

### Production (Admin)
1. View a speciific production
- **Method**:	GET
- **Endpoint**:	/get_production/{id}
- **Description**: Admin fetches details of a production plan by ID

2. View all productions	
- **Method**:	GET
- **Endpoint**:	/get_all_productions
- **Description**: Admin fetches all production plans		
