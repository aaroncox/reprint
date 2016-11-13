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
    $skip = $perPage * ($page - 1);
    return $app['twig']->render('index.html.twig', array(
      'page' => $page,
      'posts' => $app['steemd']->getContent($perPage, $skip)
    ));
  }
}
