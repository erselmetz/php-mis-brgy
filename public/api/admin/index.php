<?php
/**
 * Admin API Entry Point
 * Routes requests to the appropriate controller
 */

require_once '../../../includes/app.php';
require_once '../BaseController.php';
require_once '../BaseModel.php';
require_once '../ApiResponse.php';
require_once '../middleware/AuthMiddleware.php';
require_once 'UserModel.php';
require_once 'AdminController.php';

// Handle the request
$controller = new AdminController();
$controller->handle();
