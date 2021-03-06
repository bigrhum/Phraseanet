<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * 
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
require_once dirname(__FILE__) . '/PhraseanetPHPUnitAbstract.class.inc';

use Silex\WebTestCase;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\HttpFoundation\Response;

abstract class PhraseanetPHPUnitAuthenticatedAbstract extends PhraseanetPHPUnitAbstract
{

  public  function setUp()
  {
    parent::setUp();
    $appbox = appbox::get_instance();
    $session = $appbox->get_session();
    $auth = new Session_Authentication_None(self::$user);
    $session->authenticate($auth);
  }

  public  function tearDown()
  {
    $appbox = appbox::get_instance();
    $session = $appbox->get_session();
    $session->logout();
    parent::tearDown();
  }
  
//  public static function setUpBeforeClass()
//  {
//    parent::setUpBeforeClass();
//    $session = Session_Handler::getInstance();
//    $session->authenticate(self::$user->get_id());
//  }
//
//  public static function tearDownAfterClass()
//  {
//    $session = Session_Handler::getInstance();
//    $session->logout();
//    parent::tearDownAfterClass();
//  }

}