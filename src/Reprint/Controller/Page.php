<?php

namespace Reprint\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class Page
{
  public function viewAction(Application $app, $template)
  {
    return $app['twig']->render('pages/' . $template . '.html.twig');
  }
}
