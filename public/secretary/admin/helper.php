<?php

// Helper: build full name from residents table
function getResidentFullName(mysqli $conn, int $residentId): ?string
{
    $stmt = $conn->prepare("
        SELECT first_name, middle_name, last_name, suffix
        FROM residents
        WHERE id = ?
        LIMIT 1
    ");
    if ($stmt === false) {
        error_log('getResidentFullName prepare failed: ' . $conn->error);
        return null;
    }

    $stmt->bind_param("i", $residentId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if (!$row) return null;

    $first  = trim($row['first_name'] ?? '');
    $middle = trim($row['middle_name'] ?? '');
    $last   = trim($row['last_name'] ?? '');
    $suffix = trim($row['suffix'] ?? '');

    $full = trim($first . ' ' . ($middle ? $middle . ' ' : '') . $last . ($suffix ? ' ' . $suffix : ''));
    return $full !== '' ? $full : null;
}

/** Default position label from account role (Officials & Staff). */
function default_officer_position_for_role(string $role): string
{
    $role = strtolower(trim($role));
    $map = [
        'captain'   => 'Barangay Captain',
        'kagawad'   => 'Barangay Kagawad',
        'secretary' => 'Barangay Secretary',
        'hcnurse'   => 'Barangay HC Nurse',
    ];
    return $map[$role] ?? '';
}

function mapUserStatusToOfficerStatus(string $userStatus): string
{
    $userStatus = strtolower(trim($userStatus));
    return $userStatus === 'active' ? 'Active' : 'Inactive';
}

function mapOfficerStatusToUserStatus(string $officerStatus): string
{
    $officerStatus = strtolower(trim($officerStatus));
    return $officerStatus === 'active' ? 'active' : 'disabled';
}