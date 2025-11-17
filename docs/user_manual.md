# MIS Barangay - User Manual
## Basic User Guide

**Version:** 1.3.0  
**Last Updated:** 2024-01-16

---

## Table of Contents

1. [Getting Started](#getting-started)
2. [User Roles](#user-roles)
3. [Dashboard](#dashboard)
4. [Resident Management](#resident-management)
5. [Certificate Management](#certificate-management)
6. [Blotter System](#blotter-system)
7. [Officers Management](#officers-management)
8. [Account Management](#account-management)
9. [Settings](#settings)
10. [Troubleshooting](#troubleshooting)

---

## Getting Started

### Logging In

1. Open your web browser and navigate to the MIS Barangay system URL
2. Enter your **Username** and **Password**
3. Click the **Login** button
4. You will be redirected to your dashboard based on your role

### First Time Login

If this is your first time logging in, contact your system administrator to:
- Set up your account
- Assign your role and permissions
- Provide your initial password

---

## User Roles

The system has three main user roles:

### 1. Admin
- **Full Access:** Can access all features
- **Account Management:** Create, edit, and manage all user accounts
- **System Configuration:** Access to all modules

### 2. Staff
- **Resident Management:** Add, edit, and view residents
- **Certificate Management:** Create and manage certificate requests
- **Dashboard Access:** View population statistics and reports
- **Cannot Access:** Blotter system, account management

### 3. Tanod
- **Blotter Management:** Create, view, and manage blotter cases
- **Dashboard Access:** View blotter statistics (pending, under investigation, resolved, dismissed)
- **Cannot Access:** Resident management, certificate management, account management

---

## Dashboard

The dashboard provides an overview of key statistics based on your role.

### Staff/Admin Dashboard

**Population Report Cards:**
- **Total Population:** Total number of registered residents
- **Male/Female:** Gender distribution
- **Seniors:** Residents aged 60 and above
- **PWDs:** Persons with disabilities
- **Registered Voters:** Residents registered to vote
- **Unregistered Voters:** Residents not registered to vote

**Features:**
- Click on any card to view detailed filtered data
- Data is displayed in a searchable, sortable table
- Export options available

### Tanod Dashboard

**Blotter Statistics:**
- **Pending Cases:** Cases awaiting initial review
- **Under Investigation:** Cases currently being investigated
- **Resolved:** Cases that have been resolved
- **Dismissed:** Cases that have been dismissed

**Features:**
- Click on any card to view detailed case list
- Filter and search cases by status

---

## Resident Management

### Viewing Residents

1. Navigate to **Residents** from the sidebar
2. View the list of all registered residents
3. Use the search box to find specific residents
4. Click on a resident's name to view/edit details

### Adding a New Resident

1. Click the **➕ Add Resident** button
2. Fill in the required fields:
   - **First Name** (required)
   - **Last Name** (required)
   - **Gender** (required)
   - **Birthdate** (required)
3. Fill in optional fields as needed
4. Click **Save** to add the resident

### Editing a Resident

1. Click on a resident's name from the list
2. Make your changes
3. Click **Save** to update

### Viewing Resident Details

1. Click on a resident's name from the list
2. View all resident information
3. Use the **← Back to Residents List** button to return

---

## Certificate Management

### Creating a Certificate Request

1. Navigate to **Certificates** from the sidebar
2. Click **Request Certificate**
3. Search and select the resident
4. Choose the **Certificate Type**
5. Enter the **Purpose**
6. Click **Submit Request**

### Certificate Types

Common certificate types include:
- Barangay Clearance
- Certificate of Residency
- Certificate of Indigency
- Business Permit Certificate

### Viewing Certificate History

1. Navigate to **Certificates** from the sidebar
2. View the **History** table
3. Filter by status: Pending, Printed, Completed
4. Click **Print** to generate certificate

### Printing Certificates

1. Find the certificate in the history table
2. Click the **Print** button
3. Adjust print settings if needed:
   - Scale (to fit A4 paper)
   - Margins
4. Print or save as PDF

---

## Blotter System

> **Note:** Only Tanod and Admin roles can access the Blotter system.

### Creating a Blotter Case

1. Navigate to **Blotter** from the sidebar
2. Click **➕ Add New Blotter Case**
3. Fill in the required information:
   - **Complainant Name** (required)
   - **Respondent Name** (required)
   - **Incident Date** (required)
   - **Incident Location** (required)
   - **Incident Description** (required)
4. Fill in optional fields (contact numbers, addresses)
5. Select initial **Status**
6. Click **Add Blotter Case**

### Viewing Blotter Cases

1. Navigate to **Blotter** from the sidebar
2. View all cases in the table
3. Click on a **Case Number** to view full details
4. Use search and filters to find specific cases

### Updating Case Status

1. Open a case by clicking its case number
2. Select new **Status** from dropdown:
   - **Pending:** Initial status
   - **Under Investigation:** Case is being investigated
   - **Resolved:** Case has been resolved
   - **Dismissed:** Case has been dismissed
3. Add **Resolution** details if resolved
4. Set **Resolved Date** if applicable
5. Click **Update Status**

### Case Number Format

Case numbers are auto-generated: `BLT-YYYY-####`
- Example: `BLT-2024-0001`

---

## Officers Management

> **Note:** Officers management is integrated with Account Management. Only Admin can access this.

### Adding an Officer

1. Navigate to **Staff** (Account Management) from the sidebar
2. Click **➕ Add New Account**
3. Check **This user is an Officer**
4. Fill in account details:
   - Full Name, Username, Password, Role
5. Fill in officer-specific fields:
   - **Position** (e.g., Barangay Captain)
   - **Term Start** (required)
   - **Term End** (required)
   - **Officer Status** (Active/Inactive)
6. Optionally link to a resident record
7. Click **Add Account**

### Editing an Officer

1. Navigate to **Staff** from the sidebar
2. Find the officer in the list
3. Click **Edit**
4. Make your changes
5. Click **Save Changes**

### Officer Information

- Every officer must have a user account
- Officers can be linked to resident records (optional)
- Officer position and term dates are tracked
- Status can be set to Active or Inactive

---

## Account Management

> **Note:** Only Admin can access Account Management.

### Creating a New Account

1. Navigate to **Staff** from the sidebar
2. Click **➕ Add New Account**
3. Fill in:
   - **Full Name**
   - **Username** (must be unique)
   - **Password**
   - **Role** (Admin, Staff, or Tanod)
   - **Status** (Active or Disabled)
4. If creating an officer, check **This user is an Officer** and fill officer fields
5. If not an officer, optionally enter **Position**
6. Click **Add Account**

### Editing an Account

1. Navigate to **Staff** from the sidebar
2. Find the account in the list
3. Click **Edit**
4. Make your changes
5. To change password, enter new password (leave blank to keep current)
6. Click **Save Changes**

### Account Roles

- **Admin:** Full system access
- **Staff:** Resident and certificate management
- **Tanod:** Blotter system access only

---

## Settings

### Accessing Settings

1. Click on your profile picture/name in the top right
2. Select **Settings** or **Profile**

### Updating Profile

1. Navigate to **Settings**
2. Update your **Name** or **Username**
3. To change password, enter new password
4. Click **Save Changes**

### Profile Picture

1. Navigate to **Settings**
2. Click **Choose File** under Profile Picture
3. Select an image file (JPEG, PNG, GIF - max 2MB)
4. Click **Upload**
5. Your profile picture will appear in the navbar

---

## Troubleshooting

### Cannot Log In

- **Check username and password:** Ensure they are correct
- **Account status:** Contact admin if account is disabled
- **Forgot password:** Contact system administrator to reset

### Cannot Access a Feature

- **Check your role:** Some features are role-specific
- **Contact admin:** Request access if needed

### Data Not Saving

- **Check required fields:** Ensure all required fields are filled
- **Refresh page:** Try refreshing and re-entering data
- **Contact support:** If problem persists

### Print Issues

- **Check printer settings:** Ensure printer is connected
- **Use print preview:** Check layout before printing
- **Adjust scale/margins:** Use print options to fit A4 paper

### General Tips

- **Use search:** Most tables have search functionality
- **Click cards:** Dashboard cards are clickable for detailed views
- **Check status:** Many features have status indicators
- **Save frequently:** Save your work regularly

---

## Keyboard Shortcuts

- **Ctrl + F:** Search on current page
- **Esc:** Close dialogs/modals
- **Enter:** Submit forms

---

## Getting Help

For additional support:
1. Check the **Documentation** section in the sidebar
2. Contact your system administrator
3. Review system version and update notes

---

**Document Version:** 1.0  
**For System Version:** 1.3.0

