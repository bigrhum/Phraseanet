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

class Controller_Admin_Users implements ControllerProviderInterface
{

  public function connect(Application $app)
  {
    $appbox = appbox::get_instance();
    $session = $appbox->get_session();

    $controllers = new ControllerCollection();


    $controllers->post('/rights/', function() use ($app)
            {
              $request = $app['request'];
              $rights = new module_admin_route_users_edit($request);

              $template = 'admin/editusers.twig';
              $twig = new supertwig();
              $twig->addFilter(array('bas_name' => 'phrasea::bas_names'));
              $twig->addFilter(array('sbas_name' => 'phrasea::sbas_names'));
              $twig->addFilter(array('sbasFromBas' => 'phrasea::sbasFromBas'));
              $twig->addFilter(array('geoname_name_from_id' => 'geonames::name_from_id'));

              return $twig->render($template, $rights->get_users_rights());
            }
    );

    $controllers->get('/rights/', function() use ($app)
            {
              $request = $app['request'];
              $rights = new module_admin_route_users_edit($request);

              $template = 'admin/editusers.twig';
              $twig = new supertwig();
              $twig->addFilter(array('bas_name' => 'phrasea::bas_names'));
              $twig->addFilter(array('sbas_name' => 'phrasea::sbas_names'));
              $twig->addFilter(array('sbasFromBas' => 'phrasea::sbasFromBas'));
              $twig->addFilter(array('geoname_name_from_id' => 'geonames::name_from_id'));

              return $twig->render($template, $rights->get_users_rights());
            }
    );

    $controllers->post('/delete/', function() use ($app)
            {
              $request = $app['request'];



              $module = new module_admin_route_users_edit($request);
              $module->delete_users();

              return $app->redirect('/admin/users/search/');
            }
    );

    $controllers->post('/rights/apply/', function() use ($app)
            {
              $datas = array('error' => true);

              try
              {
                $request = $app['request'];
                $rights = new module_admin_route_users_edit($request);
                $rights->apply_rights();
                $rights->apply_infos();

                $datas = array('error' => false);
              }
              catch (Exception $e)
              {
                $datas['message'] = $e->getMessage();
              }

              return new Response(
                              p4string::jsonencode($datas)
                              , 200
                              , array('Content-Type' => 'application/json')
              );
            }
    );

    $controllers->post('/rights/quotas/', function() use ($app)
            {
              $request = $app['request'];
              $rights = new module_admin_route_users_edit($request);

              $template = 'admin/editusers_quotas.twig';
              $twig = new supertwig();
              $twig->addFilter(array('bas_name' => 'phrasea::bas_names'));
              $twig->addFilter(array('sbas_name' => 'phrasea::sbas_names'));
              $twig->addFilter(array('sbasFromBas' => 'phrasea::sbasFromBas'));

              return $twig->render($template, $rights->get_quotas());
            }
    );

    $controllers->post('/rights/quotas/apply/', function() use ($app)
            {
              $request = $app['request'];
              $rights = new module_admin_route_users_edit($request);
              $rights->apply_quotas();

              return;
            }
    );

    $controllers->post('/rights/time/', function() use ($app)
            {
              $request = $app['request'];
              $rights = new module_admin_route_users_edit($request);

              $template = 'admin/editusers_timelimit.twig';
              $twig = new supertwig();
              $twig->addFilter(array('bas_name' => 'phrasea::bas_names'));
              $twig->addFilter(array('sbas_name' => 'phrasea::sbas_names'));
              $twig->addFilter(array('sbasFromBas' => 'phrasea::sbasFromBas'));

              return $twig->render($template, $rights->get_time());
            }
    );

    $controllers->post('/rights/time/apply/', function() use ($app)
            {
              $request = $app['request'];
              $rights = new module_admin_route_users_edit($request);
              $rights->apply_time();

              return;
            }
    );

    $controllers->post('/rights/masks/', function() use ($app)
            {
              $request = $app['request'];
              $rights = new module_admin_route_users_edit($request);

              $template = 'admin/editusers_masks.twig';
              $twig = new supertwig();
              $twig->addFilter(array('bas_name' => 'phrasea::bas_names'));
              $twig->addFilter(array('sbas_name' => 'phrasea::sbas_names'));
              $twig->addFilter(array('sbasFromBas' => 'phrasea::sbasFromBas'));

              return $twig->render($template, $rights->get_masks());
            }
    );

    $controllers->post('/rights/masks/apply/', function() use ($app)
            {
              $request = $app['request'];
              $rights = new module_admin_route_users_edit($request);
              $rights->apply_masks();

              return;
            }
    );

    $controllers->match('/search/', function() use ($app)
            {
              $request = $app['request'];
              $users = new module_admin_route_users($request);
              $template = 'admin/users.html';

              $twig = new supertwig();
              $twig->addFilter(array('floor' => 'floor'));
              $twig->addFilter(array('getDate' => 'phraseadate::getDate'));

              return $twig->render($template, $users->search($request));
            }
    );

    $controllers->post('/apply_template/', function() use ($app)
            {
              $request = $app['request'];
              $users = new module_admin_route_users_edit($request);
              
              $users->apply_template();

              return new Symfony\Component\HttpFoundation\RedirectResponse('/admin/users/search/');
            }
    );

    $controllers->get('/typeahead/search/', function() use ($app, $appbox)
            {
              $request = $app['request'];
              $user_query = new User_Query($appbox);

              $user = User_Adapter::getInstance($appbox->get_session()->get_usr_id(), $appbox);
              $like_value = $request->get('term');
              $rights = $request->get('filter_rights') ? : array();
              $have_right = $request->get('have_right') ? : array();
              $have_not_right = $request->get('have_not_right') ? : array();
              $on_base = $request->get('on_base') ? : array();


              $elligible_users = $user_query->on_sbas_where_i_am($user->ACL(), $rights)
                              ->like(User_Query::LIKE_EMAIL, $like_value)
                              ->like(User_Query::LIKE_FIRSTNAME, $like_value)
                              ->like(User_Query::LIKE_LASTNAME, $like_value)
                              ->like(User_Query::LIKE_LOGIN, $like_value)
                              ->like_match(User_Query::LIKE_MATCH_OR)
                              ->who_have_right($have_right)
                              ->who_have_not_right($have_not_right)
                              ->on_base_ids($on_base)
                              ->execute()->get_results();

              $datas = array();

              foreach ($elligible_users as $user)
              {
                $datas[] = array(
                    'email' => $user->get_email() ? : ''
                    , 'login' => $user->get_login() ? : ''
                    , 'name' => $user->get_display_name() ? : ''
                    , 'id' => $user->get_id()
                );
              }

              return new Response(p4string::jsonencode($datas), 200, array('Content-type' => 'application/json'));
            });


    $controllers->post('/create/', function() use ($app)
            {

              $datas = array('error' => false, 'message' => '', 'data' => null);
              try
              {
                $request = $app['request'];
                $module = new module_admin_route_users($request);
                if ($request->get('template') == '1')
                {
                  $user = $module->create_template();
                }
                else
                {
                  $user = $module->create_newuser();
                }
                if (!($user instanceof User_Adapter))
                  throw new Exception('Unknown error');

                $datas['data'] = $user->get_id();
              }
              catch (Exception $e)
              {
                $datas['error'] = true;
                $datas['message'] = $e->getMessage();
              }

              return new Response(p4string::jsonencode($datas));
            }
    );

    $controllers->post('/export/csv/', function() use ($appbox, $app)
            {
              $request = $app['request'];
              $user_query = new User_Query($appbox);

              $user = User_Adapter::getInstance($appbox->get_session()->get_usr_id(), $appbox);
              $like_value = $request->get('like_value');
              $like_field = $request->get('like_field');
              $on_base = $request->get('base_id') ? : null;
              $on_sbas = $request->get('sbas_id') ? : null;

              $elligible_users = $user_query->on_bases_where_i_am($user->ACL(), array('canadmin'))
                      ->like($like_field, $like_value)
                      ->on_base_ids($on_base)
                      ->on_sbas_ids($on_sbas);

              $offset = 0;
              $geoname = new geonames();
              $buffer = array();

              $buffer[] = array(
                  'ID'
                  , 'Login'
                  , _('admin::compte-utilisateur nom')
                  , _('admin::compte-utilisateur prenom')
                  , _('admin::compte-utilisateur email')
                  , 'CreationDate'
                  , 'ModificationDate'
                  , _('admin::compte-utilisateur adresse')
                  , _('admin::compte-utilisateur ville')
                  , _('admin::compte-utilisateur code postal')
                  , _('admin::compte-utilisateur pays')
                  , _('admin::compte-utilisateur telephone')
                  , _('admin::compte-utilisateur fax')
                  , _('admin::compte-utilisateur poste')
                  , _('admin::compte-utilisateur societe')
                  , _('admin::compte-utilisateur activite')
              );
              do
              {
                $elligible_users->limit($offset, 20);
                $offset += 20;

                $results = $elligible_users->execute()->get_results();

                foreach ($results as $user)
                {
                  $buffer[] = array(
                      $user->get_id()
                      , $user->get_login()
                      , $user->get_lastname()
                      , $user->get_firstname()
                      , $user->get_email()
                      , phraseadate::format_mysql($user->get_creation_date())
                      , phraseadate::format_mysql($user->get_modification_date())
                      , $user->get_address()
                      , $user->get_city()
                      , $user->get_zipcode()
                      , $geoname->get_country($user->get_geonameid())
                      , $user->get_tel()
                      , $user->get_fax()
                      , $user->get_job()
                      , $user->get_company()
                      , $user->get_position()
                  );
                }
              }
              while (count($results) > 0);

              $out = format::arr_to_csv($buffer);

              $headers = array(
                  'Content-type' => 'text/csv'
                  , 'Content-Disposition' => 'attachment; filename=export.txt;'
              );
              $response = new Response($out, 200, $headers);
              $response->setCharset('UTF-8');

              return $response;
            }
    );

    return $controllers;
  }

}
