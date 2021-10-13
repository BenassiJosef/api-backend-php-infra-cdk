<?php

namespace App\Package\Clients\InternalOAuth;

use App\Package\Clients\InternalOAuth\Exceptions\TokenFetchException;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Throwable;

/**
 * Class ClientCredentialsTokenSource
 * @package App\Package\Clients\InternalOAuth
 */
class ClientCredentialsTokenSource implements TokenSource
{
    /**
     * @var ClientCredentialsConfig $config
     */
    private $config;

    /**
     * @var Client $client
     */
    private $client;

    /**
     * @var Token | null
     */
    private $token;

    /**
     * ClientCredentialsTokenSource constructor.
     * @param ClientCredentialsConfig $config
     * @param Client $client
     * @param Token|null $token
     */
    public function __construct(
        ClientCredentialsConfig $config,
        Client $client,
        ?Token $token = null
    ) {
        $this->config = $config;
        $this->client = $client;
        $this->token  = $token;
    }

    /**
     * @return Token
     * @throws TokenFetchException
     */
    public function token(): Token
    {
        if ($this->token !== null) {
            return $this->token;
        }
        $this->token = $this->fetchToken();
        return $this->token;
    }

    /**
     * @return Token
     * @throws TokenFetchException
     */
    private function fetchToken(): Token
    {
        try {
            $resp = $this->client->post(
                $this->config->getTokenURL(),
                [
                    RequestOptions::FORM_PARAMS => $this->config->requestBody(),
                ],
            );
        } catch (Throwable $exception) {
            throw new TokenFetchException($exception);
        }
        return Token::fromArray(
            json_decode($resp->getBody()->getContents(), true)
        );
    }

}