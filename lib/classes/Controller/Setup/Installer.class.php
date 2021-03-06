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

date_default_timezone_set('Europe/Berlin');
require_once dirname(__FILE__) . '/../../../version.inc';
require_once dirname(__FILE__) . '/../../phrasea.class.php';
require_once dirname(__FILE__) . '/../../bootstrap.class.php';
require_once dirname(__FILE__) . '/../../cache/cacheableInterface.class.php';
require_once dirname(__FILE__) . '/../../cache/interface.class.php';
require_once dirname(__FILE__) . '/../../cache/nocache.class.php';
require_once dirname(__FILE__) . '/../../cache/adapter.class.php';
require_once dirname(__FILE__) . '/../../User/Interface.class.php';
require_once dirname(__FILE__) . '/../../User/Adapter.class.php';

bootstrap::register_autoloads();
bootstrap::set_php_configuration();

class Controller_Setup_Installer implements ControllerProviderInterface
{

  public function connect(Application $app)
  {
    $controllers = new ControllerCollection();

    $app['available_languages'] = User_Adapter::detectLanguage(new Setup_Registry());

    $controllers->get('/', function() use ($app)
            {
              $request = $app['request'];
              $servername = $request->getScheme() . '://' . $request->getHttpHost() . '/';
              setup::write_config($servername);
              
              
              $php_constraint = setup::check_php_version();
              $writability_constraints = setup::check_writability(new Setup_Registry());
              $extension_constraints = setup::check_php_extension();
              $opcode_constraints = setup::check_cache_opcode();
              $php_conf_constraints = setup::check_php_configuration();
              $locales_constraints = setup::check_system_locales();

              $constraints_coll = array(
                  'php_constraint' => $php_constraint
                  , 'writability_constraints' => $writability_constraints
                  , 'extension_constraints' => $extension_constraints
                  , 'opcode_constraints' => $opcode_constraints
                  , 'php_conf_constraints' => $php_conf_constraints
                  , 'locales_constraints' => $locales_constraints
              );
              $redirect = true;

              foreach ($constraints_coll as $key => $constraints)
              {
                $unset = true;
                foreach ($constraints as $constraint)
                {
                  if (!$constraint->is_ok() && $constraint->is_blocker())
                    $redirect = $unset = false;
                }
                if ($unset === true)
                {
                  unset($constraints_coll[$key]);
                }
              }

              if ($redirect)
              {
                return $app->redirect('/setup/installer/step2/');
              }

              
              $ld_path = array(dirname(__FILE__) . '/../../../../templates/web');
              $loader = new Twig_Loader_Filesystem($ld_path);
              $twig = new Twig_Environment($loader);
              
              $html = $twig->render(
                      '/setup/index.twig'
                      , array_merge($constraints_coll, array(
                          'locale' => Session_Handler::get_locale()
                          , 'available_locales' => $app['available_languages']
                          , 'version_number' => GV_version
                          , 'version_name' => GV_version_name
                          , 'current_servername' => $request->getScheme() . '://' . $request->getHttpHost() . '/'
                      ))
              );

              return new Response($html);
            });

    $controllers->get('/step2/', function() use ($app)
            {
              phrasea::use_i18n(Session_Handler::get_locale());
              
              $ld_path = array(dirname(__FILE__) . '/../../../../templates/web');
              
              $loader = new Twig_Loader_Filesystem($ld_path);
              $twig = new Twig_Environment($loader);
              
              $twig->addExtension(new Twig_Extensions_Extension_I18n());

              $request = $app['request'];

              $warnings = array();
              if ($request->getScheme() == 'http')
              {
                $warnings[] = _('It is not recommended to install Phraseanet without HTTPS support');
              }
              $html = $twig->render(
                      '/setup/step2.twig'
                      , array(
                  'locale' => Session_Handler::get_locale()
                  , 'available_locales' => $app['available_languages']
                  , 'available_templates' => appbox::list_databox_templates()
                  , 'version_number' => GV_version
                  , 'version_name' => GV_version_name
                  , 'warnings' => $warnings
                  , 'current_servername' => $request->getScheme() . '://' . $request->getHttpHost() . '/'
                  , 'discovered_binaries' => setup::discover_binaries()
                  , 'rootpath' => dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/'
                      )
              );

              return new Response($html);
            });

    $controllers->post('/install/', function() use ($app)
            {
              set_time_limit(360);
              phrasea::use_i18n(Session_Handler::get_locale());
              $request = $app['request'];

              $conn = $connbas = null;

              $hostname = $request->get('ab_hostname');
              $port = $request->get('ab_port');
              $user_ab = $request->get('ab_user');
              $password = $request->get('ab_password');

              $appbox_name = $request->get('ab_name');
              $databox_name = $request->get('db_name');

              try
              {
                $conn = new connection_pdo('appbox', $hostname, $port, $user_ab, $password, $appbox_name);
              }
              catch (Exception $e)
              {
                return $app->redirect('/setup/installer/step2/?error=' . _('Appbox is unreachable'));
              }

              try
              {
                if ($databox_name)
                {
                  $connbas = new connection_pdo('databox', $hostname, $port, $user_ab, $password, $databox_name);
                }
              }
              catch (Exception $e)
              {
                return $app->redirect('/setup/installer/step2/?error=' . _('Databox is unreachable'));
              }
              setup::rollback($conn, $connbas);

              try
              {
                $appbox = appbox::create(new Setup_Registry(), $conn, $appbox_name, true);


                $registry = registry::get_instance();
                setup::create_global_values($registry);

                $appbox->set_registry($registry);

                $registry->set('GV_base_datapath_noweb', p4string::addEndSlash($request->get('datapath_noweb')));
                $registry->set('GV_base_datapath_web', p4string::addEndSlash($request->get('datapath_web')));
                $registry->set('GV_base_dataurl', p4string::addEndSlash($request->get('mount_point_web')));

                $registry->set('GV_cli', $request->get('binary_php'));
                $registry->set('GV_imagick', $request->get('binary_convert'));
                $registry->set('GV_pathcomposite', $request->get('binary_composite'));
                $registry->set('GV_exiftool', $request->get('binary_exiftool'));
                $registry->set('GV_swf_extract', $request->get('binary_swfextract'));
                $registry->set('GV_pdf2swf', $request->get('binary_pdf2swf'));
                $registry->set('GV_swf_render', $request->get('binary_swfrender'));
                $registry->set('GV_unoconv', $request->get('binary_unoconv'));
                $registry->set('GV_ffmpeg', $request->get('binary_ffmpeg'));
                $registry->set('GV_mp4box', $request->get('binary_MP4Box'));
                $registry->set('GV_mplayer', $request->get('binary_mplayer'));
                $registry->set('GV_pdftotext', $request->get('binary_xpdf'));

                $user = User_Adapter::create($appbox, $request->get('email'), $request->get('password'), $request->get('email'), true);

                if (!p4string::hasAccent($databox_name))
                {
                  if ($databox_name)
                  {

                    $template = new system_file(dirname(__FILE__) . '/../../../conf.d/data_templates/' . $request->get('db_template') . '.xml');
                    $databox = databox::create($appbox, $connbas, $template, $registry);
                    $user->ACL()
                            ->give_access_to_sbas(array($databox->get_sbas_id()))
                            ->update_rights_to_sbas(
                                    $databox->get_sbas_id(), array(
                                'bas_manage' => 1, 'bas_modify_struct' => 1,
                                'bas_modif_th' => 1, 'bas_chupub' => 1
                                    )
                    );

                    $a = collection::create($databox, $appbox, 'test', $user);

                    $user->ACL()->give_access_to_base(array($a->get_base_id()));
                    $user->ACL()->update_rights_to_base($a->get_base_id(), array(
                        'canpush' => 1, 'cancmd' => 1
                        , 'canputinalbum' => 1, 'candwnldhd' => 1, 'candwnldpreview' => 1, 'canadmin' => 1
                        , 'actif' => 1, 'canreport' => 1, 'canaddrecord' => 1, 'canmodifrecord' => 1
                        , 'candeleterecord' => 1, 'chgstatus' => 1, 'imgtools' => 1, 'manage' => 1
                        , 'modify_struct' => 1, 'nowatermark' => 1
                            )
                    );

                    $tasks = $request->get('create_task', array());
                    foreach ($tasks as $task)
                    {
                      switch ($task)
                      {
                        case 'cindexer';
                        case 'subdef';
                        case 'writemeta';
                          $class_name = sprintf('task_period_%s', $task);
                          if ($task === 'cindexer')
                          {
                            $credentials = $databox->get_connection()->get_credentials();

                            $host = $credentials['hostname'];
                            $port = $credentials['port'];
                            $user_ab = $credentials['user'];
                            $password = $credentials['password'];

                            $settings = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<tasksettings>\n<binpath>"
                                    . str_replace('/phraseanet_indexer', '', $request->get('binary_phraseanet_indexer'))
                                    . "</binpath><host>" . $host . "</host><port>"
                                    . $port . "</port><base>"
                                    . $appbox_name . "</base><user>"
                                    . $user_ab . "</user><password>"
                                    . $password . "</password><socket>25200</socket>"
                                    . "<use_sbas>1</use_sbas><nolog>0</nolog><clng></clng>"
                                    . "<winsvc_run>0</winsvc_run><charset>utf8</charset></tasksettings>";
                          }
                          else
                          {
                            $settings = null;
                          }

                          task_abstract::create($appbox, $class_name, $settings);
                          break;
                        default:
                          break;
                      }
                    }
                  }
                }

                phrasea::start();

                $auth = new Session_Authentication_None($user);

                $appbox->get_session()->authenticate($auth);

                $redirection = '/admin/?section=taskmanager&notice=install_success';

                return $app->redirect($redirection);
              }
              catch (Exception $e)
              {
                setup::rollback($conn, $connbas);
                exit($e->getMessage() . ' ' . $e->getFile() . ' ' . $e->getLine());
              }

              return $app->redirect('/setup/installer/step2/?error=' . sprintf(_('an error occured : %s'), $e->getMessage()));
            });

    return $controllers;
  }

}