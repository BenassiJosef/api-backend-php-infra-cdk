<?php

namespace App\Package\Upload;

use App\Models\DataSources\DataSource;
use App\Models\Locations\LocationSettings;
use App\Package\Async\FlushException;
use App\Package\DataSources\FlushingProfileSaver;
use App\Package\DataSources\InteractionRequest;
use App\Package\DataSources\OptInStatuses;
use App\Package\DataSources\ProfileInteractionFactory;
use App\Package\Organisations\OrganizationProvider;
use App\Package\Upload\Exceptions\InvalidDataSourceException;
use App\Package\Upload\Exceptions\InvalidHeadersException;
use App\Package\Upload\Exceptions\SaveException;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Exception;
use GuzzleHttp\Psr7\Utils;
use Iterator;
use Psr\Http\Message\StreamInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use Throwable;
use YaLinqo\Enumerable;

/**
 * Class UploadController
 * @package App\Package\Upload
 */
class UploadController
{
    /**
     * @var OrganizationProvider $organizationProvider
     */
    private $organizationProvider;

    /**
     * @var ProfileInteractionFactory $profileInteractionFactory
     */
    private $profileInteractionFactory;

    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * @var int $batchSize
     */
    private $batchSize = 10;

    /**
     * UploadController constructor.
     * @param EntityManager $entityManager
     * @param OrganizationProvider $organizationProvider
     * @param ProfileInteractionFactory $profileInteractionFactory
     */
    public function __construct(
        EntityManager $entityManager,
        OrganizationProvider $organizationProvider,
        ProfileInteractionFactory $profileInteractionFactory
    ) {
        $this->entityManager             = $entityManager;
        $this->organizationProvider      = $organizationProvider;
        $this->profileInteractionFactory = $profileInteractionFactory;
    }

    /**
     * @param Request $request
     * @return DataSource
     * @throws InvalidDataSourceException
     */
    private function dataSourceFromRequest(Request $request): DataSource
    {
        $key = $request->getQueryParam('data-source', 'import');
        /** @var DataSource | null $dataSource */
        $dataSource = $this
            ->entityManager
            ->getRepository(DataSource::class)
            ->findOneBy(
                [
                    'key' => $key,
                ]
            );
        if ($dataSource === null) {
            throw new InvalidDataSourceException($key);
        }
        return $dataSource;
    }

    private function createProfileInteraction(Request $request): FlushingProfileSaver
    {
        $organization       = $this->organizationProvider->organizationForRequest($request);
        $dataSource         = $this->dataSourceFromRequest($request);
        $serials            = $this->serials($request);
        $interactionRequest = new InteractionRequest(
            $organization,
            $dataSource,
            $serials
        );
        $profileInteraction = $this
            ->profileInteractionFactory
            ->makeProfileInteraction($interactionRequest);
        return new FlushingProfileSaver($profileInteraction);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws Throwable
     */
    public function upload(Request $request, Response $response): Response
    {
        $body       = Utils::streamFor(fopen('php://input', 'r'));
        $headerLine = Utils::readLine($body);
        $headers    = UploadRow::truncateHeaders(str_getcsv($headerLine));
        if (!UploadRow::validHeaders($headers)) {
            throw new InvalidHeadersException($headers);
        }
        $this->entityManager->beginTransaction();
        $interaction = $this->createProfileInteraction($request);
        try {
            $numSaved = $this->saveLines($interaction, $headers, $body);
        } catch (Throwable $exception) {
            newrelic_notice_error($exception);
            $this->entityManager->rollback();
            throw new SaveException("Failed to Save", $exception);
        }
        $this->entityManager->commit();
        return $response->withJson(
            Http::status(
                200,
                [
                    'recordsSaved' => $numSaved,
                ]
            )
        );
    }

    /**
     * @param FlushingProfileSaver $interactionRequest
     * @param string[] $headers
     * @param resource|string|null|int|float|bool|StreamInterface|callable|Iterator $stream
     * @return int
     * @throws FlushException
     */
    private function saveLines(FlushingProfileSaver $interactionRequest, array $headers, $stream): int
    {
        $numSaved = 0;
        while (!$stream->eof()) {
            $line    = Utils::readLine($stream);
            $lineArr = UploadRow::truncateHeaders(str_getcsv($line));
            if (count($lineArr) !== UploadRow::numHeaders()) {
                continue;
            }
            $row = UploadRow::fromArray(array_combine($headers, $lineArr));
            $interactionRequest->saveCandidateProfile($row->toCandidateProfile(), OptInStatuses::optedIn());
            $numSaved++;
            if ($numSaved % $this->batchSize === 0) {
                $interactionRequest->flush();
            }
        }
        $interactionRequest->flush();
        return $numSaved;
    }

    /**
     * @param Request $request
     * @return string[]
     * @throws Exception
     */
    private function serials(Request $request): array
    {
        return array_intersect(
            $this->allSerials($request),
            $this->requestSerials($request),
        );
    }

    private function requestSerials(Request $request): array
    {
        $serialsQuery = $request->getQueryParam('serials', []);
        if (is_string($serialsQuery)) {
            return [$serialsQuery];
        }
        return $serialsQuery;
    }

    private function allSerials(Request $request): array
    {
        $locations = $this
            ->organizationProvider
            ->organizationForRequest($request)
            ->getLocations();
        return Enumerable::from($locations)
                         ->select(
                             function (LocationSettings $locationSettings) {
                                 return $locationSettings->getSerial();
                             }
                         )->toArray();
    }
}