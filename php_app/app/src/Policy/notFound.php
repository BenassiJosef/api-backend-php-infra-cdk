<?php
/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 26/03/2017
 * Time: 19:19
 */

namespace App\Policy;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class notFound extends \Slim\Handlers\NotFound
{

    private $view;

    public function __construct(\Twig_Environment $view) {
        $this->view = $view;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {

        parent::__invoke($request, $response);
        $this->view->render($response, 'Frontend/404.twig');
        return $response->withStatus(404);

    }

}