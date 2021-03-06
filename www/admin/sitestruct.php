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

phrasea::headers();


$request = http_request::getInstance();
$parm = $request->get_parms("act", "p0", "p1", 'flush_cache', 'sudo', 'admins', 'email');

$user = User_Adapter::getInstance($session->get_usr_id(), $appbox);
if (!$user->is_admin())
{
  phrasea::headers(403);
}


$cache_flushed = false;
if ($parm['flush_cache'] && $registry->get('GV_cache_server_type') !== 'nocache')
{
  $cache = $appbox->get_cache()->flush();

  if ($cache->ping())
  {
    if ($cache->flush() === true)
      $cache_flushed = true;
  }
}
?>
<html lang="<?php echo $session->get_I18n(); ?>">
  <head>
    <link type="text/css" rel="stylesheet" href="/include/minify/f=include/jslibs/jquery-ui-1.8.12/css/ui-lightness/jquery-ui-1.8.12.custom.css,skins/common/main.css,skins/admin/admincolor.css" />
    <script type="text/javascript" src="/include/minify/f=include/jslibs/jquery-1.5.2.js"></script>
    <script type="text/javascript" src="/include/jslibs/jquery-ui-1.8.12/js/jquery-ui-1.8.12.custom.min.js"></script>
    <style type="text/css">
      body
      {
      }
      A,  A:link, A:visited, A:active
      {

        color : #000000;
        padding-bottom : 15px;
        text-decoration:none;
      }

      A:hover
      {
        COLOR : #ba36bf;
        text-decoration:underline;
      }


      h1{
        position:relative;
        float:left;
        width:100%;
      }
      ul.setup {
        position:relative;
        float:left;
        width:360px;
        list-style-type:none;
        padding:0 0 0 0px;
        margin:5px 0 5px 40px;
        border:1px solid #404040;
      }
      .setup li{
        margin:0px 0;
        padding:2px 5px 2px 30px;
        background-image:url(/skins/icons/ok.png);
        background-repeat:no-repeat;
        background-position:5px center;
      }
      .setup li.non-blocker{
        background-image:url(/skins/icons/alert.png);
      }
      .setup li.blocker{
        background-image:url(/skins/icons/delete.png);
      }
      tr.even{
        background-color:#CCCCCC;
      }
    </style>

  <style>
    .ui-autocomplete {
      max-height: 200px;
      overflow-y: auto;
      /* prevent horizontal scrollbar */
      overflow-x: hidden;
      /* add padding to account for vertical scrollbar */
      padding-right: 20px;
    }
    /* IE 6 doesn't support max-height
     * we use height instead, but this forces the menu to always be this tall
     */
    * html .ui-autocomplete {
      height: 200px;
    }
    .ui-autocomplete-loading { background: white url('/skins/icons/ui-anim_basic_16x16.gif') right center no-repeat; }
  </style>
  <script type="text/javascript">

    $(document).ready(function(){
      $( ".admin_adder" ).autocomplete({
        source: "/admin/users/typeahead/search/",
        minLength: 2,
        select: function( event, ui ) {
          var form = $('#admin_adder');
          $('input.new[name="admins[]"]', form).val(ui.item.id);
          form.submit();
        }
      }).data( "autocomplete" )._renderItem = function( ul, item ) {
        var email = item.email ? '<br/>'+item.email : '';
        var login = item.login != item.name ? " ("+ item.login +")" : '';

        return $( "<li></li>" )
          .data( "item.autocomplete", item )
          .append( "<a>" + item.name + login + email + "</a>" )
          .appendTo( ul );
      };
    });
  </script>
  </head>
  <body>
    <?php
    if ($parm['sudo'])
    {
      if ($parm['sudo'] == '1')
      {
        User_Adapter::reset_sys_admins_rights();
      }
    }

    if ($parm['admins'])
    {
      $admins = array();

      foreach ($parm['admins'] as $a)
      {
        if (trim($a) == '')
          continue;

        $admins[] = $a;
      }

      if (!in_array($session->get_usr_id(), $admins))
        $admins[] = $session->get_usr_id();

      if ($admins > 0)
      {
        User_Adapter::set_sys_admins($admins);
        User_Adapter::reset_sys_admins_rights();
      }
    }

    if ($cache_flushed)
    {
    ?>
      <div>
<?php echo _('admin::Le serveur memcached a ete flushe'); ?>
      </div>
<?php
    }
?>
    <div>
      <h1><?php echo _('setup:: administrateurs de l\'application') ?></h1>
      <form id="admin_adder" action="sitestruct.php" method="post">
<?php
    $admins = User_Adapter::get_sys_admins();

    foreach ($admins as $usr_id => $usr_login)
    {
?>
        <div><input name="admins[]" type="checkbox" value="<?php echo $usr_id ?>" id="adm_<?php echo $usr_id ?>" checked /><label for="adm_<?php echo $usr_id ?>"><?php echo $usr_login; ?></label></div>
        <?php
      }
        ?>
        <div><?php echo _('setup:: ajouter un administrateur de l\'application') ?></div>

        <input class="admin_adder"/>
        <input type="hidden" class="new" name="admins[]"/>
        <input type="submit" value="<?php echo _('boutton::valider') ?>" />
      </form>
      <h1><?php echo _('setup:: Reinitialisation des droits admins') ?></h1>

      <form action="sitestruct.php" method="post">
        <input type="hidden" name="sudo" value="1" />
        <input type="submit" value="<?php echo _('boutton::reinitialiser') ?>" />
      </form>
    </div>
    <h1><?php echo _('setup:: Reglages generaux') ?></h1>
    <br>
