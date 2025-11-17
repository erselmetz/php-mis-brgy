<?php
/**
 * Inline Help Documentation Functions
 * Provides tooltips and help text for forms
 */

/**
 * Generate help tooltip HTML
 * 
 * @param string $text Tooltip text
 * @param string $position Tooltip position (default: 'right')
 * @return string HTML for tooltip
 */
function helpTooltip(string $text, string $position = 'right'): string
{
    $tooltipId = 'tooltip_' . uniqid();
    return '<span class="help-tooltip" data-tooltip="' . htmlspecialchars($text) . '" data-position="' . $position . '">
                <svg class="w-4 h-4 text-gray-400 inline-block ml-1 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </span>';
}

/**
 * Generate help text below input
 * 
 * @param string $text Help text
 * @param string $class CSS classes (default: 'text-xs text-gray-500 mt-1')
 * @return string HTML for help text
 */
function helpText(string $text, string $class = 'text-xs text-gray-500 mt-1'): string
{
    return '<p class="' . $class . '">' . htmlspecialchars($text) . '</p>';
}

/**
 * Common help messages
 */
class HelpMessages
{
    const USERNAME = "Username must be unique. Use letters, numbers, and underscores only.";
    const PASSWORD = "Choose a strong password. Minimum 8 characters recommended.";
    const ROLE_ADMIN = "Admin has full system access including account management.";
    const ROLE_STAFF = "Staff can manage residents and certificates.";
    const ROLE_TANOD = "Tanod can only access the blotter system.";
    const OFFICER_POSITION = "Enter the official position title (e.g., Barangay Captain, Barangay Secretary).";
    const TERM_DATES = "Term start and end dates define the officer's service period.";
    const RESIDENT_LINK = "Link to a resident record if the officer is a registered resident. Leave blank if not.";
    const CASE_NUMBER = "Case number is auto-generated in format: BLT-YYYY-####";
    const BLOTTER_STATUS = "Pending: Initial status. Under Investigation: Being investigated. Resolved: Case closed. Dismissed: Case dismissed.";
    const CERTIFICATE_TYPE = "Select the type of certificate being requested (e.g., Barangay Clearance, Certificate of Residency).";
    const HOUSEHOLD_ID = "Optional. Link resident to a household record if applicable.";
    const VOTER_STATUS = "Indicates if the resident is registered to vote.";
    const DISABILITY_STATUS = "Indicates if the resident has a disability.";
}

