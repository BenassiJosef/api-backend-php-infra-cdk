<?php

namespace App\Policy;

class Role {
    protected $role;

    public function __construct($role)
    {
        $this->role = $role;
    }

    public function __invoke($request, $response, $next)
    {
        $memberRole = $request->getAttribute('user')['role'];

        if ($memberRole <= $this->role) {
            $resp = $next($request, $response);
        } else {
            $resp = $response->withStatus(403);
        }

        return $resp;
    }
}