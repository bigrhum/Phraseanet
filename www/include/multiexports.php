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

$user = User_Adapter::getInstance($session->get_usr_id(), $appbox);

$request = http_request::getInstance();
$parm = $request->get_parms("lst", "SSTTID");

$gatekeeper = gatekeeper::getInstance();
$gatekeeper->require_session();

if ($registry->get('GV_needAuth2DL') && $user->is_guest())
{
?>
  <script>
    parent.hideDwnl();
    parent.login('{act:"dwnl",lst:"<?php echo $parm['lst'] ?>",SSTTID:"<?php echo $parm['SSTTID'] ?>"}');
  </script>
<?php
  exit();
}


$download = new set_export($parm['lst'], $parm['SSTTID']);
$user = User_Adapter::getInstance($session->get_usr_id(), $appbox);

$twig = new supertwig();

$twig->addFilter(array('geoname_display' => 'geonames::name_from_id'));
$twig->addFilter(array('format_octets' => 'p4string::format_octets'));

$twig->display('common/dialog_export.twig', array(
    'download' => $download,
    'ssttid' => $parm['SSTTID'],
    'lst' => $parm['lst'],
    'user' => $user,
    'default_export_title' => $registry->get('GV_default_export_title'),
    'choose_export_title' => $registry->get('GV_choose_export_title')
));



