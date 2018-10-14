<?php
if(!($is_cli = php_sapi_name() === 'cli')) echo '<pre>';

require_once __DIR__ . '/../autoload.php';

use hugopakula\SimpleDB;
SimpleDB\SQL::loadDefaultCredentials(__DIR__ . '/db_credentials.json');

// Open a new connection to our default database
$SQL = new SimpleDB\SQL();

// Query for the posts, newest first
$posts = $SQL->query('SELECT users.full_name AS poster_name, posts.id, posts.user_id, posts.`text`, posts.created_at FROM posts LEFT OUTER JOIN users ON posts.user_id = users.id ORDER BY posts.created_at DESC');

foreach($posts->getResult() as $post) {
    echo 'Post by <a href="/user/' . $post['user_id'] . '"><strong>' . $post['poster_name'] . '</strong></a>:'
        . "\r\n" . '<small>(' . $post['created_at'] . ')</small>' . "\r\n\r\n";
    echo $post['text'];
    echo '<hr />';
}

if(!$is_cli) echo '</pre>';