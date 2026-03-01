<?php
// api/helpers.php
declare(strict_types=1);

function req_str(string $key, int $maxLen = 255, bool $required = true): ?string {
  $val = isset($_POST[$key]) ? trim((string)$_POST[$key]) : null;
  if ($required && ($val === null || $val === '')) return null;
  if ($val !== null && $val !== '' && mb_strlen($val) > $maxLen) return mb_substr($val, 0, $maxLen);
  return ($val === '') ? null : $val;
}

function req_int(string $key, bool $required = true): ?int {
  if (!isset($_POST[$key])) return $required ? null : null;
  $v = filter_var($_POST[$key], FILTER_VALIDATE_INT);
  return ($v === false) ? null : (int)$v;
}

function req_date(string $key, bool $required = false): ?string {
  $val = isset($_POST[$key]) ? trim((string)$_POST[$key]) : '';
  if ($val === '') return $required ? null : null;

  // accept yyyy-mm-dd only (simple + safe)
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $val)) return null;
  return $val;
}

function compute_medicine_status(?string $expiration, int $stockQty, int $reorderLevel): string {
  $today = date('Y-m-d');
  if ($expiration && $expiration < $today) return 'Expired';
  if ($stockQty <= 0) return 'Out of Stock';
  if ($stockQty <= $reorderLevel) return 'Critical';
  return 'In-Stock';
}
