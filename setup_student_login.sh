#!/bin/bash
# Quick setup and test script for student login

echo "=== EduAttend Student Login - Quick Setup ==="
echo ""

# Step 1: Initialize database
echo "Step 1: Setting up SQLite database..."
cd "$(dirname "$0")"
php scratch/setup_local.php
echo ""

# Step 2: Check database
echo "Step 2: Verifying database..."
php scratch/check_db.php
echo ""

# Step 3: Test login process
echo "Step 3: Testing login logic..."
php scratch/test_login_process.php
echo ""

echo "=== Setup Complete ==="
echo ""
echo "Access student login at: http://localhost:8000/pages/student/login.php"
echo "Test credentials:"
echo "  Email: alex@school.edu"
echo "  Password: password123"
echo ""
echo "Logs: Check your PHP error log for debugging (php.ini error_log setting)"
