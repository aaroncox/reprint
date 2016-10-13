<?php

namespace Reprint\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class Home
{
  public function homeAction(Application $app)
  {
    return $app['twig']->render('index.html.twig', array(
      'posts' => $app['steemd']->getContent()
    ));
  }
}
