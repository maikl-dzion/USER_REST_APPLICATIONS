<?php

$post = getPostData();

if(!empty($post)) {
    $result = array(
        'userName'  => $post['user'],
        'userLogin' => $post['login'],
        'server'    => $_SERVER
    );
} else {
    $result = array(
        'userName'  => '4566',
        'userLogin' => 'dfghjj',
        'server'    => $_SERVER
    );
}