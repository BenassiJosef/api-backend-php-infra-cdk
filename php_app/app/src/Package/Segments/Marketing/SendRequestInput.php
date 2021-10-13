<?php


namespace App\Package\Segments\Marketing;


use Slim\Http\Request;

class SendRequestInput
{
    /**
     * @param Request $request
     * @return static
     */
    public static function fromRequest(Request $request): self
    {
        return self::fromArray($request->getParsedBody());
    }

    /**
     * @param array $data
     * @return static
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['campaignType'] ?? SendRequest::TYPE_SMS,
            $data['template'] ?? ''
        );
    }

    /**
     * @var string $campaignType
     */
    private $campaignType;

    /**
     * @var string $template
     */
    private $template;

    /**
     * SendRequestInput constructor.
     * @param string $campaignType
     * @param string $template
     */
    public function __construct(
        string $campaignType = SendRequest::TYPE_SMS,
        string $template = ''
    ) {
        $this->campaignType = $campaignType;
        $this->template     = $template;
    }

    /**
     * @return string
     */
    public function getCampaignType(): string
    {
        return $this->campaignType;
    }

    /**
     * @return string
     */
    public function getTemplate(): string
    {
        return $this->template;
    }
}