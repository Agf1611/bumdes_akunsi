<?php

declare(strict_types=1);

final class AuthMfa
{
    public static function isEnabledForUser(array $user): bool
    {
        return (bool) (auth_config('mfa')['enabled'] ?? false)
            && (int) ($user['mfa_enabled'] ?? 0) === 1
            && trim((string) ($user['mfa_secret'] ?? '')) !== '';
    }

    public static function verifyForUser(array $user, string $otp): bool
    {
        if (!self::isEnabledForUser($user)) {
            return true;
        }

        $otp = preg_replace('/\s+/', '', trim($otp)) ?? '';
        if ($otp === '' || preg_match('/^\d{6}$/', $otp) !== 1) {
            return false;
        }

        $window = max(0, (int) (auth_config('mfa')['window'] ?? 1));
        return self::verifyTotp((string) ($user['mfa_secret'] ?? ''), $otp, $window);
    }

    public static function generateSecret(int $length = 20): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        return $secret;
    }

    public static function verifyTotp(string $secret, string $otp, int $window = 1): bool
    {
        $counter = (int) floor(time() / 30);
        for ($offset = -$window; $offset <= $window; $offset++) {
            if (hash_equals(self::codeForCounter($secret, $counter + $offset), $otp)) {
                return true;
            }
        }
        return false;
    }

    private static function codeForCounter(string $secret, int $counter): string
    {
        $binarySecret = self::base32Decode($secret);
        $binaryCounter = pack('N*', 0) . pack('N*', $counter);
        $hash = hash_hmac('sha1', $binaryCounter, $binarySecret, true);
        $offset = ord(substr($hash, -1)) & 0x0F;
        $chunk = substr($hash, $offset, 4);
        $value = unpack('N', $chunk)[1] & 0x7FFFFFFF;
        return str_pad((string) ($value % 1000000), 6, '0', STR_PAD_LEFT);
    }

    private static function base32Decode(string $value): string
    {
        $value = strtoupper(preg_replace('/[^A-Z2-7]/', '', $value) ?? '');
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bits = '';
        foreach (str_split($value) as $char) {
            $position = strpos($alphabet, $char);
            if ($position === false) {
                continue;
            }
            $bits .= str_pad(decbin($position), 5, '0', STR_PAD_LEFT);
        }

        $binary = '';
        foreach (str_split($bits, 8) as $chunk) {
            if (strlen($chunk) === 8) {
                $binary .= chr(bindec($chunk));
            }
        }

        return $binary;
    }
}
