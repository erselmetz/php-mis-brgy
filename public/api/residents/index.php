<?php
/**
 * Residents API Entry Point
 * Routes requests to the appropriate controller
 */

require_once '../../../includes/app.php';
require_once '../BaseController.php';
require_once '../BaseModel.php';
require_once '../ApiResponse.php';
require_once '../middleware/AuthMiddleware.php';
require_once 'ResidentModel.php';
require_once 'ResidentController.php';

// Handle the request
$controller = new ResidentController();
$controller->handle();

