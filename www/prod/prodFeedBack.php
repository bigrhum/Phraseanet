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
$appbox = appbox::get_instance();
$session = $appbox->get_session();
$registry = $appbox->get_registry();

$usr_id = $session->get_usr_id();
$user = User_Adapter::getInstance($usr_id, $appbox);

$output = '';

$request = http_request::getInstance();
$parm = $request->get_parms('action');

$action = $parm['action'];

switch ($action)
{
  case 'search':
    $session->close_storage();
    $session->close_storage();
    $engine = new searchEngine_adapter_sphinx_engine();

    $parm = $request->get_parms("bas", "term"
                    , "stemme"
                    , "search_type", "recordtype", "status", "fields", "datemin", "datemax", "datefield");

    $options = new searchEngine_options();

    $options->set_bases($parm['bas'], $user->ACL());
    if (!is_array($parm['fields']))
      $parm['fields'] = array();
    $options->set_fields($parm['fields']);
    if (!is_array($parm['status']))
      $parm['status'] = array();
    $options->set_status($parm['status']);
    $options->set_search_type($parm['search_type']);
    $options->set_record_type($parm['recordtype']);
    $options->set_min_date($parm['datemin']);
    $options->set_max_date($parm['datemax']);
    $options->set_date_fields(explode('|', $parm['datefield']));
    $options->set_use_stemming($parm['stemme']);


    $engine->set_options($options);
    $result = $engine->results($parm['term'], 0, 1);
    $res = $engine->get_suggestions($session, true);
//    $res = array(array('id'=>'oui','label'=>'oui','value'=>'oui'));
    $output = p4string::jsonencode($res);

    break;

  case 'LANGUAGE':
    $session->close_storage();
    require ($registry->get('GV_RootPath') . 'lib/classes/deprecated/prodUtils.php');
    $module_prod = new module_prod();
    $output = $module_prod->getLanguage();
    break;
  case 'CSS':
    require ($registry->get('GV_RootPath') . 'lib/classes/deprecated/prodUtils.php');
    $parm = $request->get_parms('color');
    $output = $user->setPrefs('css', $color);
    break;
  case 'MYRSS':
    require ($registry->get('GV_RootPath') . 'lib/classes/deprecated/prodUtils.php');
    $parm = $request->get_parms('renew');

    $url = $user->get_protected_rss_url(($parm['renew'] === 'true'))->get_url();

    $output = p4string::jsonencode(
                    array(
                        'texte' => '<p>' . _('publication::Voici votre fil RSS personnel. Il vous permettra d\'etre tenu au courrant des publications.')
                        . '</p><p>' . _('publications::Ne le partagez pas, il est strictement confidentiel') . '</p>
                <div><input type="text" style="width:100%" value="' . $url . '"/></div>',
                        'titre' => _('publications::votre rss personnel')
                    )
    );
    break;

  case 'SAVEPREF':
    $parm = $request->get_parms('prop', 'value');
    $ret = $user->setPrefs($parm['prop'], $parm['value']);
    if (isset($ret[$parm['prop']]) && $ret[$parm['prop']] = $parm['value'])
      $output = "1";
    else
      $output = "0";
    break;

  case 'SAVETEMPPREF':
    $parm = $request->get_parms('prop', 'value');
    $session->set_session_prefs($parm['prop'], $parm['value']);
    $output = 1;
    break;

  case 'BASKETS':
    require ($registry->get('GV_RootPath') . 'lib/classes/deprecated/prodUtils.php');
    $parm = $request->get_parms('id', 'sort');
    $baskets = new basketCollection($appbox, $usr_id);

    $twig = new supertwig();
    $twig->addFilter(array('get_collection_logo' => 'collection::getLogo'));

    $output = $twig->render('prod/baskets.html', array(
                'basket_collection' => $baskets,
                'selected_ssel' => $parm['id'],
                'srt' => $parm['sort']
                    )
    );
    break;
  case 'BASKETNAME':
    require ($registry->get('GV_RootPath') . 'lib/classes/deprecated/prodUtils.php');
    $parm = $request->get_parms('ssel_id');
    $basket = basket_adapter::getInstance($appbox, $parm['ssel_id'], $usr_id);
    $output = p4string::jsonencode(array('name' => $basket->get_name(), 'description' => $basket->get_description()));
    break;
  case 'BASKETRENAME':
    require ($registry->get('GV_RootPath') . 'lib/classes/deprecated/prodUtils.php');
    $parm = $request->get_parms('ssel_id', 'name', 'description');
    $basket = basket_adapter::getInstance($appbox, $parm['ssel_id'], $usr_id);
    $basket->set_name($parm['name']);
    $basket->set_description($parm['description']);
//    $output = $basket->save();
    break;

  case 'GETBASKET':
    require ($registry->get('GV_RootPath') . 'lib/classes/deprecated/prodUtils.php');

    $twig = new supertwig();
    $twig->addFilter(array('nl2br' => 'nl2br'));

    $parm = $request->get_parms('id', 'ord');

    $basket = basket_adapter::getInstance($appbox, $parm['id'], $usr_id);
    $basket->set_read();

    $order = $parm['ord'];

    if (trim($order) == '' || !in_array($order, array('asc', 'desc', 'nat')))
      $order = $user->getPrefs('bask_val_order');
    else
      $user->setPrefs('bask_val_order', $order);

    $basket->sort($order);

    $output = p4string::jsonencode(array('content' => $twig->render('prod/basket.twig', array('basket' => $basket, 'ordre' => $order))));
    break;

  case 'DELETE':
    require ($registry->get('GV_RootPath') . 'lib/classes/deprecated/prodUtils.php');
    $parm = $request->get_parms('lst');
    $output = whatCanIDelete($parm['lst']);
    break;
  case 'DODELETE':
    require ($registry->get('GV_RootPath') . 'lib/classes/deprecated/prodUtils.php');
    $parm = $request->get_parms('lst', 'del_children');
    $output = deleteRecord($parm['lst'], $parm['del_children']);
    break;

  case 'MAIL_REQ':
    $parm = $request->get_parms('user', 'contrib', 'message', 'query');
    $output = query_phrasea::mail_request($parm['user'], $parm['contrib'], $parm['message'], $parm['query']);
    break;


  case 'REORDER_DATAS':
    $parm = $request->get_parms('ssel_id');
    $basket = basket_adapter::getInstance($appbox, $parm['ssel_id'], $usr_id);
    $output = $basket->getOrderDatas();
    break;
  case 'SAVE_ORDER_DATAS':
    $parm = $request->get_parms('ssel_id', 'value');
    $basket = basket_adapter::getInstance($appbox, $parm['ssel_id'], $usr_id);
    $output = $basket->saveOrderDatas($parm['value']);
    break;


  case 'DENY_CGU':
    $parm = $request->get_parms('sbas_id');
    $output = databox_cgu::denyCgus($parm['sbas_id']);
    break;
  case 'READ_NOTIFICATIONS':
    try
    {
      $evt_mngr = eventsmanager_broker::getInstance($appbox);
      $parm = $request->get_parms('notifications');
      $output = $evt_mngr->read(explode('_', $parm['notifications']), $session->get_usr_id());
      $output = p4string::jsonencode(array('error' => false, 'message' => ''));
    }
    catch (Exception $e)
    {
      $output = p4string::jsonencode(array('error' => true, 'message' => $e->getMessage()));
    }
    break;
  case 'NOTIFICATIONS_FULL':
    $evt_mngr = eventsmanager_broker::getInstance($appbox);
    $parm = $request->get_parms('page');
    $output = $evt_mngr->get_json_notifications($parm['page']);
    break;





  case 'VIDEOTOKEN':
    $parm = $request->get_parms('sbas_id', 'record_id');
    $sbas_id = (int) $parm['sbas_id'];
    $record = new record_adapter($sbas_id, $parm['record_id']);

    $output = p4string::jsonencode(array('url' => $record->get_preview()->renew_url()));
    break;



  case 'ANSWERTRAIN':
    $parm = $request->get_parms('pos', 'record_id', 'options_serial', 'query');

    $search_engine = null;
    if (($options = unserialize($parm['options_serial'])) !== false)
    {
      $search_engine = new searchEngine_adapter($registry);
      $search_engine->set_options($options);
    }

    $record = new record_preview('RESULT', $parm['pos'], '', '', $search_engine, $parm['query']);
    $records = $record->get_train($parm['pos'], $parm['query'], $search_engine);
    $twig = new supertwig();
    $output = p4string::jsonencode(
                    array('current' =>
                        $twig->render(
                                'prod/preview/result_train.html', array(
                            'records' => $records
                            , 'selected' => $parm['pos'])
                        )
                    )
    );
    break;


  case 'REGTRAIN':
    $parm = $request->get_parms('cont', 'pos');
    $record = new record_preview('REG', $parm['pos'], $parm['cont']);
    $output = $twig->render('prod/preview/reg_train.html', array('container_records' => $record->get_container()->get_children(),
                'record' => $record, 'GV_rollover_reg_preview' => $registry->get('GV_rollover_reg_preview')));
    break;
  case 'UNFIX':
    $parm = $request->get_parms('lst');
    $output = basket_adapter::unfix_grouping($parm['lst']);
    break;
  case 'FIX':
    $parm = $request->get_parms('lst');
    $output = basket_adapter::fix_grouping($parm['lst']);
    break;
  case 'ADDIMGT2CHU':
  case 'ADDCHU2CHU':
  case 'ADDREG2CHU':
    $parm = $request->get_parms('dest', 'lst');
    $basket = basket_adapter::getInstance($appbox, $parm['dest'], $usr_id);
    $output = p4string::jsonencode($basket->push_list($parm['lst'], false));

    break;
  case 'ADDIMGT2REG':
  case 'ADDCHU2REG':
  case 'ADDREG2REG':
    $parm = $request->get_parms('dest', 'lst');
    $basket = basket_adapter::getInstance($appbox, $parm['dest'], $usr_id);
    $output = p4string::jsonencode($basket->push_list($parm['lst'], false));
    break;
  case 'DELFROMBASK':
    $parm = $request->get_parms('ssel_id', 'sselcont_id');
    $basket = basket_adapter::getInstance($appbox, $parm['ssel_id'], $usr_id);
    $output = p4string::jsonencode($basket->remove_from_ssel($parm['sselcont_id']));
    break;
  case 'DELBASK':
    $parm = $request->get_parms('ssel');
    $basket = basket_adapter::getInstance($appbox, $parm['ssel'], $usr_id);
    $output = $basket->delete();
    unset($basket);
    break;

  case 'MOVCHU2CHU':
    $parm = $request->get_parms('from', 'dest', 'sselcont');
    $from_basket = basket_adapter::getInstance($appbox, $parm['from'], $usr_id);
    $to_basket = basket_adapter::getInstance($appbox, $parm['dest'], $usr_id);

    $ret = array('error' => _('phraseanet :: une erreur est survenue'), 'datas' => array());
    if (!is_array($parm['sselcont']))
      $parm['sselcont'] = explode(';', $parm['sselcont']);

    $elements = $from_basket->get_elements();
    foreach ($parm['sselcont'] as $sselcont_id)
    {
      if (!isset($elements[$sselcont_id]))
        continue;

      $element = $elements[$sselcont_id];

      if ($to_basket->push_element($element->get_record(), false, false))
      {
        unset($elements[$sselcont_id]);
        $from_basket->remove_from_ssel($sselcont_id);

        $ret['error'] = false;
        $ret['datas'][] = $sselcont_id;
      }
    }
    $output = p4string::jsonencode($ret);
    break;
  case 'MOVREG2REG':
    require ($registry->get('GV_RootPath') . 'lib/classes/deprecated/prodUtils.php');
    $lst = array();
    $parm = $request->get_parms('lst', 'dest', 'sselcont', 'from');
    $basket = basket_adapter::getInstance($appbox, $parm['dest'], $usr_id);
    $res = $basket->push_list($parm['lst'], false);
    if (!$res['error'])
    {
      $basket = basket_adapter::getInstance($appbox, $parm['from'], $usr_id);

      $sselcont_ids = explode(';', $parm['sselcont']);
      foreach ($sselcont_ids as $sselcont_id)
      {
        $basket->remove_from_ssel($sselcont_id);
      }
    }
    $output = p4string::jsonencode(array('error' => $res['error'], 'datas' => explode(';', $parm['sselcont'])));
    break;
  case 'MOVCHU2REG':
    require ($registry->get('GV_RootPath') . 'lib/classes/deprecated/prodUtils.php');
    $parm = $request->get_parms('lst', 'dest', 'sselcont', 'from');
    $basket = basket_adapter::getInstance($appbox, $parm['dest'], $usr_id);
    $res = $basket->push_list($parm['lst'], false);
    if (!$res['error'])
    {
      $basket = basket_adapter::getInstance($appbox, $parm['from'], $usr_id);

      $sselcont_ids = explode(';', $parm['sselcont']);
      foreach ($sselcont_ids as $sselcont_id)
      {
        $basket->remove_from_ssel($sselcont_id);
      }
    }
    $output = p4string::jsonencode(array('error' => $res['error'], 'datas' => explode(';', $parm['sselcont'])));
    break;
//  case 'MOVREG2CHU':
//    require ($registry->get('GV_RootPath') . 'lib/classes/deprecated/prodUtils.php');
//
//    $parm = $request->get_parms('lst', 'dest', 'sselcont', 'from');
//    $basket = basket_adapter::getInstance($appbox, $parm['dest'], $usr_id);
//    $add = $basket->push_list($parm['lst'], false);
//
//    if (!$add['error'])
//    {
//
//      $basket = basket_adapter::getInstance($appbox, $parm['from'], $usr_id);
//
//      $ret['error'] = false;
//      $ret['datas'] = array();
//
//      $sselcont_ids = explode(';', $parm['sselcont']);
//      foreach ($sselcont_ids as $sselcont_id)
//      {
//        $rem = $basket->remove_from_ssel($sselcont_id);
//        if (!$rem['error'])
//        {
//          $ret['datas'][] = $sselcont_id;
//        }
//        else
//        {
//          $ret['error'] = true;
//        }
//      }
//    }
//    else
//      $ret = array('datas' => array(), 'error' => $add['error']);
//    $output = p4string::jsonencode($ret);
//    break;




  case 'GET_ORDERMANAGER':
    try
    {
      $parm = $request->get_parms('sort', 'page');
      $orders = new set_ordermanager($parm['sort'], $parm['page']);
      $twig = new supertwig();
      $twig->addFilter(array('phraseadate' => 'phraseadate::getPrettyString'));
      $render = $twig->render('prod/orders/order_box.twig', array('ordermanager' => $orders));
      $ret = array('error' => false, 'datas' => $render);
    }
    catch (Exception $e)
    {
      $ret = array('error' => true, 'datas' => $e->getMessage());
    }

    $output = p4string::jsonencode($ret);
    break;

  case 'GET_ORDER':
    try
    {
      $parm = $request->get_parms('order_id');
      $order = new set_order($parm['order_id']);

      $twig = new supertwig();
      $twig->addFilter(array('phraseadate' => 'phraseadate::getPrettyString'));
      $twig->addFilter(array('nl2br' => 'nl2br'));
      $render = $twig->render('prod/orders/order_item.twig', array('order' => $order));
      $ret = array('error' => false, 'datas' => $render);
    }
    catch (Exception $e)
    {
      $ret = array('error' => true, 'datas' => $e->getMessage());
    }

    $output = p4string::jsonencode($ret);
    break;

  case 'SEND_ORDER':
    try
    {
      $parm = $request->get_parms('order_id', 'elements', 'force');
      $order = new set_order($parm['order_id']);
      $order->send_elements($parm['elements'], $parm['force']);
      $ret = array('error' => false, 'datas' => '');
    }
    catch (Exception $e)
    {
      $ret = array('error' => true, 'datas' => $e->getMessage());
    }

    $output = p4string::jsonencode($ret);
    break;

  case 'DENY_ORDER':
    try
    {
      $parm = $request->get_parms('order_id', 'elements');
      $order = new set_order($parm['order_id']);
      $order->deny_elements($parm['elements']);
      $ret = array('error' => false, 'datas' => '');
    }
    catch (Exception $e)
    {
      $ret = array('error' => true, 'datas' => $e->getMessage());
    }

    $output = p4string::jsonencode($ret);
    break;



  case "ORDER":
    $parm = $request->get_parms('lst', 'ssttid', 'use', 'deadline');
    $order = new set_exportorder($parm['lst'], $parm['ssttid']);

    if ($order->order_available_elements($session->get_usr_id(), $parm['use'], $parm['deadline']))
    {
      $ret = array('error' => false, 'message' => _('les enregistrements ont ete correctement commandes'));
    }
    else
    {
      $ret = array('error' => true, 'message' => _('Erreur lors de la commande des enregistrements'));
    }

    $output = p4string::jsonencode($ret);

    break;
  case "FTP_EXPORT":

    $request = http_request::getInstance();
    $parm = $request->get_parms(
                    "addr"   // addr du srv ftp
                    , "login" // login ftp
                    , "pwd"  // pwd ftp
                    , "passif" // mode passif ou non
                    , "nbretry" // nb retry
                    , "ssl" // nb retry
                    , "obj" // les types d'obj a exporter
                    , "destfolder"// le folder de destination
                    , "usr_dest"  // le mail dudestinataire ftp
                    , "lst"  // la liste des objets
                    , "ssttid"
                    , "sendermail"
                    , "namecaract"
                    , "NAMMKDFOLD"
                    , "logfile"
    );

    $download = new set_exportftp($parm['lst'], $parm['ssttid']);

    if (count($download->get_display_ftp()) == 0)
    {
      $output = p4string::jsonencode(array('error' => true, 'message' => _('Les documents ne peuvent etre envoyes par FTP')));
    }
    else
    {
      try
      {
        $download->prepare_export($parm['obj']);
        $download->export_ftp($parm['usr_dest'], $parm['addr'], $parm['login'], $parm['pwd'], $parm['ssl'], $parm['nbretry'], $parm['passif'], $parm['destfolder'], $parm['NAMMKDFOLD'], $parm['logfile']);

        $output = p4string::jsonencode(array('error' => false, 'message' => _('Export enregistre dans la file dattente')));
      }
      catch (Exception $e)
      {
        $output = p4string::jsonencode(array('error' => true, 'message' => $e->getMessage()));
      }
    }
    break;
  case "FTP_TEST":

    $request = http_request::getInstance();
    $parm = $request->get_parms(
                    "addr"   // addr du srv ftp
                    , "login" // login ftp
                    , "pwd"  // pwd ftp
                    , "ssl" // nb retry
    );

    $ssl = $parm['ssl'] == '1';

    try
    {
      $ftp_client = new ftpclient($parm['addr'], 21, 90, $ssl = false);
      $ftp_client->login($parm['login'], $parm['pwd']);
      $ftp_client->close();
      $output = _('Connection au FTP avec succes');
    }
    catch (Exception $e)
    {
      $output = sprintf(_('Erreur lors de la connection au FTP : %s'), $e->getMessage());
    }

    break;
}
echo $output;


