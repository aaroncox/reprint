<?php

namespace Reprint\Steem;

use JsonRPC\Client as RpcClient;
use JsonRPC\HttpClient;

use Reprint\Model\Comment;

class Client
{

  protected $config;
  protected $client;

  public function __construct($config, $db = null)
  {
    $this->config = $config;
    $this->db = $db;
    $httpClient = new HttpClient($this->getConfig('host'));
    $httpClient->withoutSslVerification();
    $this->client = new RpcClient($this->getConfig('host'), false, $httpClient);
  }

  public function getConfig($key) {
    if(isset($this->config[$key])) {
      return $this->config[$key];
    }
    return null;
  }

  public function getPost($username, $permlink) {
    $client = $this->client;
    $response = $client->get_content($username, $permlink);
    $post = new Comment($response);
    return $post;
  }

  protected function amendPosts($posts, $tags = []) {
    $return = [];
    foreach($posts as $data) {
      $post = new Comment($data);
      if(empty($tags) || in_array($post->category, $tags) || ($post->json_metadata && count(array_intersect($tags, $post->json_metadata['tags'])) > 0)) {
        $return[] = $post;
      }
    }
    return $return;
  }

  public function getPostsFromAccount($account, $tags, $limit, $skip)
  {
    $client = $this->client;
    $response = $client->get_state('@' . $account);
    return $this->amendPosts($response['content'], $tags);
  }

  public function sortPosts($posts) {
    // Sort the posts by the new timestamp
    uasort($posts, function($a, $b) {
      if ($a->ts == $b->ts) {
        return 0;
      }
      return ($a->ts < $b->ts) ? 1 : -1;
    });
    return $posts;
  }

  public function getContent() {
    $accounts = $this->getConfig('accounts');
    $tags = $this->getConfig('tags');
    $posts = array();
    foreach($accounts as $name => $data) {
      if(in_array('post', $data)) {
        $posts = array_merge($posts, $this->getPosts($name, $tags));
      }
      if(in_array('reblog', $data)) {
        $posts = array_merge($posts, $this->getReblogs($name, $tags));
      }
      if(in_array('vote', $data)) {

      }
    }
    return $posts;
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
      $response[] = $content = new Comment($reblog['content'][0]);
      // Check to see if it exists in the local database
      // $this->cache($content['_id'], $content);
    }
    return $response;
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

  public function getPosts($account, $tags = array(), $limit = 5, $skip = 0)
  {
    $posts = $this->getPostsFromAccount($account, $tags, $limit, $skip);
    // Sort these posts by timestamp
    $posts = $this->sortPosts($posts);
    // Slice to get our desired amount
    $posts = array_slice($posts, $skip, $limit);
    // Return our posts
    return $posts;
  }

  public function getApi($name)
  {
    return $this->client->call(1, 'get_api_by_name', [$name]);
  }

  public function getFollowing($username, $limit = 100, $skip = -1)
  {
    // Load the appropriate API
    $api = $this->getApi('follow_api');
    // Get our followers
    return $this->client->call($api, 'get_following', [$username, $skip, $limit]);;
  }
}
