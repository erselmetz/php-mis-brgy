<?php
/**
 * Resident Model
 * Handles database operations for residents
 */

class ResidentModel extends BaseModel {
    public function __construct() {
        parent::__construct();
        $this->table = 'residents';
    }
}

