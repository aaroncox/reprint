<?php

namespace Reprint\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class Blog
{
  public function postAction(Application $app, $category, $author, $permlink)
  {
    return $app['twig']->render('post.html.twig', array(
      'username' => $author,
      'post' => $app['steemd']->getPost($author, $permlink)
    ));
  }
  public function listAction(Application $app, $category = null)
  {
    return $app['twig']->render('list.html.twig', array(
      'category' => $category,
      'posts' => $app['steemd']->getContentByTag($category)
    ));
  }
}
