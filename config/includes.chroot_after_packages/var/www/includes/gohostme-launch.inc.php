<?php

if (!function_exists('root_load_shared_sso_secret')) {
    function root_load_shared_sso_secret(): string {
        static $ssoSecret = null;

        if ($ssoSecret !== null) {
            return $ssoSecret;
        }

        $secretFile = getenv('SSO_SECRET_FILE') ?: '/home/root/.vault/ecosystem-sso-secret';
        if (is_readable($secretFile)) {
            $fileSecret = trim((string) file_get_contents($secretFile));
            if ($fileSecret !== '') {
                $ssoSecret = $fileSecret;
                return $ssoSecret;
            }
        }

        $envSecret = trim((string) (getenv('SSO_SECRET') ?: ''));
        if ($envSecret !== '') {
            $ssoSecret = $envSecret;
            return $ssoSecret;
        }

        $ssoSecret = '';
        return $ssoSecret;
    }
}

if (!function_exists('root_create_signed_sso_token')) {
    function root_create_signed_sso_token(?int $clientId, int $ttlSeconds = 300): string {
        $clientId = (int) $clientId;
        if ($clientId <= 0) {
            return '';
        }

        $ssoSecret = root_load_shared_sso_secret();
        if ($ssoSecret === '') {
            return '';
        }

        $timestamp = time();
        $signature = hash_hmac('sha256', $clientId . '|' . $timestamp, $ssoSecret);
        return base64_encode($clientId . '|' . $timestamp . '|' . $signature);
    }
}

if (!function_exists('root_build_gohostme_launch_url')) {
    function root_build_gohostme_launch_url(?int $clientId, string $panel = 'dashboard'): string {
        $baseUrl = 'https://root.com';
        $panel = trim($panel);
        $returnTo = '/gohostme/dashboard';

        if ($panel !== '' && $panel !== 'dashboard') {
            $returnTo .= '?panel=' . rawurlencode($panel);
        }

        $token = root_create_signed_sso_token($clientId);
        if ($token === '') {
            return $clientId ? $baseUrl . $returnTo : $baseUrl . '/gohostme/';
        }

        return $baseUrl . $returnTo
            . (str_contains($returnTo, '?') ? '&' : '?')
            . 'sso=' . rawurlencode($token);
    }
}