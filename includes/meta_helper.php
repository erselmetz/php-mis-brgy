<?php
function meta_decode(?string $raw): array
{
    if (!$raw) return [];

    $raw = trim($raw);

    // If JSON
    if ($raw !== '' && $raw[0] === '{') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) return $decoded;
    }

    // Fallback: parse old pipe format
    $data = [];
    $parts = explode('|', $raw);

    foreach ($parts as $part) {
        if (strpos($part, ':') !== false) {
            [$key, $value] = explode(':', $part, 2);
            $key = strtolower(trim(str_replace(' ', '_', $key)));
            $data[$key] = trim($value);
        }
    }

    return $data;
}

function meta_encode(array $data): string
{
    // remove null values
    $clean = [];

    foreach ($data as $k => $v) {
        if ($v === null) continue;
        if ($v === '') continue;
        $clean[$k] = $v;
    }

    return json_encode($clean, JSON_UNESCAPED_UNICODE);
}

function meta_normalize(array $meta): array
{
    // If remarks contains JSON string, decode and merge SAFE keys only
    if (isset($meta['remarks']) && is_string($meta['remarks'])) {
        $r = trim($meta['remarks']);
        if ($r !== '' && $r[0] === '{') {
            $decoded = json_decode($r, true);
            if (is_array($decoded)) {

                // keys that we allow to merge from nested json
                $allow = ['status', 'time', 'health_worker', 'worker', 'remarks', 'note'];

                foreach ($decoded as $k => $v) {
                    if (!in_array($k, $allow, true)) continue;

                    // only fill if missing/empty
                    if (!isset($meta[$k]) || $meta[$k] === '' || $meta[$k] === null) {
                        $meta[$k] = $v;
                    }
                }
            }
        }
    }

    // Make sure program/sub_type are strings if present
    if (isset($meta['program']) && !is_string($meta['program'])) $meta['program'] = (string)$meta['program'];
    if (isset($meta['sub_type']) && !is_string($meta['sub_type'])) $meta['sub_type'] = (string)$meta['sub_type'];

    return $meta;
}



function meta_get(?string $raw, string $key, $default = null)
{
    $data = meta_decode($raw);
    return $data[$key] ?? $default;
}

function meta_set(?string $raw, string $key, $value): string
{
    $data = meta_decode($raw);
    $data[$key] = $value;
    return meta_encode($data);
}

function json_ok(array $payload = []): void
{
  echo json_encode(['status' => 'ok'] + $payload);
  exit;
}

function json_err(string $message, int $code = 400, array $extra = []): void
{
  http_response_code($code);
  echo json_encode(['status' => 'error', 'message' => $message] + $extra);
  exit;
}

function respond($ok, $msg, $extra = []) {
  echo json_encode(array_merge(['success' => $ok, 'message' => $msg], $extra));
  exit;
}