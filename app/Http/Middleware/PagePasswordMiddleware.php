<?php

namespace App\Http\Middleware;

use App\Models\Pages;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class PagePasswordMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $slug = $request->route('slug');
        $page = Pages::where('slug', $slug)->first();

        if (!$page) {
            abort(404);
        }


        if ($page->password) {
            // $sessionKey = 'page_access_' . $page->id;
            // if (!Session::has($sessionKey) || now()->diffInMinutes(Session::get($sessionKey)) > 60) {
            //     Session::flash('password_required', true);
            // }
            $cookieKey = 'page_access_' . $page->id;
            if (!request()->cookie($cookieKey)) {
                Session::flash('password_required', true);
            }
        }

        $request->attributes->set('page', $page);

        return $next($request);
    }
}
