<?php

namespace App\Controllers\Billing\Subscriptions\Cancellation;


use App\Controllers\Integrations\Mail\MailSender;
use App\Package\RequestUser\UserProvider;
use Slim\Http\Request;
use Slim\Http\Response;
use Exception;
use Throwable;

/**
 * Class CancellationController
 * @package App\Controllers\Billing\Subscriptions\Cancellation
 */
class CancellationController
{
    /**
     * @var MailSender $mailSender
     */
    private $mailSender;

    /**
     * @var UserProvider $userProvider
     */
    private $userProvider;

    /**
     * @var array $recipients
     */
    private $recipients = [];

    /**
     * CancellationController constructor.
     * @param MailSender $mailSender
     * @param UserProvider $userProvider
     * @param string $recipients
     */
    public function __construct(
        MailSender $mailSender,
        UserProvider $userProvider,
        string $recipients = "support@stampede.ai"
    )
    {
        $this->mailSender   = $mailSender;
        $this->userProvider = $userProvider;
        $this->recipients   = explode(',', $recipients);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function requestCancellation(Request $request, Response $response)
    {
        try {
            $cancellationRequest = $this->createCancellationRequest($request);
        } catch (Throwable $t) {
            $errorResponse = ["message" => "MUST_PROVIDE_REASON"];
            return $response->withJson($errorResponse, 400);
        }
        $cancellationEmail             = $this->createCancellationEmail($cancellationRequest);
        $cancellationNotificationEmail = $this->createCancellationNotificationEmail($cancellationRequest);
        $this->sendEmail($cancellationEmail);
        $this->sendEmail($cancellationNotificationEmail);
        return $response->withStatus(204);
    }

    /**
     * @param CancellationRequest $request
     * @return CancellationEmail
     */
    private function createCancellationEmail(CancellationRequest $request): CancellationEmail
    {
        return new CancellationEmail(
            $this->recipients,
            $request->getUser(),
            $request->getReason()
        );
    }

    private function createCancellationNotificationEmail(CancellationRequest $request): CancellationNotificationEmail
    {
        return new CancellationNotificationEmail(
            $request->getUser(),
            $request->getReason()
        );
    }

    /**
     * @param Request $request
     * @return CancellationRequest
     * @throws Exception
     */
    private function createCancellationRequest(Request $request): CancellationRequest
    {
        $reason = $request->getParsedBodyParam("reason");
        if ($reason === null) {
            throw new Exception("please provide a reason");
        }
        return new CancellationRequest(
            $this->userProvider->getUser($request),
            $reason
        );
    }

    /**
     * @param Email $email
     */
    private function sendEmail(Email $email): void
    {
        $this->mailSender->send(
            $email->getSendTo(),
            $email->getArguments(),
            $email->getTemplate(),
            $email->getSubject()
        );
    }
}