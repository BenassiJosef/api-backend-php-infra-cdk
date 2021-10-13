<?php

namespace App\Package\Menu;

use App\Models\Menu;
use App\Package\Organisations\OrganizationProvider;
use Doctrine\ORM\EntityManager;
use Slim\Http\Request;
use Slim\Http\Response;

class MenuController
{

    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * @var OrganizationProvider $organizationProvider
     */
    private $organizationProvider;

    /**
     * MenuController constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(
        EntityManager $entityManager,
        OrganizationProvider $organizationProvider
    ) {
        $this->entityManager = $entityManager;
        $this->organizationProvider = $organizationProvider;
    }

    public function getMenuSitemap(Request $request, Response $response): Response
    {
        /**
         * @var Menu[] | null $items
         */
        $items = $this->entityManager->getRepository(Menu::class)->findAll();
        if (is_null($items)) {
            return $response->withStatus(404);
        }
        $returnItems = [];
        foreach ($items as $item) {
            $returnItems[] = $item->jsonSerializeSitemap();
        }

        return $response->withJson($returnItems, 200);
    }

    public function getMenuItems(Request $request, Response $response): Response
    {
        $organizationId = $request->getAttribute('orgId');
        /**
         * @var Menu[] | null $items
         */
        $items = $this
            ->entityManager
            ->getRepository(Menu::class)
            ->findBy(
                [
                    'organizationId' => $organizationId
                ]
            );
        $returnItems = [];
        foreach ($items as $item) {
            $returnItems[] = $item->jsonSerialize();
        }

        return $response->withJson($returnItems, 200);
    }

    public function getMenuItem(Request $request, Response $response): Response
    {
        $id = $request->getAttribute('id');
        $item = $this->getMenuFromPrettyId($id);
        if (is_null($item)) {
            return $response->withStatus(404);
        }

        return $response->withJson($item->jsonSerializeSitemap(), 200);
    }

    public function createMenuItem(Request $request, Response $response): Response
    {

        $organization = $this->organizationProvider->organizationForRequest($request);
        $prettyId = $request->getParsedBodyParam('pretty_id', null);
        $items = $request->getParsedBodyParam('items', []);
        if (is_null($prettyId)) {
            return $response->withJson('PRETTY_ID_MISSING', 400);
        }

        $existingItem = $this->getMenuFromPrettyId($prettyId);

        if (!is_null($existingItem)) {
            return $response->withJson('PRETTY_ID_TAKEN', 409);
        }

        $menu = new Menu($organization, $prettyId, $items);
        $menu->setIcon($icon);
        $this->entityManager->persist($menu);
        $this->entityManager->flush();
        return $response->withJson($menu->jsonSerialize(), 200);
    }

    public function updateMenuItem(Request $request, Response $response): Response
    {

        $id = $request->getAttribute('id');
        $prettyId = $request->getParsedBodyParam('pretty_id', null);
        $icon = $request->getParsedBodyParam('icon', null);
        $items = $request->getParsedBodyParam('items', []);
        if (is_null($id)) {
            return $response->withJson('ID_MISSING', 400);
        }

        /**
         * @var Menu | null $menu
         */
        $menu = $this->entityManager->getRepository(Menu::class)->find($id);
        if (is_null($menu)) {
            return $response->withJson('NOT_FOUND', 404);
        }
        if ($prettyId !== $menu->getPrettyId()) {
            $existingItem = $this->getMenuFromPrettyId($prettyId);
            if (!is_null($existingItem)) {
                return $response->withJson('PRETTY_ID_TAKEN', 409);
            }
            $menu->setPrettyId($prettyId);
        }

        $menu->setItems($items);
        $menu->setIcon($icon);
        $this->entityManager->persist($menu);
        $this->entityManager->flush();
        return $response->withJson($menu->jsonSerialize(), 200);
    }

    public function getMenuFromPrettyId(string $prettyId): ?Menu
    {
        return $this->entityManager->getRepository(Menu::class)->findOneBy(['prettyId' => $prettyId]);
    }

}
