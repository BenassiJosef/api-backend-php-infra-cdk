<?php


namespace App\Package\Profile\Data\Presentation;


use App\Package\Auth\Exceptions\ForbiddenException;
use App\Package\Auth\ProfileSource;
use App\Package\Database\Database;
use App\Package\Database\Exceptions\FailedToExecuteStatementException;
use App\Package\Database\Exceptions\UnsupportedParamTypeException;
use App\Package\Profile\Data\DataArea;
use App\Package\Profile\Data\DataDefinition;
use App\Package\Profile\Data\DataDeleter;
use App\Package\Profile\Data\DataFetcher;
use App\Package\Profile\Data\Deletable;
use App\Package\Profile\Data\Exceptions\SubjectNotFoundException;
use App\Package\Profile\Data\ObjectDefinition;
use App\Package\Profile\Data\SubjectLocator;
use App\Package\Reviews\DelayedReviewSender;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\StatusCode;

/**
 * Class DataController
 * @package App\Package\Profile\Data\Presentation
 */
class DataController
{
    /**
     * @var DataFetcher $dataFetcher
     */
    private $dataFetcher;

    /**
     * @var SubjectLocator $subjectLocator
     */
    private $subjectLocator;

    /**
     * @var Database $database
     */
    private $database;

    /**
     * DataController constructor.
     * @param DataFetcher $dataFetcher
     * @param SubjectLocator $subjectLocator
     * @param Database $database
     */
    public function __construct(
        DataFetcher $dataFetcher,
        SubjectLocator $subjectLocator,
        Database $database
    ) {
        $this->dataFetcher    = $dataFetcher;
        $this->subjectLocator = $subjectLocator;
        $this->database       = $database;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws ForbiddenException
     * @throws SubjectNotFoundException
     * @throws FailedToExecuteStatementException
     * @throws UnsupportedParamTypeException
     */
    public function getData(Request $request, Response $response): Response
    {
        /** @var ProfileSource | null $ps */
        $ps = $request->getAttribute(ProfileSource::class);
        if ($ps === null) {
            throw new ForbiddenException();
        }

        $profileId = $ps
            ->getProfile()
            ->getId();
        $subject   = $this
            ->subjectLocator
            ->byId($profileId);
        return $response->withJson(
            $this->dataFetcher->dataForSubject($subject)
        );
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws ForbiddenException
     * @throws SubjectNotFoundException
     */
    public function forget(Request $request, Response $response): Response
    {
        /** @var ProfileSource | null $ps */
        $ps = $request->getAttribute(ProfileSource::class);
        if ($ps === null) {
            throw new ForbiddenException();
        }
        $profileId = $ps
            ->getProfile()
            ->getId();
        $subject   = $this
            ->subjectLocator
            ->byId($profileId);

        DataDefinition::base()
                      ->walkReverse(new DataDeleter($this->database, $subject));
        return $response->withStatus(StatusCode::HTTP_NO_CONTENT);
    }
}