<?php
          try
          {
            $invite = new User_Adapter('invite', $appbox);
          }
          catch (Exception $e)
          {
            $invite = User_Adapter::create($appbox, 'invite', 'invite', '', false);
          }
?>
          <div><a href="editusr.php?ord=asc&p2=<?php echo $invite->get_id() ?>"><?php echo _('Reglages:: reglages d acces guest'); ?></a></div>
    <?php
          try
          {
            $autoregister = new User_Adapter('autoregister', $appbox);
          }
          catch (Exception $e)
          {
            $invite = User_Adapter::create($appbox, 'autoregister', 'autoregister', '', false);
          }
          if ($autoregister !== false)
          {
    ?>
            <div><a href="editusr.php?ord=asc&p2=<?php echo $autoregister->get_id() ?>"><?php echo _('Reglages:: reglages d inscitpition automatisee'); ?></a></div>
    <?php
          }
    ?>
          <h2><?php echo _('setup::Votre configuration') ?></h2>
          <div>
            <div style="position:relative;float:left;width:400px;">
    <?php
          setup::check_mail_form();

          if ($parm['email'])
          {
            echo 'result : ';
            var_dump(mail::mail_test($parm['email']));
          }
          $php_constraints = setup::check_php_version();


          foreach($php_constraints as $php_constraint)
          {
            echo '<h1>' . $php_constraint->get_name() . '</h1>';
            echo '<ul class="setup">';
              ?>
                <li class="<?php echo $php_constraint->is_ok() ? 'good-enough' : 'blocker' ;?>">
                  <?php echo $php_constraint->get_message(); ?>
                </li>
              <?php
            echo '</ul>';
          }

      $php_constraints  = setup::check_writability($registry);

      echo '<h1>' . _('setup::Filesystem configuration') . '</h1>';
      echo '<ul class="setup">';
      foreach($php_constraints as $constraint)
      {
        ?>
          <li class="<?php echo !$constraint->is_ok() ? ($constraint->is_blocker() ? 'blocker' : 'non-blocker') : 'good-enough';?>">
            <?php echo $constraint->get_message(); ?>
          </li>
        <?php
      }
      echo '</ul>';


      $php_constraints  = setup::check_binaries($registry);
      echo '<h1>' . _('setup::Executables') . '</h1>';
      echo '<ul class="setup">';
      foreach($php_constraints as $constraint)
      {
        ?>
          <li class="<?php echo !$constraint->is_ok() ? ($constraint->is_blocker() ? 'blocker' : 'non-blocker') : 'good-enough';?>">
            <?php echo $constraint->get_message(); ?>
          </li>
        <?php
      }
      echo '</ul>';


      $php_constraints  = setup::check_php_extension();
      echo '<h1>' . _('setup::PHP extensions') . '</h1>';
      echo '<ul class="setup">';
      foreach($php_constraints as $constraint)
      {
        ?>
          <li class="<?php echo !$constraint->is_ok() ? ($constraint->is_blocker() ? 'blocker' : 'non-blocker') : 'good-enough';?>">
            <?php echo $constraint->get_message(); ?>
          </li>
        <?php
      }
      echo '</ul>';

      $php_constraints  = setup::check_cache_server();
      echo '<h1>' . _('setup::Serveur de cache') . '</h1>';
      echo '<ul class="setup">';
      foreach($php_constraints as $constraint)
      {
        ?>
          <li class="<?php echo !$constraint->is_ok() ? ($constraint->is_blocker() ? 'blocker' : 'non-blocker') : 'good-enough';?>">
            <?php echo $constraint->get_message(); ?>
          </li>
        <?php
      }
      echo '</ul>';


        ?>
        </div>
        <div style="position:relative;float:left;width:400px;margin-left:25px;">
        <?php

          $php_constraints  = setup::check_phrasea();
          echo '<h1>' . _('Phrasea Module') . '</h1>';
          echo '<ul class="setup">';
          foreach($php_constraints as $constraint)
          {
            ?>
              <li class="<?php echo !$constraint->is_ok() ? ($constraint->is_blocker() ? 'blocker' : 'non-blocker') : 'good-enough';?>">
                <?php echo $constraint->get_message(); ?>
              </li>
            <?php
          }
          echo '</ul>';

          setup::check_apache();
          setup::check_mod_auth_token();
          setup::check_cache_opcode();
          setup::check_cache_memcache();

          if ($registry->get('GV_cache_server_type') !== 'nocache')
          {
            $cache = cache_adapter::get_instance($registry);

            if ($cache->ping())
            {
        ?>
              <form method="post" action="sitestruct.php">
                <input type="hidden" name="flush_cache" value="1"/>
                <input type="submit" value="Flush Memcached"/>
              </form>
<?php
            }
          }
?>
        <?php
          setup::check_sphinx_search();
          setup::check_php_configuration();
        ?>
      </div>
    </div>
  </body>
</html>
