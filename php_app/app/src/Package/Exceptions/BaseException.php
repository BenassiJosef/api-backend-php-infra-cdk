<?php


namespace App\Package\Exceptions;


use App\Package\Response\BodyResponse;
use App\Package\Response\ProblemResponse;
use App\Package\Response\Responder;
use App\Package\Response\Response;
use Exception;
use GuzzleHttp\Psr7\Uri;
use JsonSerializable;
use OAuth2\HttpFoundationBridge\Response as StatusCodes;
use Psr\Http\Message\UriInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Slim\Http\Response as SlimResponse;
use Throwable;

class BaseException extends Exception implements JsonSerializable, Response, Responder
{

	/**
	 * @var UuidInterface $instanceId
	 */
	private $instanceId;

	/**
	 * @var string[] $extra
	 */
	private $extra;

	/**
	 * BaseException constructor.
	 * @param string $message
	 * @param int $code
	 * @param array $extra
	 * @param Throwable|null $previous
	 * @throws Exception
	 */
	public function __construct(
		string $message = "Internal Server Error",
		int $code = StatusCodes::HTTP_INTERNAL_SERVER_ERROR,
		array $extra = [],
		Throwable $previous = null
	) {

		parent::__construct($message, $code, $previous);
		$this->message    = $message;
		$this->code       = $code;
		$this->extra      = $extra;
		$this->instanceId = Uuid::uuid1();
	}

	/**
	 * @return UriInterface|null
	 */
	public function getType(): ?UriInterface
	{
		$title = $this->getTitle();
		return (new Uri())
			->withScheme('https')
			->withHost($this->currentHost())
			->withPath("/public/errors/${title}");
	}

	/**
	 * @return string|null
	 */
	public function getTitle(): ?string
	{
		return from(array_merge(class_parents($this), [get_class($this)]))
			->where(
				function (string $type): bool {
					return !in_array($type, [self::class, Exception::class]);
				}
			)
			->toValues()
			->select(
				function (string $fullClassName): string {
					$className = string(from(string($fullClassName)->explode('\\'))->last());
					if ($className->endsWith('Exception')) {
						$className = $className->replaceLast('Exception', '');
					}
					return $className;
				}
			)
			->toString('/');
	}

	/**
	 * @return int|null
	 */
	public function getStatus(): ?int
	{
		return $this->code;
	}

	/**
	 * @return string|null
	 */
	public function getDetail(): ?string
	{
		return $this->message;
	}

	/**
	 * @return UriInterface|null
	 */
	public function getInstance(): ?UriInterface
	{
		$path       = $this->getType()->getPath();
		$instanceId = $this->instanceId;
		return $this
			->getType()
			->withPath("${path}/${instanceId}");
	}


	/**
	 * @param array|JsonSerializable $body
	 * @return BodyResponse
	 */
	public function withBody($body): BodyResponse
	{
		return new BodyResponse(
			$this,
			$body
		);
	}

	/**
	 * @param SlimResponse $response
	 * @return SlimResponse
	 */
	public function respond(SlimResponse $response): SlimResponse
	{
		return $response
			->withStatus($this->getCode())
			->withJson($this)
			->withHeader('Content-Type', 'application/problem+json');
	}


	/**
	 * Specify data which should be serialized to JSON
	 * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
	 * @return mixed data which can be serialized by <b>json_encode</b>,
	 * which is a value of any type other than a resource.
	 * @since 5.4
	 */
	public function jsonSerialize()
	{
		if (!$this->shouldDisplayError()) {
			return ProblemResponse::fromStatus($this->code)->jsonSerialize();
		}
		return array_merge(
			[
				'type'     => $this->getType()->__toString(),
				'title'    => $this->getTitle(),
				'status'   => $this->getStatus(),
				'detail'   => $this->getDetail(),
				'instance' => $this->getInstance()->__toString(),
			],
			$this->extra
		);
	}

	public function shouldDisplayError(): bool
	{
		return $this->code >= StatusCodes::HTTP_BAD_REQUEST
			&& $this->code < StatusCodes::HTTP_INTERNAL_SERVER_ERROR;
	}

	/**
	 * @return string
	 */
	private function currentHost(): string
	{
		$host = getenv('API_HOST');
		if ($host === false) {
			return 'api.stampede.ai';
		}
		return $host;
	}
}
