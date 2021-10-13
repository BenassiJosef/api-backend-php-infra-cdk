<?php


namespace App\Package\WebForms;

use App\Controllers\Billing\Subscription;
use App\Package\Organisations\OrganizationProvider;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Slim\Http\Request;
use Slim\Http\Response;
use App\Models\Forms\Forms;
use App\Package\Organisations\OrganizationService;

class Settings
{

	/**
	 * @var EntityManager
	 */
	protected $em;

	/**
	 * @var OrganizationProvider
	 */
	protected $organisationProvider;

	/**
	 * @var Subscription
	 */
	protected $subscription;

	public function __construct(EntityManager $em)
	{
		$this->em = $em;
		$this->organisationProvider = new OrganizationProvider($em);
		$this->subscription = new Subscription(new OrganizationService($this->em), $this->em);
	}

	public function getForms(Request $request, Response $response): Response
	{
		$organisation = $this->organisationProvider->organizationForRequest($request);
		$forms = $this->em->getRepository(Forms::class)->findBy([
			'organizationId' => $organisation->getId(),
			'deletedAt' => null
		]);

		if (!$forms) {
			return $response->withStatus(404);
		}

		$formsArray = [];
		foreach ($forms as $form) {
			$formsArray[] = $form->jsonSerialize();
		}

		$res = Http::status(200, $formsArray);

		return $response->withJson($res, $res['status']);
	}

	public function deleteForm(Request $request, Response $response): Response
	{
		$id = $request->getAttribute('id');
		$form = $this->getForm($id);
		if (!$form) {
			return $response->withStatus(404);
		}

		$form->setDeleted(true);
		$this->em->persist($form);
		$this->em->flush();

		return $response->withStatus(200);
	}

	public function getFormRequest(Request $request, Response $response): Response
	{
		$id = $request->getAttribute('id');
		if (is_null($id) || $id === 'undefined') {
			return $response->withStatus(404);
		}
		$form = $this->getForm($id);

		if (is_null($form)) {
			return $response->withStatus(404);
		}
		$form->setValidSubscription($this->subscription->hasValidSubscription(
			$form->getOrganizationId()->toString()
		));
		$res = Http::status(200, $form->jsonSerialize());

		return $response->withJson($res, $res['status']);
	}

	public function updateForm(Request $request, Response $response): Response
	{
		$id = $request->getAttribute('id');
		$body = $request->getParsedBody();
		$form = $this->getForm($id);
		if (!$form) {
			return $response->withStatus(404);
		}

		$form = $this->formUpdateBody($form, $body);

		$res = Http::status(200, $form->jsonSerialize());

		return $response->withJson($res, $res['status']);
	}

	public function createForm(Request $request, Response $response): Response
	{
		$organisation = $this->organisationProvider->organizationForRequest($request);
		$body = $request->getParsedBody();
		if (empty($body['name'])) {
			return $response->withStatus(403);
		}

		$form = new Forms($organisation, $body['name']);
		$form = $this->formUpdateBody($form, $body);

		$res = Http::status(200, $form->jsonSerialize());

		return $response->withJson($res, $res['status']);
	}

	public function formUpdateBody(Forms $form, array $body): Forms
	{

		if (!empty($body['name'])) {
			$form->setName($body['name']);
		}
		if (!empty($body['inputs'])) {
			$form->setInputs($body['inputs']);
		}
		if (!empty($body['serials'])) {
			$form->setSerials($body['serials']);
		}
		if (!empty($body['redirect'])) {
			$form->setRedirect($body['redirect']);
		}

		if (!empty($body['colour'])) {
			$form->setColour($body['colour']);
		}

		if (!empty($body['optText'])) {
			$form->setOptText($body['optText']);
		}

		$this->em->persist($form);
		$this->em->flush();

		return $form;
	}

	/**
	 * @param string $id
	 * @return Forms|null
	 */
	protected function getForm(string $id): ?Forms
	{
		return $this->em->getRepository(Forms::class)->findOneBy([
			'id' => $id,
			'deletedAt' => null
		]);
	}
}
