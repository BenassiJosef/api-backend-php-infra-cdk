<?php
/**
 * Created by jamieaitken on 28/02/2018 at 10:28
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Policy;


class RoleOR
{

    protected $role1;
    protected $role2;

    public function __construct(int $role1, int $role2)
    {
        $this->role1 = $role1;
        $this->role2 = $role2;
    }

    public function __invoke($request, $response, $next)
    {
        $memberRole = $request->getAttribute('user')['role'];

        if ($memberRole === 0 || $memberRole === $this->role1 || $this->role2) {
            $resp = $next($request, $response);
        } else {
            $resp = $response->withStatus(403);
        }

        return $resp;
    }
}