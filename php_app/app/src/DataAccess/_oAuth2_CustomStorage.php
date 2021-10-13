<?php

namespace App\DataAccess;

use App\Models\OauthClients;
use OAuth2\Storage\Pdo;

/**
 * Class _oAuth2_CustomStorage / setAuthorizationCodeWithIdToken
 */
class _oAuth2_CustomStorage extends Pdo
{
    protected $db;
    protected $config;

    protected $request;


    /**
     * @var OauthClients $oauthClient
     */
    protected $oauthClient;

    public function __construct($connection, $config = [])
    {
        parent::__construct($connection, $config);
        $this->config['user_table']        = 'oauth_users';
        $this->config['nearly_user_table'] = 'user_profile_accounts';
        $this->oauthClient = new OauthClients();
    }

    public function setRequest(array $body)
    {
        $this->request = $body;
    }


    public function getUser($email)
    {
        $client = null;

        if (isset($this->request)) {
            if (isset($this->request['access_client'])) {
                $client = $this->request['access_client'];
            } else {
                $client = $this->request['client_id'];
            }
        }
        $this->oauthClient->setClientId($client);
        if ($this->oauthClient->isEndUserClient()) {
            $stmt = $this->db->prepare(
                $sql = sprintf('SELECT user_profile_accounts.id, user_profile_accounts.password 
                                        FROM user_profile_accounts 
                                        LEFT JOIN user_profile ON user_profile_accounts.id = user_profile.id 
                                        WHERE user_profile.email = :email')
            );
        } else {
            $stmt = $this->db->prepare($sql = sprintf(
                'SELECT uid, email, password FROM %s WHERE email=:email AND deleted=0',
                $this->config['user_table']
            ));
        }

        $stmt->execute(['email' => $email]);

        if (!$userInfo = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            return false;
        }

        // we use id as the user_id
        if ($this->oauthClient->isEndUserClient()) {
            return array_merge([
                'user_id' => $userInfo['id']
            ], $userInfo);
        }

        return array_merge([
            'user_id' => $userInfo['uid']
        ], $userInfo);
    }

    public function checkPassword($user, $password)
    {
        $client = null;

        if (isset($this->request)) {
            if (isset($this->request['access_client'])) {
                $client = $this->request['access_client'];
            } else {
                $client = $this->request['client_id'];
            }
        }
        $this->oauthClient->setClientId($client);

        if ($this->oauthClient->isEndUserClient()) {
            return $user['password'] == hash('sha512', $password);
        }

        return $user['password'] == $this->hashPassword($password);
    }
}
