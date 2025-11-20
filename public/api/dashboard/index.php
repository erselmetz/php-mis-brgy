<?php
/**
 * Dashboard API Entry Point
 * Routes requests to the appropriate controller
 */

require_once '../../../includes/app.php';
require_once '../BaseController.php';
require_once '../ApiResponse.php';
require_once '../middleware/AuthMiddleware.php';
require_once 'DashboardController.php';

// Handle the request
$controller = new DashboardController();
$controller->handle();
