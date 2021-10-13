<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 10/10/2017
 * Time: 14:26
 */

namespace App\Controllers\Schedule;

use App\Controllers\Billing\Quotes\_QuotesController;
use App\Models\PartnerQuotes;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Slim\Http\Response;
use Slim\Http\Request;

class QuoteScheduler
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function runRoute(Request $request, Response $response)
    {
        $queryParams = $request->getQueryParams();
        $offset      = 0;
        if (isset($queryParams['offset'])) {
            $offset = $queryParams['offset'];
        }

        $send = $this->run($offset);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function run($offset)
    {
        $getQuotes = $this->em->createQueryBuilder()
            ->select('u.id, u.body, u.sendReference')
            ->from(PartnerQuotes::class, 'u')
            ->where('u.completed = false')
            ->andWhere('u.declined = false')
            ->andWhere('u.accepted = false')
            ->andWhere('u.sent = true')
            ->andWhere('u.sendQuoteAuto = true')
            ->setFirstResult($offset)
            ->setMaxResults(10);

        $results = new Paginator($getQuotes);
        $results->setUseOutputWalkers(false);

        $getQuotes = $results->getIterator()->getArrayCopy();

        if (empty($getQuotes)) {
            return Http::status(200);
        }

        $return = [
            'has_more'    => false,
            'total'       => count($results),
            'next_offset' => $offset + 10
        ];

        if ($offset <= $return['total'] && count($getQuotes) !== $return['total']) {
            $return['has_more'] = true;
        }

        $newQuotesController = new _QuotesController($this->em);

        foreach ($getQuotes as $key => $quote) {
            $newQuotesController->sendQuote($quote['id'], [
                'body'          => $quote['body'],
                'sendReference' => $quote['sendReference']
            ]);
        }


        return Http::status(200, $return);
    }
}
