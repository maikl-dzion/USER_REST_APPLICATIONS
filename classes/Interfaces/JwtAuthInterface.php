<?php

interface JwtAuthInterface {
    public function encode($login, $password, $host);
    public function decode($token);
}