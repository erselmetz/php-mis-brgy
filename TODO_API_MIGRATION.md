# API Migration and MVC Implementation Plan

## Current State Analysis
- ✅ Some API endpoints exist: `dashboard_data.php`, `resident/save.php`, `household/save.php`, `certificate_request_submit.php`
- ❌ UI pages contain mixed backend logic (database queries, form handling)
- ❌ No centralized API structure
- ❌ Direct database queries in UI files
- ❌ No proper separation of concerns

## Migration Plan

### Phase 1: Create Centralized API Structure
- [ ] Create `public/api/v1/` directory structure
- [ ] Create base API classes (BaseController, BaseModel, ApiResponse)
- [ ] Implement authentication middleware for API endpoints
- [ ] Create consistent error handling and response formatting

### Phase 2: Residents Module Migration
- [ ] Create `public/api/v1/residents/` directory
- [ ] Create ResidentController with CRUD operations
- [ ] Create ResidentModel for database operations
- [ ] Migrate existing `resident/save.php` logic to new structure
- [ ] Update `residents.php` to use API calls instead of direct DB queries
- [ ] Update `resident/view.php` to use API calls
- [ ] Update `resident/add.php` to use API calls

### Phase 3: Households Module Migration
- [ ] Create `public/api/v1/households/` directory
- [ ] Create HouseholdController with CRUD operations
- [ ] Create HouseholdModel for database operations
- [ ] Migrate existing `household/save.php` logic
- [ ] Update `households.php` to use API calls
- [ ] Update `household/view.php` to use API calls
- [ ] Update `household/add.php` to use API calls

### Phase 4: Certificates Module Migration
- [ ] Create `public/api/v1/certificates/` directory
- [ ] Create CertificateController for requests and status updates
- [ ] Create CertificateModel for database operations
- [ ] Migrate existing `certificate_request_submit.php` logic
- [ ] Update `certificates.php` to use API calls
- [ ] Update certificate status update functionality
- [ ] Update certificate printing functionality

### Phase 5: Blotter Module Migration
- [ ] Create `public/api/v1/blotter/` directory
- [ ] Create BlotterController with CRUD operations
- [ ] Create BlotterModel for database operations
- [ ] Migrate blotter form handling from `blotter.php`
- [ ] Update `blotter.php` to use API calls
- [ ] Update `blotter/view.php` to use API calls

### Phase 6: Admin/Accounts Module Migration
- [ ] Create `public/api/v1/admin/` directory
- [ ] Create AdminController for account management
- [ ] Create UserModel for user operations
- [ ] Migrate account CRUD operations from `admin/account.php`
- [ ] Update `admin/account.php` to use API calls

### Phase 7: Dashboard Migration
- [ ] Update existing `dashboard_data.php` to use new API structure
- [ ] Create DashboardController and DashboardModel
- [ ] Update dashboard UI to use API calls

### Phase 8: Testing and Cleanup
- [ ] Test all migrated functionality
- [ ] Remove old backend logic from UI files
- [ ] Update any remaining direct database calls
- [ ] Add API documentation
- [ ] Implement proper error logging

## API Structure
```
public/api/v1/
├── index.php (API entry point)
├── BaseController.php
├── BaseModel.php
├── ApiResponse.php
├── middleware/
│   ├── AuthMiddleware.php
│   └── ValidationMiddleware.php
├── residents/
│   ├── index.php
│   ├── ResidentController.php
│   └── ResidentModel.php
├── households/
│   ├── index.php
│   ├── HouseholdController.php
│   └── HouseholdModel.php
├── certificates/
│   ├── index.php
│   ├── CertificateController.php
│   └── CertificateModel.php
├── blotter/
│   ├── index.php
│   ├── BlotterController.php
│   └── BlotterModel.php
├── admin/
│   ├── index.php
│   ├── AdminController.php
│   └── UserModel.php
└── dashboard/
    ├── index.php
    ├── DashboardController.php
    └── DashboardModel.php
```

## Implementation Notes
- All API responses will be JSON format
- Consistent error handling with proper HTTP status codes
- Input validation using middleware
- Database operations abstracted to models
- Controllers handle request/response logic only
- Authentication required for all API endpoints
- Role-based access control maintained
