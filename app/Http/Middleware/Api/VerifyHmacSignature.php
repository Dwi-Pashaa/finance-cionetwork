<?php

namespace App\Http\Middleware\Api;

use App\Services\Api\Hmac\HmacSignatureService;
use App\Services\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

class VerifyHmacSignature
{
    public function __construct(private HmacSignatureService $signatureService) {}

    private const STATUS_MAP = [
        'UNAUTHENTICATED' => 401,
        'CLIENT_NOT_FOUND' => 401,
        'CREDENTIAL_INVALID' => 401,
        'TIMESTAMP_INVALID' => 401,
        'SIGNATURE_INVALID' => 401,
        'NONCE_REUSED' => 409,
        'CLIENT_DISABLED' => 403,
        'CLIENT_REVOKED' => 403,
    ];

    private const MESSAGE_MAP = [
        'UNAUTHENTICATED' => 'Missing or invalid authentication headers',
        'CLIENT_NOT_FOUND' => 'Client not found',
        'CLIENT_DISABLED' => 'Client is disabled',
        'CLIENT_REVOKED' => 'Client has been revoked',
        'CREDENTIAL_INVALID' => 'Credential is invalid or expired',
        'TIMESTAMP_INVALID' => 'Timestamp outside allowed window',
        'NONCE_REUSED' => 'Nonce already used',
        'SIGNATURE_INVALID' => 'Invalid signature',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        try {
            $result = $this->signatureService->verify($request);
        } catch (InvalidArgumentException $e) {
            $code = $e->getMessage();

            return ApiResponse::error(
                self::MESSAGE_MAP[$code] ?? 'Authentication failed',
                $code,
                self::STATUS_MAP[$code] ?? 401
            );
        }

        $request->attributes->set('api_client', $result->client);
        $request->attributes->set('api_credential', $result->credential);

        $result->client->forceFill([
            'last_used_at' => now(),
            'last_ip' => $request->ip(),
        ])->saveQuietly();

        return $next($request);
    }
}
