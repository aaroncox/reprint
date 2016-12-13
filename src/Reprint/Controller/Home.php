<?php

namespace Reprint\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class Home
{
  public function homeAction(Application $app, Request $request)
  {
    $page = $request->query->get('page', 1);
    $perPage = 5;
    $response = $app['steemd']->getContent(null, $perPage, $page);
    return $app['twig']->render('index.html.twig', array(
      'page' => $response['page'],
      'pages' => $response['pages'],
      'total' => $response['total'],
      'perPage' => $response['perPage'],
      'posts' => $response['content'],
      'recent' => array_slice($response['results'], 0, 5),
      'categories' => $response['categories'],
    ));
  }
}
