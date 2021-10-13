<?php

namespace App\Package\Service;

use App\Package\Exceptions\BaseException;

/**
 * Class InvalidReachInput
 * @package App\Package\Segments\Exceptions
 */
class ServiceException extends BaseException
{
	/**
	 * InvalidReachInput constructor.
	 * @param array $reachInput
	 */
	public function __construct(string $message, int $status)
	{
		parent::__construct(
			"(${message})",
			$status
		);
	}
}
