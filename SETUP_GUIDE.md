# BloodConnect System Setup Guide

## 🚀 Complete Setup Instructions

Your BloodConnect system is ready to be fully functional! Follow these steps to get everything working.

## 📋 Prerequisites

1. **WAMP Server** (Windows, Apache, MySQL, PHP)
   - Download from: https://www.wampserver.com/
   - Install with default settings
   - Password: `14162121` (as configured)

2. **Web Browser** (Chrome, Firefox, Edge)

## 🔧 Setup Steps

### Step 1: Start WAMP Server

1. Start WAMP Server from Start Menu
2. Wait for the icon to turn green (all services running)
3. Click WAMP icon → MySQL → MySQL Console
4. Enter password: `14162121`

### Step 2: Create Database

1. Open phpMyAdmin: http://localhost/phpmyadmin
2. Login with:
   - Username: `root`
   - Password: `14162121`
3. Create new database named: `bloodconnect`
4. Import the database schema:
   - Click on `bloodconnect` database
   - Go to "Import" tab
   - Choose file: `backend/database/bloodconnect_database.sql`
   - Click "Go"

### Step 3: Place Files in WAMP Directory

1. Copy your entire project folder to: `C:\wamp64\www\bloodconnect\`
2. Your structure should be:
   ```
   C:\wamp64\www\bloodconnect\
   ├── backend/
   ├── frontend/
   ├── SETUP_GUIDE.md
   └── other files...
   ```

### Step 4: Test the System

1. **Test Database Connection:**
   - Visit: http://localhost/bloodconnect/backend/test_setup.php
   - Should show all green checkmarks

2. **Test Frontend:**
   - Visit: http://localhost/bloodconnect/frontend/index.html
   - Navigate through the site

## 🧪 Testing Your System

### 1. Admin Login Test
- Go to: http://localhost/bloodconnect/frontend/auth/login.html
- Click "Admin Login" button
- Should automatically log you in as admin

### 2. User Registration Test
- **Patient Registration:** http://localhost/bloodconnect/frontend/auth/register-patient.html
- **Donor Registration:** http://localhost/bloodconnect/frontend/auth/register-donor.html
- **Hospital Registration:** http://localhost/bloodconnect/frontend/auth/register-hospital.html

### 3. Dashboard Access Test
- After registration/login, you should be redirected to appropriate dashboard
- **Patient Dashboard:** http://localhost/bloodconnect/frontend/dashboard/patient.html
- **Donor Dashboard:** http://localhost/bloodconnect/frontend/dashboard/donor.html
- **Hospital Dashboard:** http://localhost/bloodconnect/frontend/dashboard/hospital.html
- **Admin Dashboard:** http://localhost/bloodconnect/frontend/dashboard/index.html

## 🎯 Key Features to Test

### ✅ Authentication System
- [x] User registration (all types)
- [x] User login with validation
- [x] Dashboard redirection based on user type
- [x] Session management
- [x] Logout functionality

### ✅ Patient Features
- [x] Blood availability search
- [x] Blood request submission
- [x] Request status tracking
- [x] Hospital contact information

### ✅ Donor Features
- [x] Donation offer submission
- [x] Donation history tracking
- [x] Eligibility checking
- [x] Hospital matching

### ✅ Hospital Features
- [x] Blood request management
- [x] Donation offer handling
- [x] Inventory management
- [x] Patient communication

### ✅ Admin Features
- [x] Hospital approval system
- [x] User management
- [x] System monitoring
- [x] Report generation

## 🔍 Troubleshooting

### Database Connection Issues
```
Error: Database connection failed
Solution: 
1. Check WAMP is running (green icon)
2. Verify MySQL password is 14162121
3. Ensure bloodconnect database exists
```

### API Not Working
```
Error: 404 Not Found on API calls
Solution:
1. Check files are in C:\wamp64\www\bloodconnect\
2. Verify Apache is running
3. Check file permissions
```

### Login Not Working
```
Error: Invalid credentials
Solution:
1. Use Admin Login button for testing
2. Check database has admin user
3. Verify API endpoints are accessible
```

## 📱 Mobile Testing

The system is fully responsive. Test on:
- Desktop browsers
- Mobile browsers
- Tablet browsers

## 🎉 Success Indicators

Your system is working when:

1. ✅ **Database Test Page** shows all green checkmarks
2. ✅ **Admin Login** works automatically
3. ✅ **User Registration** creates accounts successfully
4. ✅ **Dashboard Redirection** works for all user types
5. ✅ **Blood Search** returns hospital results
6. ✅ **Request Submission** saves to database
7. ✅ **Hospital Approval** workflow functions
8. ✅ **Real-time Updates** refresh dashboard data

## 🚀 Go Live Checklist

- [ ] WAMP Server running
- [ ] Database imported successfully
- [ ] All API endpoints responding
- [ ] Frontend pages loading
- [ ] User registration working
- [ ] Login system functional
- [ ] Dashboard protection active
- [ ] Blood request system working
- [ ] Hospital approval system working
- [ ] Mobile responsiveness tested

## 📞 Support

If you encounter issues:

1. Check the browser console for JavaScript errors
2. Check WAMP logs for PHP errors
3. Verify database connections in phpMyAdmin
4. Test API endpoints directly in browser

## 🎯 Next Steps

Once basic functionality is confirmed:

1. **Customize Design** - Update colors, logos, branding
2. **Add Features** - Implement additional functionality
3. **Security Review** - Add additional security measures
4. **Performance Optimization** - Optimize for production
5. **Backup System** - Set up regular database backups

Your BloodConnect system is now fully functional and ready for use!