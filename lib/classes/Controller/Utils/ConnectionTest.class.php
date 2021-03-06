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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;

class Controller_Utils_ConnectionTest implements ControllerProviderInterface
{

  public function connect(Application $app)
  {
    $controllers = new ControllerCollection();

    $controllers->get('/mysql/', function() use ($app)
            {
              require_once dirname(__FILE__) . '/../../connection/pdo.class.php';

              $request = $app['request'];
              $hostname = $request->get('hostname', '127.0.0.1');
              $port = (int) $request->get('port', 3306);
              $user = $request->get('user');
              $password = $request->get('password');
              $dbname = $request->get('dbname');

              $connection_ok = $db_ok = $is_databox = $is_appbox = $empty = false;

              try
              {
                $conn = new connection_pdo('test', $hostname, $port, $user, $password);
                $connection_ok = true;
              }
              catch (Exception $e)
              {
                
              }

              if ($dbname && $connection_ok === true)
              {
                try
                {
                  $conn = new connection_pdo('test', $hostname, $port, $user, $password, $dbname);
                  $db_ok = true;

                  $sql = "SHOW TABLE STATUS";
                  $stmt = $conn->prepare($sql);
                  $stmt->execute();

                  $empty = $stmt->rowCount() === 0;

                  $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                  $stmt->closeCursor();

                  foreach ($rs as $row)
                  {
                    if ($row["Name"] === 'sitepreff')
                    {
                      $is_appbox = true;
                    }
                    if ($row["Name"] === 'pref')
                    {
                      $is_databox = true;
                    }
                  }
                }
                catch (Exception $e)
                {
                  
                }
              }

              return new Response(p4string::jsonencode(array(
                                  'connection' => $connection_ok
                                  , 'database' => $db_ok
                                  , 'is_empty' => $empty
                                  , 'is_appbox' => $is_appbox
                                  , 'is_databox' => $is_databox
                              )), 200, array('application/json'));
            });

    return $controllers;
  }

}

