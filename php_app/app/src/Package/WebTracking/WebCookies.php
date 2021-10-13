<?php

namespace App\Package\WebTracking;

use Dflydev\FigCookies\Cookie;
use Dflydev\FigCookies\FigRequestCookies;
use Dflydev\FigCookies\FigResponseCookies;
use Dflydev\FigCookies\Modifier\SameSite;
use Dflydev\FigCookies\SetCookie;
use Slim\Http\Request;
use Slim\Http\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class WebCookies
{

    protected $permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyz';
    protected $cookie_id = 'stmpd_id';
    protected $domain = '.stampede.ai';
    public $cookie_name = 'Stmpd-Cookie';
    protected $createResponseCookie = false;
    protected $user_cookie = '';

    /**
     * @param Response $response
     * @param $cookie
     * @return ResponseInterface
     */
    public function setCookieResponse(Response $response, $cookie): ResponseInterface
    {
        return FigResponseCookies::set($response,
            SetCookie::create($this->cookie_id)
                ->withValue($cookie)
                ->rememberForever()
                ->withPath('/')
                ->withDomain($this->domain)
                ->withHttpOnly(true)
                ->withSameSite(SameSite::none())
                ->withSecure(true)
        );
    }

    /**
     * @param Request $request
     * @return RequestInterface|Request
     */

    public function handleMiddlewareRequest(Request $request)
    {
        $cookie = $this->getCookie($request)->getValue();
        if (!$cookie) {
            $this->createResponseCookie = true;
            $cookie                     = $this->generateCookie();
            $request                    = $this->setCookieRequest($request, $cookie);
        }

        $this->user_cookie = $cookie;

        return $request;
    }

    public function handleMiddlewareResponse(Response $response)
    {
        if ($this->createResponseCookie) {
            return $this->setCookieResponse($response, $this->user_cookie);
        }

        return $response;
    }

    /**
     * @param Request $request
     * @return Cookie
     */

    public function getCookie(Request $request): Cookie
    {
        return FigRequestCookies::get($request, $this->cookie_id);
    }

    /**
     * @param Request $request
     * @param string $cookie
     * @return RequestInterface
     */
    public function setCookieRequest(Request $request, string $cookie): RequestInterface
    {
        return FigRequestCookies::set($request, Cookie::create($this->cookie_id, $cookie));
    }

    /**
     * @return string
     */
    public function generateCookie(): string
    {
        return '_' . substr(str_shuffle($this->permitted_chars), 0, 9);
    }

}