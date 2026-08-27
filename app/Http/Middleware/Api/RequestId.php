<?php

namespace App\Http\Middleware\Api;

use App\Services\Api\ApiClientService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequestId
{
    public function __construct(private ApiClientService $clientService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set('request_id', $this->clientService->generateRequestId());

        return $next($request);
    }
}
