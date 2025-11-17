# MIS Barangay - Management Information System

A comprehensive Management Information System designed for barangay administration to efficiently manage residents, certificates, blotter cases, officers, and administrative tasks.

## Purpose

This system was developed as a capstone project to digitize and streamline barangay operations, providing a centralized platform for:

- **Resident Management** - Maintain complete resident database with household and family information
- **Certificate Management** - Generate and track barangay certificates (clearance, residency, etc.)
- **Blotter System** - Record and manage incident reports and case tracking for barangay security (Tanod)
- **Officer Management** - Track barangay officers, their positions, and terms of service
- **Administrative Dashboard** - Role-based dashboards providing real-time statistics and quick access to key information

## Key Features

- Role-based access control (Admin, Staff, Tanod)
- Secure authentication and session management
- Database-driven with MySQL
- Responsive web interface
- Automated certificate generation
- Case management system for security incidents
- Profile management with photo uploads

## Technology Stack

- **Backend:** PHP 7.4+
- **Database:** MySQL 5.7+
- **Frontend:** HTML, CSS (Tailwind), JavaScript (jQuery)
- **Server:** Apache with mod_rewrite

## Quick Start

1. Clone the repository
2. Configure database settings in `config.php`
3. Run database migrations: `php schema/run.php`
4. Create admin account: `php seed.php`
5. Access the system via web browser

## Documentation

- User Manual: `markdown/user_manual.md`
- Database Schema: `markdown/database_schema.md`
- Web Documentation: Access `/docs` in the application

**Version:** 1.3.0  
**Author:** Ersel Magbanua  
**License:** See LICENSE file
