<?php

class MainBolderfest {

   public function siteMenu() {

       $bolderfestMenu = array(
           '/web'             => 'Web',
           '/basisportal/web' => 'Basisportal',
           '/newportal/web/'  => 'Newportal',
           '/v1/'             => 'v1',
           '/gitlab/'         => 'My gitlab',
           '/USER_WEB/'       => 'USER_WEB',
           '/USER_WEB/martxa'  => 'Martxa',
           '/USER_WEB/onetech' => 'Onetech',
           '/API_DB_CONTROL_PANEL/web/' => 'DB',
       );

       $begetSitesMenu = array(
           'shop1.bolderp5.bget.ru' => array(
               'title' => 'Bolderfest',
               'submenu' => $bolderfestMenu,
           ),

           '300f.bolderp5.bget.ru'         => array('title' => '300f dir'  , 'submenu' => ''),
           'ionic.bolderp5.beget.tech'     => array('title' => 'Ionic dir' , 'submenu' => ''),
           'bolderp5.bget.ru'              => array('title' => 'Bolderp5'  , 'submenu' => ''),
           'symfony.bolderp5.beget.tech'   => array('title' => 'Symfony'   , 'submenu' => ''),
       );

       // getResponse($begetSitesMenu, 'error');

       return $begetSitesMenu;
   }

}