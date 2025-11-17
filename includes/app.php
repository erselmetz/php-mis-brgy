<?php
/**
 * Application Bootstrap
 * MIS Barangay - Core Application Setup
 * 
 * This file initializes the application by starting the session
 * and including all necessary core files.
 */

session_start();
include_once 'function.php';
include_once 'auth.php';
include_once 'db.php';
include_once 'help.php';