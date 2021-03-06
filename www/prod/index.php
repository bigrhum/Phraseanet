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
require_once dirname(__FILE__) . "/../../lib/bootstrap.php";
User_Adapter::updateClientInfos(1);
phrasea::headers(200, false, 'text/html', 'UTF-8', false);

try
{
  //listage des css
  $appbox = appbox::get_instance();
  $css = array();
  $cssfile = false;
  $registry = $appbox->get_registry();

  $session = $appbox->get_session();
  $user = User_Adapter::getInstance($session->get_usr_id(), $appbox);

  $cssPath = $registry->get('GV_RootPath') . 'www/skins/prod/';

  if ($hdir = opendir($cssPath))
  {
    while (false !== ($file = readdir($hdir)))
    {
      if (substr($file, 0, 1) == "." || mb_strtolower($file) == "cvs")
        continue;
      if (is_dir($cssPath . $file))
      {
        $css[$file] = $file;
      }
    }
    closedir($hdir);
  }

  $cssfile = $user->getPrefs('css');
  if (!$cssfile && isset($css['000000']))
    $cssfile = '000000';


  $user_feeds = Feed_Collection::load_all($appbox, $user);
  $feeds = array_merge(array($user_feeds->get_aggregate()), $user_feeds->get_feeds());

  $srt = 'name asc';


  $thjslist = "";

  $queries_topics = '';
  if ($registry->get('GV_client_render_topics') == 'popups')
    $queries_topics = queries::dropdown_topics();
  elseif ($registry->get('GV_client_render_topics') == 'tree')
    $queries_topics = queries::tree_topics();

  $module_prod = new module_prod();

  $sbas = $bas2sbas = array();
  foreach ($appbox->get_databoxes() as $databox)
  {
    $sbas_id = $databox->get_sbas_id();

    $sbas['s' + $sbas_id] = array(
        'sbid' => $sbas_id,
        'seeker' => null);

    foreach ($databox->get_collections() as $coll)
    {
      $bas2sbas['b' . $coll->get_base_id()] = array(
          'sbid' => $sbas_id,
          'ckobj' => array('checked' => false),
          'waschecked' => false
      );
    }
  }
  $session->close_storage();
}
catch (Exception $e)
{

}

try
{

  $twig = new supertwig();
  $twig->addFilter(array('get_collection_logo' => 'collection::getLogo'));
  $twig->addFilter(array('sbas_names' => 'phrasea::sbas_names'));
  $twig->addFilter(array('bas_names' => 'phrasea::bas_names'));
  $twig->addFilter(array('implode' => 'implode'));
  $twig->addFilter(array('array_keys' => 'array_keys'));
  $twig->display('prod/index.html', array(
      'module_name' => 'Production',
      'cssfile' => $cssfile,
      'module' => 'prod',
      'events' => eventsmanager_broker::getInstance($appbox),
      'GV_defaultQuery_type' => $registry->get('GV_defaultQuery_type'),
      'GV_multiAndReport' => $registry->get('GV_multiAndReport'),
      'GV_thesaurus' => $registry->get('GV_thesaurus'),
      'basket_collection' => new basketCollection($appbox, $user->get_id(), $srt),
      'cgus_agreement' => databox_cgu::askAgreement(),
      'css' => $css,
      'feeds' => $feeds,
      'GV_google_api' => $registry->get('GV_google_api'),
      'queries_topics' => $queries_topics,
      'module_prod' => $module_prod,
      'search_status' => databox_status::getSearchStatus(),
      'queries_history' => queries::history(),
      'thesau_js_list' => $thjslist,
      'thesau_json_sbas' => p4string::jsonencode($sbas),
      'thesau_json_bas2sbas' => p4string::jsonencode($bas2sbas),
      'thesau_languages' => User_Adapter::avLanguages(),
      'GV_bitly_user' => $registry->get('GV_bitly_user'),
      'GV_bitly_key' => $registry->get('GV_bitly_key')
  ));
}
catch (Exception $e)
{
  echo $e->getMessage();
}
