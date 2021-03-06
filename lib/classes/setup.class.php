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
 * This file MUST NOT contains any default PHP function as
 * mb_*, curl_*, bind_text_domain, _
 *
 * This file is intended to be loaded on setup test
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class setup
{

  protected static $PHP_EXT = array(
      "dom"
      , "exif"
      , "fileinfo"
      , "ftp"
      , "curl"
      , "gd"
      , "gettext"
      , "hash"
      , "json"
      , "iconv"
      , "libxml"
      , "mbstring"
      , "mysql"
      , "PDO"
      , "phrasea2"
      , "SimpleXML"
      , "sockets"
      , "xml"
      , "zip"
      , "zlib"
  );
  protected static $PHP_CONF = array(
      'output_buffering' => '4096'  //INI_ALL
      , 'memory_limit' => '1024M'  //INI_ALL
      , 'error_reporting' => '6143'  //INI_ALL
      , 'default_charset' => 'UTF-8'  //INI_ALL
      , 'session.use_cookies' => '1'   //INI_ALL
      , 'session.use_only_cookies' => '1'   //INI_ALL
      , 'session.auto_start' => '0'   //INI_ALL
      , 'session.hash_function' => '1'   //INI_ALL
      , 'session.hash_bits_per_character' => '6'  //INI_ALL
      , 'allow_url_fopen' => 'on'  //INI_ALL
      , 'display_errors' => 'off'  //INI_ALL
      , 'display_startup_errors' => 'off'  //INI_ALL
      , 'log_errors' => 'off'  //INI_ALL
  );
  protected static $PHP_REQ = array(
      'safe_mode' => 'off'
      , 'file_uploads' => '1'
      , 'magic_quotes_runtime' => 'off'  //INI_ALL
      , 'magic_quotes_gpc' => 'off'  //INI_PER_DIR -- just for check
  );

  public static function is_installed()
  {
    return file_exists(dirname(__FILE__) . "/../../config/connexion.inc")
            && file_exists(dirname(__FILE__) . "/../../config/config.inc");
  }

  function create_global_values(registryInterface &$registry, $datas=array())
  {
    require(dirname(__FILE__) . "/../../lib/conf.d/_GV_template.inc");


    if ($registry->is_set('GV_timezone'))
      date_default_timezone_set($registry->get('GV_timezone'));
    else
      date_default_timezone_set('Europe/Berlin');

    $debug = $log_errors = false;
    $vars = array();

    $error = false;
    $extra_conf = '';

    foreach ($GV as $section)
    {
      foreach ($section['vars'] as $variable)
      {
        if (isset($datas[$variable['name']]) === false)
        {
          if (isset($variable['default']))
          {
            if ($variable['type'] === 'boolean')
            {
              if ($variable['default'] === true)
                $datas[$variable['name']] = '1';
              else
                $datas[$variable['name']] = '0';
            }
            else
            {
              $datas[$variable['name']] = $variable['default'];
            }
          }
        }


        $type = 'string';
        switch ($variable['type'])
        {
          case 'string':
          case 'password':
            $datas[$variable['name']] = (string) trim($datas[$variable['name']]);
            break;
          case 'enum':
            if (!isset($variable['available']))
            {
              $variable['error'] = 'avalaibility';
            }
            elseif (!is_array($variable['available']))
            {
              $variable['error'] = 'avalaibility';
            }
            elseif (!in_array($datas[$variable['name']], $variable['available']))
            {
              $variable['error'] = 'avalaibility';
            }
            break;
          case 'enum_multi':
            if (!isset($datas[$variable['name']]))
              $datas[$variable['name']] = null;
            $datas[$variable['name']] = ($datas[$variable['name']]);
            $type = 'array';
            break;
          case 'boolean':
            $datas[$variable['name']] = strtolower($datas[$variable['name']]) === 'true' ? '1' : '0';
            $type = 'boolean';
            break;
          case 'integer':
            $datas[$variable['name']] = (int) trim($datas[$variable['name']]);
            $type = 'integer';
            break;
          case 'text':
            $datas[$variable['name']] = trim($datas[$variable['name']]);
            break;
          case 'timezone':
            $datas[$variable['name']] = trim($datas[$variable['name']]);
            break;
          default:
            $error = true;
            break;
        }

        if (isset($variable['required']) && $variable['required'] === true && trim($datas[$variable['name']]) === '')
          $variable['error'] = 'required';

        if (isset($variable['end_slash']))
        {
          if ($variable['end_slash'] === true)
          {
            $datas[$variable['name']] = trim($datas[$variable['name']]) !== '' ? p4string::addEndSlash($datas[$variable['name']]) : '';
          }
          if ($variable['end_slash'] === false)
          {
            $datas[$variable['name']] = trim($datas[$variable['name']]) !== '' ? p4string::delEndSlash($datas[$variable['name']]) : '';
          }
        }

        if ($variable['name'] === 'GV_debug' && $datas[$variable['name']] === '1')
          $debug = true;
        if ($variable['name'] === 'GV_log_errors' && $datas[$variable['name']] === '1')
          $log_errors = true;

        if ($variable['type'] !== 'integer' && $variable['type'] !== 'boolean')
          $datas[$variable['name']] = $datas[$variable['name']];

        $vars[$variable['name']] = array('value' => $datas[$variable['name']], 'type' => $type);
      }
    }

    if ($error === false)
    {
      foreach ($vars as $key => $values)
      {
        $registry->set($key, $values['value'], $values['type']);
      }

      return true;
    }

    return false;
  }

  public static function check_binaries(registryInterface $registry)
  {
    $binaries = array(
        'PHP CLI' => $registry->get('GV_cli'),
        'ImageMagick (convert)' => $registry->get('GV_imagick'),
        'PDF 2 SWF' => $registry->get('GV_pdf2swf'),
        'Unoconv' => $registry->get('GV_unoconv'),
        'SWFextract' => $registry->get('GV_swf_extract'),
        'SWFrender' => $registry->get('GV_swf_render'),
        'MP4Box' => $registry->get('GV_mp4box'),
        'xpdf (pdf2text)' => $registry->get('GV_pdftotext'),
        'ImageMagick (composite)' => $registry->get('GV_pathcomposite'),
        'Exiftool' => $registry->get('GV_exiftool'),
        'FFmpeg' => $registry->get('GV_ffmpeg'),
        'MPlayer' => $registry->get('GV_mplayer')
    );

    $constraints = array();

    foreach ($binaries as $name => $binary)
    {
      if (trim($binary) == '' || (!is_file($binary)))
      {
        $constraints[] = new Setup_Constraint(
                        $name
                        , false
                        , sprintf('%s missing', $name)
                        , false
        );
      }
      else
      {
        if (!is_executable($binary))
        {
          $constraints[] = new Setup_Constraint(
                          $name
                          , false
                          , sprintf('%s not executeable', $name)
                          , true
          );
        }
        else
        {
          $constraints[] = new Setup_Constraint(
                          $name
                          , true
                          , sprintf('%s OK', $name)
                          , true
          );
        }
      }
    }

    return new Setup_ConstraintsIterator($constraints);
  }

  public static function discover_binaries()
  {
    if (system_server::get_platform() == 'WINDOWS')
    {
      $exiftool = dirname(dirname(__FILE__)) . '/vendor/exiftool/exiftool.exe';
      $indexer = dirname(dirname(dirname(__FILE__))) . '/bin/phraseanet_indexer.exe';
    }
    else
    {
      $exiftool = dirname(dirname(__FILE__)) . '/vendor/exiftool/exiftool';
      $indexer = null;
    }

    return array(
        'php' => array('name' => 'PHP CLI', 'binary' => self::discover_binary('php')),
        'exiftool' => array('name' => 'Exiftool', 'binary' => self::discover_binary('exiftool', array($exiftool))),
        'phraseanet_indexer' => array('name' => 'Indexeur Phrasea', 'binary' => self::discover_binary('phraseanet_indexer', array($indexer))),
        'convert' => array('name' => 'ImageMagick (convert)', 'binary' => self::discover_binary('convert')),
        'composite' => array('name' => 'ImageMagick (composite)', 'binary' => self::discover_binary('composite')),
        'pdf2swf' => array('name' => 'PDF 2 SWF', 'binary' => self::discover_binary('pdf2swf')),
        'unoconv' => array('name' => 'Unoconv', 'binary' => self::discover_binary('unoconv')),
        'swfextract' => array('name' => 'SWFextract', 'binary' => self::discover_binary('swfextract')),
        'swfrender' => array('name' => 'SWFrender', 'binary' => self::discover_binary('swfrender')),
        'MP4Box' => array('name' => 'MP4Box', 'binary' => self::discover_binary('MP4Box')),
        'xpdf' => array('name' => 'XPDF', 'binary' => self::discover_binary('xpdf')),
        'ffmpeg' => array('name' => 'FFmpeg', 'binary' => self::discover_binary('ffmpeg')),
        'mplayer' => array('name' => 'MPlayer', 'binary' => self::discover_binary('mplayer'))
    );
  }

  protected static function discover_binary($binary, array $look_here = array())
  {
    if (system_server::get_platform() == 'WINDOWS')

      return null;

    foreach ($look_here as $place)
    {
      if (is_executable($place))

        return $place;
    }

    return exec(sprintf('which %s', $binary));
  }

  function check_mod_auth_token()
  {
    $registry = registry::get_instance();

    if ($registry->get('GV_h264_streaming') !== true)

      return;
    ?>
    <h1>mod_auth_token configuration </h1>
    <ul class="setup">
      <?php
      $fileName = $registry->get('GV_mod_auth_token_directory_path') . '/test_mod_auth_token.txt';    // The file to access

      touch($fileName);

      $url = $registry->get('GV_ServerName') . p4file::apache_tokenize($fileName);

      if (http_query::getHttpCodeFromUrl($url) == 200)
        echo '<li>' . _('mod_auth_token correctement configure') . '</li>';
      else
        echo '<li class="blocker">' . _('mod_auth_token mal configure') . '</li>';
      ?>
    </ul>
    <?php
  }

  function check_apache()
  {
    $registry = registry::get_instance();
    ?>
    <h1>Apache Server mods avalaibility</h1>
    <div style="position:relative;float:left;">
      <?php
      echo _('Attention, seul le test de l\'activation des mods est effectue, leur bon fonctionnement ne l\'est pas ')
      ?>
    </div>

    <ul id="apache_mods_checker" class="setup">

      <li class="blocker">
        <a href="#" onclick="check_apache_mod(this,'rewrite');return false;">mod_rewrite (required)</a>
      </li>
      <li class="blocker">
        <a href="#" onclick="check_apache_mod(this,'xsendfile');return false;">mod_xsendfile (optionnal)</a>
        <?php
        if ($registry->get('GV_modxsendfile'))
        {
          ?>
          <div class="infos"><img style="vertical-align:middle" src="/skins/icons/alert.png"/> <?php echo _('Attention, veuillez verifier la configuration xsendfile, actuellement activee dans le setup'); ?></div>
        <?php } ?>
      </li>
      <li class="blocker">
        <a href="#" onclick="check_apache_mod(this,'authtoken');return false;">mod_auth_token (optionnal)</a>
        <?php
        if ($registry->get('GV_h264_streaming'))
        {
          ?>
          <div class="infos"><img style="vertical-align:middle" src="/skins/icons/alert.png"/> <?php echo _('Attention, veuillez verifier la configuration h264_streaming, actuellement activee dans le setup'); ?></div>
        <?php } ?>
      </li>
      <li class="blocker">
        <a href="#" onclick="check_apache_mod(this,'h264');return false;">mod_h264_streaming (optionnal)</a>
        <?php
        if ($registry->get('GV_h264_streaming'))
        {
          ?>
          <div class="infos"><img style="vertical-align:middle" src="/skins/icons/alert.png"/> <?php echo _('Attention, veuillez verifier la configuration h264_streaming, actuellement activee dans le setup'); ?></div>
        <?php } ?>
      </li>
      <style type="text/css">
        #apache_mods_checker div.infos{
          display:none;
        }
        #apache_mods_checker .blocker div.infos{
          display:block;
        }
      </style>
      <script type="text/javascript">
        $(document).ready(function(){
          $('#apache_mods_checker a').trigger('click');
        });

        function check_apache_mod(el,mod)
        {
          var url = '/admin/test-';
          switch(mod)
          {
            case 'rewrite':
              url += 'rewrite';
              break;
            case 'xsendfile':
              url += 'xsendfile';
              break;
            case 'authtoken':
              url += 'authtoken';
              break;
            case 'h264':
              url += 'h264';
              break;
          }

          $.get(url, function(data) {
            if(data == '1')
              $(el).closest('li').removeClass('blocker');
            else
              $(el).closest('li').addClass('blocker');
          });


        }
      </script>

      <?php
      echo '</ul>';
    }

    public static function check_phrasea()
    {
      $constraints = array();
      if(function_exists('phrasea_info'))
      {
        foreach (phrasea_info() as $name => $value)
        {
          switch ($name)
          {
            default:
              $result = true;
              $message = $name.' = '.$value;
              break;
            case 'temp_writable':
              $result = $value == '1';
              if ($result)
                $message = 'Directory is writeable';
              else
                $message = 'Directory MUST be writable';
              break;
            case 'version':
              $result = version_compare($value, '1.18.0.3', '>=');
              if ($result)
                $message = sprintf ('Phrasea version %s is ok', $value);
              else
                $message = sprintf('Phrasea version %s is NOT ok', $value);
              break;
          }
          $blocker = $name == 'temp_writable' ? ($value ? '' : 'blocker') : '';
          $constraints[] = new Setup_Constraint($name, $result, $message, true);
        }
      }

      return new Setup_ConstraintsIterator($constraints);
    }

    public static function check_writability(registryInterface $registry)
    {
      $root = p4string::addEndSlash(realpath(dirname(__FILE__) . '/../../'));

      $pathes = array(
          $root . 'config',
          $root . 'config/stamp',
          $root . 'config/status',
          $root . 'config/minilogos',
          $root . 'config/templates',
          $root . 'config/topics',
          $root . 'config/wm',
          $root . 'logs',
          $root . 'tmp',
          $root . 'www/custom',
          $root . 'tmp/locks',
          $root . 'tmp/cache_twig',
          $root . 'tmp/cache_minify',
          $root . 'tmp/lazaret',
          $root . 'tmp/desc_tmp',
          $root . 'tmp/download',
          $root . 'tmp/batches');

      if ($registry->is_set('GV_base_datapath_web'))
      {
        $pathes[] = $registry->get('GV_base_datapath_web');
      }
      if ($registry->is_set('GV_base_datapath_noweb'))
      {
        $pathes[] = $registry->get('GV_base_datapath_noweb');
      }


      $constraints = array();

      foreach ($pathes as $p)
      {
        if (!is_writable($p))
        {
          $message = sprintf('%s not writeable', $p);
        }
        else
        {
          $message = sprintf('%s OK', $p);
        }

        $constraints[] = new Setup_Constraint(
                        'Writeability test', is_writable($p), $message, true
        );
      }
      $php_constraints = new Setup_ConstraintsIterator($constraints);

      return $php_constraints;
    }

    function check_mail_form()
    {
      echo '<h1>' . _('setup::Tests d\'envois d\'emails') . '</h1>';
      ?>
      <form method="post" action="/admin/sitestruct.php" target="_self">
        <label>Email : </label><input name="email" type="text" />
        <input type="submit" value="<?php echo _('boutton::valider'); ?>"/>
      </form>

      <?php

      return;
    }

    /**
     *
     */
    public static function check_php_version()
    {
      $version_ok = version_compare(PHP_VERSION, '5.3.4', '>');
      if (!$version_ok)
      {
        $message = sprintf(
                'Wrong PHP version : % ; PHP >= 5.3.4 required'
                , PHP_VERSION
        );
      }
      else
      {
        $message = sprintf('PHP version OK : %s', PHP_VERSION);
      }
      $constraints = array(
          new Setup_Constraint('PHP Version', $version_ok, $message)
      );

      return new Setup_ConstraintsIterator($constraints);
    }

    public static function check_php_extension()
    {
      $constraints = array();
      foreach (self::$PHP_EXT as $ext)
      {
        if (extension_loaded($ext) !== true)
        {
          $constraints[] = new Setup_Constraint(sprintf('Extension %s', $ext), false, sprintf('%s missing', $ext), true);
        }
        else
          $constraints[] = new Setup_Constraint(sprintf('Extension %s', $ext), true, sprintf('%s loaded', $ext), true);
      }

      return new Setup_ConstraintsIterator($constraints);
    }

    public static function check_cache_server()
    {
      $availables_caches = array('memcache', 'memcached', 'redis');

      $constraints = array();
      foreach ($availables_caches as $ext)
      {
        if (extension_loaded($ext) === true)
        {
          $constraints[] = new Setup_Constraint(sprintf('Extension %s', $ext), true, sprintf('%s loaded', $ext), false);
        }
        else
          $constraints[] = new Setup_Constraint(sprintf('Extension %s', $ext), false, sprintf('%s not loaded', $ext), false);
      }

      return new Setup_ConstraintsIterator($constraints);
    }

    function check_cache_memcache()
    {

      echo '<h1>' . _('setup:: Serveur Memcached') . '</h1>';
      echo '<ul class="setup">';

      $registry = registry::get_instance();

      if ($registry->get('GV_cache_server_type') !== 'nocache')
      {
        $cache = cache_adapter::get_instance(registry::get_instance());

        if ($cache->ping())
        {
          $stats = $cache->getStats();

          foreach ($stats as $name => $stat)
          {
            echo '<li>Statistics given by `' . $name . '`</li>';
            echo '<li>' . sprintf(_('setup::Serveur actif sur %s'), $registry->get('GV_cache_server_host') . ':' . $registry->get('GV_cache_server_port')) . '</li>';
            echo "<table>";
            foreach ($stat as $key => $value)
            {
              echo "<tr class='even'><td>" . $key . "</td><td> " . $value . "</td></tr>";
            }
            echo "</table>";
          }
        }
        else
        {
          echo '<li class="non-blocker">' . sprintf(_('Le serveur memcached ne repond pas, vous devriez desactiver le cache')) . '</li>';
        }
      }
      else
      {
        echo '<li class="non-blocker">' . sprintf(_('setup::Aucun serveur memcached rattache.')) . '</li>';
      }
      echo '</ul>';
    }

    public static function check_cache_opcode()
    {
      $availables = array('XCache', 'apc', 'eAccelerator', 'phpa', 'WinCache');
      $constraints = array();

      $found = 0;
      foreach ($availables as $ext)
      {
        if (extension_loaded($ext) === true)
        {
          $constraints[] = new Setup_Constraint($ext, true, sprintf('%s loaded', $ext), false);
          $found++;
        }
      }

      if ($found > 1)
        $constraints[] = new Setup_Constraint('Multiple opcode caches', false, 'Many opcode cache load is forbidden', true);
      if ($found === 0)
        $constraints[] = new Setup_Constraint('No opcode cache', false, 'No opcode cache were detected. Phraseanet strongly recommends the use of XCache or APC.', false);

      return new Setup_ConstraintsIterator($constraints);
    }

    public static function check_php_configuration()
    {
      $nonblockers = array('log_errors', 'display_startup_errors', 'display_errors');

      $constraints = array();
      foreach (self::$PHP_REQ as $conf => $value)
      {
        if (($tmp = self::test_php_conf($conf, $value)) === false)
        {
          $constraints[] = new Setup_Constraint($conf, false, sprintf(_('setup::Configuration mauvaise : pour la variable %1$s, configuration donnee : %2$s ; attendue : %3$s'), $conf, $tmp, $value), true);
        }
        else
        {
          $constraints[] = new Setup_Constraint($conf, true, sprintf('%s = %s => OK', $conf, $value), true);
        }
      }
      foreach (self::$PHP_CONF as $conf => $value)
      {
        if (($tmp = self::test_php_conf($conf, $value)) === false)
        {
          $constraints[] = new Setup_Constraint($conf, false, sprintf('Bad configuration for %1$s var ; %2$s given - %3$s supposed', $conf, $tmp, $value), !in_array($conf, $nonblockers));
        }
        else
        {
          $constraints[] = new Setup_Constraint($conf, true, sprintf('%s = %s => OK', $conf, $value), !in_array($conf, $nonblockers));
        }
      }

      return new Setup_ConstraintsIterator($constraints);
    }

    public static function check_sphinx_search()
    {
      $registry = registry::get_instance();

      try
      {
        $engine = new searchEngine_adapter($registry);
        $status = $engine->get_status();

        echo '<h1>' . _('setup::Etat du moteur de recherche') . '</h1>';
        echo '<ul class="setup">';
        foreach ($status as $value)
        {
          echo '<li>' . sprintf('%s : %s', $value[0], $value[1]) . '</li>';
        }
        echo '</ul>';
      }
      catch (Exception $e)
      {

        echo '<h1>' . _('setup::Sphinx confguration') . '</h1>';
        echo '<ul class="setup">';
        echo '<li class="blocker">' . $e->getMessage() . '</li>';
        echo '</ul>';
      }
    }

    /**
     *
     * @return Setup_ConstraintsIterator
     */
    public static function check_system_locales()
    {
      $constraints = array();

      if (!extension_loaded('gettext'))

        return new Setup_ConstraintsIterator($constraints);

      foreach (User_Adapter::$locales as $code => $language_name)
      {
        phrasea::use_i18n($code, 'test');

        if (_('test::test') == 'test')
        {
          $constraints[] = new Setup_Constraint($language_name, true, sprintf('Locale %s (%s) ok', $language_name, $code), false);
        }
        else
        {
          $constraints[] = new Setup_Constraint($language_name, false, sprintf('Locale %s (%s) not installed', $language_name, $code), false);
        }
      }
      phrasea::use_i18n(Session_Handler::get_locale());

      return new Setup_ConstraintsIterator($constraints);
    }

    private static function test_php_conf($conf, $value)
    {
      $is_flag = false;
      $flags = array('on', 'off', '1', '0', '');
      if (in_array(strtolower($value), $flags))
        $is_flag = true;
      $current = ini_get($conf);
      if ($is_flag)
        $current = strtolower($current);

      if (($current === '' || $current === 'off' || $current === '0') && $is_flag)
        if ($value === 'off' || $value === '0' || $value === '')

          return $current;
      if (($current === '1' || $current === 'on') && $is_flag)
        if ($value === 'on' || $value === '1')

          return $current;
      if ($current === $value)

        return $current;
    }

    public static function write_config($servername)
    {
      $datas = "<?php\n\$servername = \""
              . str_replace('"', '\"', $servername)
              . "\";\n\$maintenance=false;\n\$debug=false;\n";

      file_put_contents(self::get_config_filepath(), $datas);

      return;
    }

    public static function get_config_filepath()
    {
      return dirname(__FILE__) . '/../../config/config.inc';
    }

    public static function get_connexion_filepath()
    {
      return dirname(__FILE__) . '/../../config/connexion.inc';
    }

    public static function rollback(connection_pdo $conn, connection_pdo $connbas =null)
    {
      $structure = simplexml_load_file(dirname(__FILE__) . "/../../lib/conf.d/bases_structure.xml");

      if (!$structure)
        throw new Exception('Unable to load schema');

      $appbox = $structure->appbox;
      $databox = $structure->databox;

      foreach ($appbox->tables->table as $table)
      {
        try
        {
          $sql = 'DROP TABLE `' . $table['name'] . '`';
          $stmt = $conn->prepare($sql);
          $stmt->execute();
          $stmt->closeCursor();
        }
        catch (Exception $e)
        {

        }
      }
      if ($connbas)
      {
        foreach ($databox->tables->table as $table)
        {
          try
          {
            $sql = 'DROP TABLE `' . $table['name'] . '`';
            $stmt = $connbas->prepare($sql);
            $stmt->execute();
            $stmt->closeCursor();
          }
          catch (Exception $e)
          {

          }
        }
      }
      $connexion = dirname(__FILE__) . "/../../config/connexion.inc";

      if (file_exists($connexion))
        unlink($connexion);

      return;
    }

  }
