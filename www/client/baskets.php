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

$usr_id = $session->get_usr_id();

$request = http_request::getInstance();
$parm = $request->get_parms("bas", "courChuId", "act", "p0", "first");

$parm['p0'] = utf8_decode($parm['p0']);

$nbNoview = 0;

$user = User_Adapter::getInstance($session->get_usr_id(), $appbox);
$ACL = $user->ACL();

$out = null;

if ($parm["act"] == "DELIMG" && $parm["p0"] != "")
{
  $basket = basket_adapter::getInstance($appbox, $parm['courChuId'], $user->get_id());
  $basket->remove_from_ssel($parm['p0']);
}

if ($parm["act"] == "ADDIMG" && ($parm["p0"] != "" && $parm["p0"] != null))
{
  $basket = basket_adapter::getInstance($appbox, $parm['courChuId'], $user->get_id());
  $sbas_id = phrasea::sbasFromBas($parm['bas']);
  $record = new record_adapter($sbas_id, $parm['p0']);
  $basket->push_element($record, false, false);
  unset($record);
}

if ($parm["act"] == "DELCHU" && ($parm["p0"] != "" && $parm["p0"] != null))
{
  $basket = basket_adapter::getInstance($appbox, $parm['p0'], $user->get_id());
  $basket->delete();
  unset($basket);
}


if ($parm["act"] == "NEWCHU" && ($parm["p0"] != "" && $parm["p0"] != null))
{
  $basket = basket_adapter::create($appbox, $parm['p0'], $user);
  $parm['courChuId'] = $basket->get_ssel_id();
}
$basket_coll = new basketCollection($appbox, $usr_id, 'name ASC', array('regroup'));
$baskets = $basket_coll->get_baskets();

$out = "<table style='width:99%' class='baskIndicator' id='baskMainTable'><tr><td>";
$out .= '<select id="chutier_name" name="chutier_name" onChange="chg_chu();" style="width:120px;">';
$firstGroup = true;

foreach ($baskets as $typeBask => $basket)
{

  if (!$firstGroup)
    $out.='</optgroup>';
  $firstGroup = false;

  if ($typeBask == 'baskets' && count($basket) > 0)
  {
    $out.='<optgroup label="' . _('paniers::categories: mes paniers') . '">';
    foreach ($basket as $bask)
    {
      $baskId = $bask->get_ssel_id();
      $sltd = '';
      if (is_null($parm['courChuId']) || trim($parm['courChuId']) == '')
        $parm['courChuId'] = $baskId;
      if ($parm['courChuId'] == $baskId)
        $sltd = 'selected';
      $out .= '<option class="chut_choice" ' . $sltd . ' value="' . $baskId . '">' . $bask->get_name() . '</option>';
    }
  }

  if ($typeBask == 'recept' && !is_null($basket))
  {
    $out.='<optgroup label="' . _('paniers::categories: paniers recus') . '">';
    foreach ($basket as $bask)
    {
      $baskId = $bask->get_ssel_id();
      $sltd = '';
      if (is_null($parm['courChuId']) || trim($parm['courChuId']) == '')
        $parm['courChuId'] = $baskId;
      if ($parm['courChuId'] == $baskId)
        $sltd = 'selected';
      $out .= '<option class="chut_choice" ' . $sltd . ' value="' . $baskId . '">' . $bask->get_name() . '</option>';
    }
  }
}
$out.='</optgroup>';
$out .= "</select>";
$out .= '</td><td style="width:40%">';


$basket = basket_adapter::getInstance($appbox, $parm['courChuId'], $user->get_id());

$jscriptnochu = $basket->get_name() . " :  " . sprintf(_('paniers:: %d documents dans le panier'), count($basket->get_elements()));

$nbElems = count($basket->get_elements());
?><div id="blocBask" class="bodyLeft" style="height:314px;bottom:0px;"><?php ?><div class="baskTitle"><?php ?><div id="flechenochu" class="flechenochu"></div><?php
$totSizeMega = $basket->get_size();
echo '<div class="baskName">' . sprintf(_('paniers:: paniers:: %d documents dans le panier'), $nbElems) .
 ($appbox->get_registry()->get('GV_viewSizeBaket') ? ' (' . $totSizeMega . ' Mo)' : '') . '</div>';
?></div><?php
?><div><?php
    echo $out;


    if ($nbElems > 0 && $basket->is_mine())
    {
?><div class="baskDel" title="<?php echo _('action : supprimer') ?>" onclick="evt_chutier('DELSSEL');"/></div><?php
    }
    if ($ACL->has_right("addtoalbum"))
    {
?><div class="baskCreate" title="<?php echo _('action:: nouveau panier') ?>" onclick="newBasket();"></div><?php
    }
