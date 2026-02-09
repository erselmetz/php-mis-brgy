<?php
/**
 * Dashboard Data API Endpoint
 * 
 * Provides filtered data for dashboard statistics.
 * Supports both staff/resident filters and tanod/blotter filters.
 * Uses prepared statements for security and proper role-based access control.
 */

require_once '../../includes/app.php';
requireHCNurse();
header('Content-Type: application/json');