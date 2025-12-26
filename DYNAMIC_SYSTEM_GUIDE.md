# BloodConnect Dynamic System Guide

## 🚀 System Overview

The BloodConnect system is now fully dynamic with real backend APIs and database integration. All features are functional including:

- ✅ **User Registration & Login** (Patients, Donors, Hospitals)
- ✅ **Hospital Admin Approval System**
- ✅ **Blood Request Management**
- ✅ **Donation Offer System**
- ✅ **Real-time Dashboard Updates**
- ✅ **Blood Inventory Management**

## 📋 Setup Instructions

### 1. Database Setup

1. **Import Database Schema**
   ```sql
   -- In phpMyAdmin or MySQL command line
   CREATE DATABASE bloodconnect;
   USE bloodconnect;
   -- Import: backend/database/bloodconnect_database.sql
   ```

2. **Verify Database Configuration**
   ```php
   // backend/config/database.php
   DB_HOST: localhost
   DB_NAME: bloodconnect
   DB_USER: root
   DB_PASS: 14162121
   ```

3. **Test Database Connection**
   ```
   http://localhost/bloodconnect/backend/test_setup.php
   ```

### 2. File Structure Verification

Ensure these new dynamic files are in place:
```
backend/api/
├── admin/
│   ├── approve_hospital.php
│   └── get_pending_hospitals.php
├── blood/
│   ├── search_availability.php
│   └── submit_request.php
├── donation/
│   └── submit_offer.php
├── hospital/
│   ├── approve_request.php
│   ├── get_nearby.php
│   └── get_requests.php
├── patient/
│   └── get_requests.php
└── donor/
    └── get_dashboard.php

frontend/js/
├── patient-dashboard-new.js
├── hospital-dashboard-dynamic.js
└── admin-dashboard-dynamic.js
```

## 🧪 Testing Workflow

### Phase 1: User Registration & Authentication

1. **Register as Patient**
   - Visit: `frontend/auth/register-patient.html`
   - Fill form with valid data
   - Check database: `users` and `patients` tables
   - Verify email confirmation

2. **Register as Donor**
   - Visit: `frontend/auth/register-donor.html`
   - Fill form with valid data
   - Check database: `users` and `donors` tables

3. **Register as Hospital**
   - Visit: `frontend/auth/register-hospital.html`
   - Fill form with valid data
   - Check database: `users` and `hospitals` tables
   - Note: Hospital will be unverified initially

4. **Login Testing**
   - Test login for each user type
   - Verify dashboard redirection
   - Check session storage

### Phase 2: Admin Hospital Approval

1. **Admin Login**
   - Email: `admin@bloodconnect.com`
   - Password: `admin123`
   - Or use "Admin Login" button

2. **Approve Hospital**
   - Go to admin dashboard
   - See pending hospital registrations
   - Click "Approve" on hospital
   - Verify hospital gets notification
   - Check hospital can now login

### Phase 3: Blood Request System

1. **Patient Blood Request**
   - Login as patient
   - Search for blood availability
   - Select hospital from results
   - Submit blood request
   - Check database: `blood_requests` table

2. **Hospital Request Management**
   - Login as approved hospital
   - See incoming blood requests
   - Approve or reject requests
   - Verify patient gets notification

### Phase 4: Donation System

1. **Donor Offer**
   - Login as donor
   - Submit donation offer
   - Select hospital and time
   - Check database: `donation_offers` table

2. **Hospital Donation Management**
   - Login as hospital
   - See donation offers
   - Accept or reject offers
   - Schedule appointments

## 🔧 API Endpoints Reference

### Authentication APIs
- `POST /api/auth/register.php` - User registration
- `POST /api/auth/login.php` - User login

### Patient APIs
- `GET /api/patient/get_requests.php` - Get patient's blood requests
- `GET /api/blood/search_availability.php` - Search blood availability
- `POST /api/blood/submit_request.php` - Submit blood request

### Hospital APIs
- `GET /api/hospital/get_requests.php` - Get hospital requests & donations
- `GET /api/hospital/get_nearby.php` - Get nearby hospitals
- `POST /api/hospital/approve_request.php` - Approve/reject blood requests

### Donor APIs
- `GET /api/donor/get_dashboard.php` - Get donor dashboard data
- `POST /api/donation/submit_offer.php` - Submit donation offer

### Admin APIs
- `GET /api/admin/get_pending_hospitals.php` - Get pending hospital registrations
- `POST /api/admin/approve_hospital.php` - Approve/reject hospital registration

## 🎯 Key Features Implemented

### 1. Dynamic Blood Search
- Real-time hospital availability
- Filter by blood type, location, radius
- Shows only verified hospitals
- Stock status indicators

### 2. Request Management System
- Patient submits requests
- Hospital receives notifications
- Approval/rejection workflow
- Status tracking and progress

### 3. Hospital Approval Workflow
- Hospitals register and wait for approval
- Admin reviews and approves/rejects
- Automatic notifications sent
- Access control based on approval status

### 4. Donation Management
- Donors can offer blood donations
- Hospitals can accept/reject offers
- Appointment scheduling
- Donation completion tracking

### 5. Real-time Updates
- Dashboard data refreshes automatically
- Notifications for status changes
- Live inventory updates
- Progress tracking

## 🔍 Troubleshooting

### Common Issues

1. **Database Connection Failed**
   ```
   Solution: Check WAMP is running, verify password 14162121
   ```

2. **API Returns 401 Unauthorized**
   ```
   Solution: Check user is logged in, token is valid
   ```

3. **Hospital Can't Login After Registration**
   ```
   Solution: Hospital needs admin approval first
   ```

4. **Blood Search Returns No Results**
   ```
   Solution: Check hospitals are verified and have inventory
   ```

### Debug Tools

1. **Backend Testing**
   ```
   http://localhost/bloodconnect/backend/test_setup.php
   ```

2. **Browser Console**
   - Check for JavaScript errors
   - Monitor API calls in Network tab
   - Inspect localStorage for session data

3. **Database Verification**
   - Check phpMyAdmin for data
   - Verify table relationships
   - Check user permissions

## 📊 Database Schema Summary

### Core Tables
- `users` - All user accounts
- `patients` - Patient profiles
- `donors` - Donor profiles  
- `hospitals` - Hospital profiles

### Transaction Tables
- `blood_requests` - Patient blood requests
- `donation_offers` - Donor offers
- `blood_inventory` - Hospital stock levels
- `notifications` - System notifications

### Audit Tables
- `activity_logs` - System activity tracking
- `donation_history` - Completed donations

## 🎉 Success Indicators

### System is Working When:
- ✅ Users can register and login
- ✅ Hospitals get approved by admin
- ✅ Patients can search and request blood
- ✅ Hospitals can manage requests
- ✅ Donors can offer donations
- ✅ Real-time updates work
- ✅ Notifications are sent
- ✅ Data persists in database

## 🚀 Next Steps

1. **Test all user workflows**
2. **Verify data persistence**
3. **Check notification system**
4. **Test error handling**
5. **Validate security measures**
6. **Performance optimization**

The system is now fully functional with real backend integration!