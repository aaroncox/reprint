<?php

namespace Reprint\Steem;

use Greymass\SteemPHP\RPC;

class Client
{

  protected $config;
  protected $client;

  public function __construct($config, $db = null)
  {
    $this->config = $config;
    $this->db = $db;
    $this->client = new RPC($this->getConfig('host'));
  }

  public function getConfig($key) {
    if(isset($this->config[$key])) {
      return $this->config[$key];
    }
    return null;
  }

  public function getContent($limit = 5, $skip = 0) {
    $accounts = $this->getConfig('accounts');
    $tags = $this->getConfig('tags');
    $content = array();
    foreach($accounts as $name => $data) {
      if(in_array('post', $data)) {
        $content = array_merge($content, $this->getPosts($name, $tags));
      }
      // if(in_array('reblog', $data)) {
      //   $content = array_merge($content, $this->getReblogs($name, $tags));
      // }
      // if(in_array('vote', $data)) {

      // }
    }
    // Sort the posts chronologically
    $content = $this->sortContent($content);
    // Slice to get our desired amount
    $content = array_slice($content, $skip, $limit);
    return $content;
  }

  public function getContentByTag($tag, $limit = 5, $skip = 0) {
    $accounts = $this->getConfig('accounts');
    $tags = [$tag];
    $content = array();
    foreach($accounts as $name => $data) {
      if(in_array('post', $data)) {
        $content = array_merge($content, $this->getPosts($name, $tags));
      }
      // if(in_array('reblog', $data)) {
      //   $content = array_merge($content, $this->getReblogs($name, $tags));
      // }
      // if(in_array('vote', $data)) {

      // }
    }
    // Sort the posts chronologically
    $content = $this->sortContent($content);
    // Slice to get our desired amount
    $content = array_slice($content, $skip, $limit);
    return $content;
  }

  public function sortContent($posts) {
    // Sort the posts by the new timestamp
    uasort($posts, function($a, $b) {
      if ($a->ts == $b->ts) {
        return 0;
      }
      return ($a->ts < $b->ts) ? 1 : -1;
    });
    return $posts;
  }

  private function cache($id, $content) {
    $sql = "SELECT * FROM content WHERE id = ?";
    $post = $this->db->fetchAssoc($sql, array($id));
    if(!$post) {
      $this->db->insert('content', array(
        'id' => $id,
        'time' => strtotime((string)$content['created']),
        'json' => json_encode($content)
      ));
    } else {
      $this->db->update('content', array(
        'json' => json_encode($content)
      ), array(
        'id' => $id
      ));
    }
  }

  public function getPost($author, $permlink)
  {
    return $this->client->get_content($author, $permlink);
  }

  public function getPosts($account, $tags = array(), $limit = 5, $skip = 0)
  {
    $posts = $this->getPostsFromAccount($account, $tags, $limit, $skip);
    // Sort the posts chronologically
    $posts = $this->sortContent($posts);
    // Slice to get our desired amount
    $posts = array_slice($posts, $skip, $limit);
    // Return our posts
    return $posts;
  }

  public function getPostsFromAccount($account, $tags, $limit, $skip)
  {
    $response = $this->client->get_posts($account, $limit);
    $return = [];
    foreach($response as $data) {
      if(empty($tags) || in_array($data->category, $tags) || ($data->json_metadata && count(array_intersect($tags, $data->json_metadata['tags'])) > 0)) {
        $return[] = $data;
      }
    }
    return $return;
  }

  public function getReblogs($account, $tags)
  {
    // Temporary solution using steemdb until we get proper APIs
    $url = sprintf('https://steemdb.com/api/account/%s/contentreblog', $account);
    $json = json_decode(file_get_contents($url), true);
    $response = array();
    // Local Storage
    // $db = new Database('../var/cache/database/reblog');
    foreach($json as $reblog) {
      // Add the content to our response
      // $response[] = $content = new Comment($reblog['content'][0]);
      // Check to see if it exists in the local database
      // $this->cache($content['_id'], $content);
    }
    return $response;
  }

}
