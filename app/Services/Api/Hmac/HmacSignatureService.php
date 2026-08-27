<?php

namespace App\Services\Api\Hmac;

use App\Enums\ApiClientStatus;
use App\Models\ApiClient;
use App\Models\ApiCredential;
use App\Models\NonceCache;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use InvalidArgumentException;

class HmacVerificationResult
{
    public function __construct(
        public readonly ApiClient $client,
        public readonly ApiCredential $credential,
    ) {}
}

class HmacSignatureService
{
    private const TIMESTAMP_TOLERANCE = 300;

    public function canonicalString(string $method, string $pathWithQuery, string $clientId, string $keyId, string $timestamp, string $nonce, string $bodyHash): string
    {
        return implode("\n", [$method, $pathWithQuery, $clientId, $keyId, $timestamp, $nonce, $bodyHash]);
    }

    /**
     * Helper untuk client (testing/dokumentasi): hitung signature dari secret.
     */
    public function sign(string $secret, string $canonicalString): string
    {
        return base64_encode(hash_hmac('sha256', $canonicalString, $secret, true));
    }

    public function verify(Request $request): HmacVerificationResult
    {
        $clientId = $request->header('X-Client-ID', '');
        $keyId = $request->header('X-Key-ID', '');
        $timestamp = $request->header('X-Timestamp', '');
        $nonce = $request->header('X-Nonce', '');
        $signature = $request->header('X-Signature', '');

        if ($clientId === '' || $keyId === '' || $timestamp === '' || $nonce === '' || $signature === '') {
            throw new InvalidArgumentException('UNAUTHENTICATED');
        }

        $client = ApiClient::where('client_id', $clientId)->first();

        if (! $client) {
            throw new InvalidArgumentException('CLIENT_NOT_FOUND');
        }

        if ($client->status === ApiClientStatus::Inactive) {
            throw new InvalidArgumentException('CLIENT_DISABLED');
        }

        if ($client->status === ApiClientStatus::Revoked) {
            throw new InvalidArgumentException('CLIENT_REVOKED');
        }

        /** @var ApiCredential|null $credential */
        $credential = $client->credentials()->where('key_id', $keyId)->first();

        if (! $credential || ! $credential->isActive()) {
            throw new InvalidArgumentException('CREDENTIAL_INVALID');
        }

        if (! ctype_digit($timestamp) || abs(now()->getTimestamp() - (int) $timestamp) > self::TIMESTAMP_TOLERANCE) {
            throw new InvalidArgumentException('TIMESTAMP_INVALID');
        }

        if (strlen($nonce) < 8 || strlen($nonce) > 128) {
            throw new InvalidArgumentException('SIGNATURE_INVALID');
        }

        $bodyHash = hash('sha256', $request->getContent() ?: '');
        $canonical = $this->canonicalString(
            strtoupper($request->getMethod()),
            '/'.ltrim($request->getRequestUri(), '/'),
            $clientId,
            $keyId,
            $timestamp,
            $nonce,
            $bodyHash
        );

        $expectedSignature = hash_hmac('sha256', $canonical, $this->resolveSecret($credential), true);
        $providedSignature = base64_decode($signature, true);

        if ($providedSignature === false || ! hash_equals($expectedSignature, $providedSignature)) {
            throw new InvalidArgumentException('SIGNATURE_INVALID');
        }

        $this->claimNonce($clientId, $nonce);

        return new HmacVerificationResult($client, $credential);
    }

    private function resolveSecret(ApiCredential $credential): string
    {
        // Verifikasi HMAC membutuhkan secret asli. Secret disimpan terenkripsi
        // (AES-256-GCM, kunci = APP_KEY) dan tidak pernah ditampilkan ulang.
        // Kolom secret_hash (SHA-256) hanya dipakai sebagai fingerprint/integritas.
        return Crypt::decryptString($credential->secret_encrypted);
    }

    private function claimNonce(string $clientId, string $nonce): void
    {
        try {
            NonceCache::create([
                'client_id' => $clientId,
                'nonce' => $nonce,
                'expires_at' => now()->addSeconds(self::TIMESTAMP_TOLERANCE * 2),
            ]);
        } catch (QueryException $e) {
            $sqlState = (string) $e->getCode();
            $message = $e->getMessage();

            if (
                ! in_array($sqlState, ['23000', '23505'], true)
                && ! str_contains($message, 'Duplicate entry')
                && ! str_contains($message, 'UNIQUE constraint failed')
            ) {
                throw $e;
            }

            throw new InvalidArgumentException('NONCE_REUSED');
        }
    }
}
