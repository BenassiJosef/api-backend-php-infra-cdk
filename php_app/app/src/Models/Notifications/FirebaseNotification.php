<?php

namespace App\Models\Notifications;

class FirebaseNotification
{

    /**
     * @var string
     */
    private $token;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $body;

    /**
     * @var string
     */
    private $type;

    /**
     * @var array
     */
    private $data = [];

    /**
     * @var array
     */
    private $link = '';

    public function __construct(string $title, string $body, array $data, string $type = 'stampede')
    {
        $this->title = $title;
        $this->body = $body;
        $this->data = $data;
        $this->type = $type;
    }

    public function setToken(string $token)
    {
        $this->token = $token;
    }

    public function getToken()
    {
        return $this->token;
    }

    public function setTitle(string $title)
    {
        $this->title = $title;
    }
    public function getTitle()
    {
        return $this->title;
    }

    public function setBody(string $body)
    {
        $this->body = $body;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function setData(array $data)
    {
        $this->data = $data;
    }

    public function getNotification()
    {
        return [
            'title' => $this->getTitle(),
            'body' => $this->getBody(),
        ];
    }

    public function getFirebaseNotification()
    {
        return [
            'type' => $this->type,
            'token' => $this->getToken(),
            'notification' => $this->getNotification(),
            'data' => $this->data,
            'webpush' => [
                'fcm_options' => [
                    'link' => $this->link,
                ],
            ]];
    }

}