?><div style="float:right;position:relative;width:3px;height:16px;"></div><?php
    if ($nbElems > 0 && ($ACL->has_right("candwnldhd") || $ACL->has_right("candwnldpreview") || $ACL->has_right("cancmd") > 0 ))
    {
?><div class="baskDownload" title="<?php echo _('action : exporter') ?>" onclick="evt_dwnl();"></div><?php
    }
    if ($nbElems > 0)
    {
?><div class="baskPrint" title="<?php echo _('action : print') ?>" onclick="evt_print();"></div><?php
    }
    $jsclick = '';
    if ($parm['courChuId'] != null && $parm['courChuId'] != '' && is_numeric($parm['courChuId']))
    {
      $jsclick = ' onclick=openCompare(\'' . $parm['courChuId'] . '\') ';
    }
?><div class="baskComparator" <?php echo $jsclick ?> title="<?php echo _('action : ouvrir dans le comparateur') ?>"></div><?php
?></td><?php
?></tr><?php
?></table><?php
?></div><?php
?><div class="divexterne" style="height:270px;overflow-x:hidden;overflow-y:auto;position:relative"><?php
    if ($basket->get_pusher() instanceof user)
    {
?><div class="txtPushClient"><?php
      echo sprintf(_('paniers:: panier emis par %s'), $pusher->get_display_name())
?></div><?php
    }

    foreach ($basket->get_elements() as $basket_element)
    {

      $dim = $dim1 = $top = 0;

      $thumbnail = $basket_element->get_record()->get_thumbnail();

      if ($thumbnail->get_width() > $thumbnail->get_height()) // cas d'un format paysage
      {
        if ($thumbnail->get_width() > 67)
        {
          $dim1 = 67;
          $top = ceil((67 - 67 * $thumbnail->get_height() / $thumbnail->get_width()) / 2);
        }
        else // miniature
        {
          $dim1 = $thumbnail->get_width();
          $top = ceil((67 - $thumbnail->get_height()) / 2);
        }
        $dim = "width:" . $dim1 . "px";
      }
      else // cas d'un format portrait
      {
        if ($thumbnail->get_height() > 55)
        {
          $dim1 = 55;
          $top = ceil((67 - 55) / 2);
        }
        else // miniature
        {
          $dim1 = $thumbnail->get_height();
          $top = ceil((67 - $thumbnail->get_height()) / 2);
        }
        $dim = "height:" . $dim1 . "px";
      }

      if ($thumbnail->get_height() > 42)
        $classSize = "hThumbnail";
      else
        $classSize = "vThumbnail";

      $tooltip = "";

      $record = $basket_element->get_record();
      if ($appbox->get_registry()->get('GV_rollover_chu'))
      {
        $tooltip = 'tooltipsrc="/prod/tooltip/caption/' . $record->get_sbas_id() . '/' . $record->get_record_id() . '/basket/"';
      }
?><div class="diapochu"><?php
?><div class="image"><?php
?><img onclick="openPreview('BASK',<?php echo $basket_element->get_record()->get_number() ?>,<?php echo $parm["courChuId"] ?>); return(false);"
<?php echo $tooltip ?> style="position:relative; top:<?php echo $top ?>px; <?php echo $dim ?>"
               class="<?php echo $classSize ?> baskTips" src="<?php echo $thumbnail->get_url() ?>"><?php
?></div><?php
?><div class="tools"><?php
      if ($basket->is_mine())//le panier est a moi, je peux effacer des elements
      {
?><div class="baskOneDel" onclick="evt_del_in_chutier('<?php echo $basket_element->get_sselcont_id() ?>');"
                 title="<?php echo _('action : supprimer') ?>"></div><?php
      }

      if ($user->ACL()->has_right_on_base($record->get_base_id(), 'candwnldhd') ||
              $user->ACL()->has_right_on_base($record->get_base_id(), 'candwnldpreview') ||
              $user->ACL()->has_right_on_base($record->get_base_id(), 'cancmd') ||
              $user->ACL()->has_preview_grant($record))
      {
?><div class="baskOneDownload" onclick="evt_dwnl('<?php echo $record->get_sbas_id() ?>_<?php echo $record->get_record_id() ?>');" title="<?php echo _('action : exporter') ?>"></div><?php
       }
?></div><?php
?></div><?php
     }
?></div></div><div id="blocNoBask" class="bodyLeft" style="height: 22px;display:none;bottom:0px;"><?php
?><div class="baskTitle"><?php
?><div id="flechechu" class="flechenochu"></div><?php
?><div id="viewtext" class="baskName"><?php echo $jscriptnochu ?><span style="width:16px;height:16px;position: absolute; right: 10px;background-position:center center;" class='baskIndicator'></span></div><?php ?></div><?php ?></div>
<?php
?>
<script>
  var oldNoview = p4.nbNoview;
  p4.nbNoview = parseInt(<?php echo $nbNoview ?>);
  if(p4.nbNoview>oldNoview)
    alert('<?php echo _('paniers:: vous avez de nouveaux paniers non consultes'); ?>');
</script>
