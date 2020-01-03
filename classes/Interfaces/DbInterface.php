<?php

interface DbInterface {
    public function select($uery);
    public function exec($uery);
    public function connect();
}
