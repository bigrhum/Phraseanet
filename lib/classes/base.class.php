<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

abstract class base implements cache_cacheableInterface
{

  protected $version;

  /**
   *
   * @var int
   */
  protected $id;

  /**
   *
   * @var string
   */
  protected $schema;

  /**
   *
   * @var <type>
   */
  protected $dbname;

  /**
   *
   * @var <type>
   */
  protected $passwd;

  /**
   * Database Username
   *
   * @var <type>
   */
  protected $user;

  /**
   *
   * @var <type>
   */
  protected $port;

  /**
   *
   * @var <type>
   */
  protected $host;

  /**
   *
   */
  const APPLICATION_BOX = 'APPLICATION_BOX';
  /**
   *
   */
  const DATA_BOX = 'DATA_BOX';

  /**
   *
   */
  abstract function get_base_type();

  /**
   *
   * @return <type>
   */
  public function get_schema()
  {
    if ($this->schema)

      return $this->schema;

    $this->load_schema();

    return $this->schema;
  }

  /**
   *
   * @return <type>
   */
  public function get_dbname()
  {
    return $this->dbname;
  }

  /**
   *
   * @return <type>
   */
  public function get_passwd()
  {
    return $this->passwd;
  }

  /**
   *
   * @return <type>
   */
  public function get_user()
  {
    return $this->user;
  }

  /**
   *
   * @return <type>
   */
  public function get_port()
  {
    return $this->port;
  }

  /**
   *
   * @return <type>
   */
  public function get_host()
  {
    return $this->host;
  }

  /**
   *
   * @return registry
   */
  public function get_registry()
  {
    return $this->registry;
  }

  /**
   *
   * @param registryInterface $registry
   * @return base
   */
  public function set_registry(registryInterface $registry)
  {
    $this->registry = $registry;

    return $this;
  }

  /**
   *
   * @return connection_pdo
   */
  public function get_connection()
  {
    return $this->connection;
  }

  /**
   *
   * @return cache_adapter
   */
  public function get_cache()
  {
    if (!$this->cache)
      $this->cache = cache_adapter::get_instance($this->get_registry());

    return $this->cache;
  }

  /**
   *
   * @param <type> $option
   * @return <type>
   */
  public function get_data_from_cache($option = null)
  {
    return $this->get_cache()->get($this->get_cache_key($option));
  }

  public function set_data_to_cache($value, $option = null, $duration = 0)
  {
    return $this->get_cache()->set($this->get_cache_key($option), $value, $duration);
  }

  public function delete_data_from_cache($option = null)
  {

    if ($option === appbox::CACHE_LIST_BASES)
    {
      $keys = array($this->get_cache_key(appbox::CACHE_LIST_BASES));
      phrasea::reset_sbasDatas();
      phrasea::reset_baseDatas();
      phrasea::clear_sbas_params();
      $keys[] = $this->get_cache_key(appbox::CACHE_SBAS_IDS);

      return $this->get_cache()->deleteMulti($keys);
    }

    if (is_array($option))
    {
      foreach ($option as $key => $value)
        $option[$key] = $this->get_cache_key($value);

      return $this->get_cache()->deleteMulti($option);
    }
    else
    {
      return $this->get_cache()->delete($this->get_cache_key($option));
    }
  }

  public function get_cache_key($option = null)
  {
    throw new Exception(__METHOD__ . ' must be defined in extended class');
  }

  public function need_major_upgrade()
  {
    return (version_compare($this->get_version(), '3.2.0.0a1', '<') === true);
  }

  public function get_version()
  {

    if ($this->version)

      return $this->version;

    $version = '0.0.0';

    $sql = '';
    if ($this->get_base_type() == self::APPLICATION_BOX)
      $sql = 'SELECT version FROM sitepreff';
    if ($this->get_base_type() == self::DATA_BOX)
      $sql = 'SELECT value AS version FROM pref WHERE prop="version" LIMIT 1;';

    if ($sql !== '')
    {
      $stmt = $this->get_connection()->prepare($sql);
      $stmt->execute();
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      $stmt->closeCursor();
      if ($row)
        $version = $row['version'];
    }

    $this->version = $version;

    return $this->version;
  }

  public function upgradeavailable()
  {
    if ($this->get_version())

      return version_compare(GV_version, $this->get_version(), '>');
    else

      return true;
  }

  protected function upgradeDb($apply_patches, Setup_Upgrade &$upgrader)
  {
    require_once dirname(__FILE__) . '/../version.inc';

    $recommends = array();

    $allTables = array();

    $schema = $this->get_schema();

    foreach ($schema->tables->table as $table)
      $allTables[(string) $table['name']] = $table;

    $upgrader->add_steps(count($allTables) + 1);

    $sql = "SHOW TABLE STATUS";
    $stmt = $this->get_connection()->prepare($sql);
    $stmt->execute();
    $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();


    foreach ($rs as $row)
    {
      $tname = $row["Name"];

      if (isset($allTables[$tname]))
      {
        $upgrader->set_current_message(sprintf(_('Updating table %s'), $tname));

        $engine = strtolower(trim($allTables[$tname]->engine));
        $ref_engine = strtolower($row['Engine']);

        if ($engine != $ref_engine && in_array($engine, array('innodb', 'myisam')))
        {
          $sql = 'ALTER TABLE `' . $tname . '` ENGINE = ' . $engine;
          try
          {
            $stmt = $this->get_connection()->prepare($sql);
            $stmt->execute();
            $stmt->closeCursor();
          }
          catch (Exception $e)
          {
            $recommends[] = array(
                'message' => sprintf(_('Erreur lors de la tentative ; errreur : %s'), $e->getMessage()),
                'sql' => $sql
            );
          }
        }

        $ret = self::upgradeTable($allTables[$tname]);
        $recommends = array_merge($recommends, $ret);
        unset($allTables[$tname]);
        $upgrader->add_steps_complete(1);
      }
      else
      {
        $recommends[] = array(
            'message' => 'Une table pourrait etre supprime',
            'sql' => 'DROP TABLE ' . $this->dbname . '.`' . $tname . '`;'
        );
      }
    }

    foreach ($allTables as $tname => $table)
    {
      $upgrader->set_current_message(sprintf(_('Creating table %s'), $table));
      $this->createTable($table);
      $upgrader->add_steps_complete(1);
    }
    $current_version = $this->get_version();

    $upgrader->set_current_message(sprintf(_('Applying patches on %s'), $this->get_dbname()));
    if ($apply_patches)
    {
      $this->apply_patches($current_version, GV_version, false, $upgrader);
    }
    $upgrader->add_steps_complete(1);

    return $recommends;
  }

  protected function setVersion($version)
  {
    try
    {
      $sql = '';
      if ($this->get_base_type() === self::APPLICATION_BOX)
        $sql = 'UPDATE sitepreff SET version = "' . $version . '"';
      if ($this->get_base_type() === self::DATA_BOX)
      {
        $sql = 'DELETE FROM pref WHERE prop="version" AND locale IS NULL';
        $this->get_connection()->query($sql);
        $sql = 'REPLACE INTO pref (id, prop, value,locale, updated_on) VALUES (null, "version", :version,"", NOW())';
      }
      if ($sql !== '')
      {
        $stmt = $this->get_connection()->prepare($sql);
        $stmt->execute(array(':version' => $version));
        $stmt->closeCursor();

        $this->version = $version;

        return true;
      }
    }
    catch (Exception $e)
    {
      throw new Exception('Unable to set the database version');
    }

    return;
  }

  /**
   *
   * @return base
   */
  protected function load_schema()
  {
    if ($this->schema)

      return $this;

    $structure = simplexml_load_file(dirname(__FILE__) . "/../../lib/conf.d/bases_structure.xml");

    if (!$structure)
      throw new Exception('Unable to load schema');

    if ($this->get_base_type() === self::APPLICATION_BOX)
      $this->schema = $structure->appbox;
    elseif ($this->get_base_type() === self::DATA_BOX)
      $this->schema = $structure->databox;
    else
      throw new Exception('Unknown schema type');

    return $this;
  }

  /**
   *
   * @return base
   */
  protected function insert_datas()
  {
    $this->load_schema();

    foreach ($this->get_schema()->tables->table as $table)
    {
      $this->createTable($table);
    }

    if (defined('GV_version'))
      $this->setVersion(GV_version);

    return $this;
  }

  /**
   *
   * @param SimpleXMLElement $table
   * @return base
   */
  protected function createTable(SimpleXMLElement $table)
  {
    $field_stmt = $defaults_stmt = array();

    $create_stmt = "CREATE TABLE `" . $table['name'] . "` (";

    foreach ($table->fields->field as $field)
    {
      $isnull = trim($field->null) == "" ? "NOT NULL" : "NULL";

      if (trim($field->default) != "" && trim($field->default) != "CURRENT_TIMESTAMP")
        $is_default = " default '" . $field->default . "'";
      elseif (trim($field->default) == "CURRENT_TIMESTAMP")
        $is_default = " default " . $field->default;
      else
        $is_default = '';

      $character_set = '';
      if (in_array(strtolower((string) $field->type), array('text', 'longtext', 'mediumtext', 'tinytext'))
              || substr(strtolower((string) $field->type), 0, 7) == 'varchar'
              || in_array(substr(strtolower((string) $field->type), 0, 4), array('char', 'enum')))
      {

        $collation = trim((string) $field->collation) != '' ? trim((string) $field->collation) : 'utf8_unicode_ci';

        $code = array_pop(array_reverse(explode('_', $collation)));

        $character_set = ' CHARACTER SET ' . $code . ' COLLATE ' . $collation;
      }

      $field_stmt[] = " `" . $field->name . "` " . $field->type . " "
              . $field->extra . " " . $character_set . " "
              . $is_default . " " . $isnull . "";
    }


    if ($table->indexes)
    {
      foreach ($table->indexes->index as $index)
      {
        switch ($index->type)
        {
          case "PRIMARY":
            $primary_fields = array();

            foreach ($index->fields->field as $field)
            {
              $primary_fields[] = "`" . $field . "`";
            }

            $field_stmt[] = 'PRIMARY KEY (' . implode(',', $primary_fields) . ')';
            break;
          case "UNIQUE":
            $unique_fields = array();

            foreach ($index->fields->field as $field)
            {
              $unique_fields[] = "`" . $field . "`";
            }

            $field_stmt[] = 'UNIQUE KEY `' . $index->name . '` (' . implode(',', $unique_fields) . ')';
            break;
          case "INDEX":
            $index_fields = array();

            foreach ($index->fields->field as $field)
            {
              $index_fields[] = "`" . $field . "`";
            }

            $field_stmt[] = 'KEY `' . $index->name . '` (' . implode(',', $index_fields) . ')';
            break;
        }
      }
    }
    if ($table->defaults)
    {
      foreach ($table->defaults->default as $default)
      {
        $k = $v = $params = $dates_values = array();
        $nonce = random::generatePassword(16);
        foreach ($default->data as $data)
        {
          $k = trim($data['key']);
          if ($k === 'usr_password')
            $data = User_Adapter::salt_password($data, $nonce);
          if ($k === 'nonce')
            $data = $nonce;
          $v = trim(str_replace(array("\r\n", "\r", "\n", "\t"), '', $data));

          if (trim(mb_strtolower($v)) == 'now()')
            $dates_values [$k] = 'NOW()';
          else
            $params[$k] = (trim(mb_strtolower($v)) == 'null' ? null : $v);
        }

        $separator = ((count($params) > 0 && count($dates_values) > 0) ? ', ' : '');

        $defaults_stmt[] = array(
            'sql' =>
            'INSERT INTO `' . $table['name'] . '` (' . implode(', ', array_keys($params))
            . $separator . implode(', ', array_keys($dates_values)) . ')
                      VALUES (:' . implode(', :', array_keys($params))
            . $separator . implode(', ', array_values($dates_values)) . ') '
            , 'params' => $params
        );
      }
    }

    $engine = mb_strtolower(trim($table->engine));
    $engine = in_array($engine, array('innodb', 'myisam')) ? $engine : 'innodb';

    $create_stmt .= implode(',', $field_stmt);
    $create_stmt .= ") ENGINE=" . $engine . " CHARACTER SET utf8 COLLATE utf8_unicode_ci;";

    $stmt = $this->get_connection()->prepare($create_stmt);
    $stmt->execute();
    $stmt->closeCursor();

    foreach ($defaults_stmt as $def)
    {
      try
      {
        $stmt = $this->get_connection()->prepare($def['sql']);
        $stmt->execute($def['params']);
        $stmt->closeCursor();
      }
      catch (Exception $e)
      {
        $recommends[] = array(
            'message' => sprintf(_('Erreur lors de la tentative ; errreur : %s'), $e->getMessage()),
            'sql' => $def['sql']
        );
      }
    }

    return $this;
  }

  protected function upgradeTable(SimpleXMLElement $table)
  {
    $correct_table = array('fields' => array(), 'indexes' => array(), 'collation' => array());
    $alter = $alter_pre = $return = array();

    $registry = registry::get_instance();

    foreach ($table->fields->field as $field)
    {
      $expr = trim((string) $field->type);

      $_extra = trim((string) $field->extra);
      if ($_extra)
        $expr .= ' ' . $_extra;

      $collation = trim((string) $field->collation) != '' ? trim((string) $field->collation) : 'utf8_unicode_ci';

      if (in_array(strtolower((string) $field->type), array('text', 'longtext', 'mediumtext', 'tinytext'))
              || substr(strtolower((string) $field->type), 0, 7) == 'varchar'
              || in_array(substr(strtolower((string) $field->type), 0, 4), array('char', 'enum')))
      {
        $code = array_pop(array_reverse(explode('_', $collation)));

        $collation = ' CHARACTER SET ' . $code . ' COLLATE ' . $collation;

        $correct_table['collation'][trim((string) $field->name)] = $collation;

        $expr .= $collation;
      }

      $_null = mb_strtolower(trim((string) $field->null));
      if (!$_null || $_null == 'no')
        $expr .= ' NOT NULL';

      $_default = (string) $field->default;
      if ($_default && $_default != 'CURRENT_TIMESTAMP')
        $expr .= ' DEFAULT \'' . $_default . '\'';
      elseif ($_default == 'CURRENT_TIMESTAMP')
        $expr .= ' DEFAULT ' . $_default . '';

      $correct_table['fields'][trim((string) $field->name)] = $expr;
    }
    if ($table->indexes)
    {
      foreach ($table->indexes->index as $index)
      {
        $i_name = (string) $index->name;
        $expr = array();
        foreach ($index->fields->field as $field)
          $expr[] = '`' . trim((string) $field) . '`';

        $expr = implode(', ', $expr);

        switch ((string) $index->type)
        {
          case "PRIMARY":
            $correct_table['indexes']['PRIMARY'] = 'PRIMARY KEY (' . $expr . ')';
            break;
          case "UNIQUE":
            $correct_table['indexes'][$i_name] = 'UNIQUE KEY `' . $i_name . '` (' . $expr . ')';
            break;
          case "INDEX":
            $correct_table['indexes'][$i_name] = 'KEY `' . $i_name . '` (' . $expr . ')';
            break;
        }
      }
    }


    $sql = "SHOW FULL FIELDS FROM `" . $table['name'] . "`";
    $stmt = $this->get_connection()->prepare($sql);
    $stmt->execute();
    $rs2 = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    foreach ($rs2 as $row2)
    {
      $f_name = $row2['Field'];
      $expr_found = trim($row2['Type']);

      $_extra = $row2['Extra'];

      if ($_extra)
        $expr_found .= ' ' . $_extra;

      $_collation = $row2['Collation'];

      $current_collation = '';

      if ($_collation)
      {
        $_collation = explode('_', $row2['Collation']);

        $expr_found .= $current_collation = ' CHARACTER SET ' . $_collation[0] . ' COLLATE ' . implode('_', $_collation);
      }

      $_null = mb_strtolower(trim($row2['Null']));

      if (!$_null || $_null == 'no')
        $expr_found .= ' NOT NULL';

      $_default = $row2['Default'];

      if ($_default)
      {
        if (trim($row2['Type']) == 'timestamp' && $_default == 'CURRENT_TIMESTAMP')
          $expr_found .= ' DEFAULT CURRENT_TIMESTAMP';
        else
          $expr_found .= ' DEFAULT \'' . $_default . '\'';
      }


      if (isset($correct_table['fields'][$f_name]))
      {
        if (isset($correct_table['collation'][$f_name]) && $correct_table['collation'][$f_name] != $current_collation)
        {
          $old_type = mb_strtolower(trim($row2['Type']));
          $new_type = false;

          switch ($old_type)
          {
            case 'text':
              $new_type = 'blob';
              break;
            case 'longtext':
              $new_type = 'longblob';
              break;
            case 'mediumtext':
              $new_type = 'mediumblob';
              break;
            case 'tinytext':
              $new_type = 'tinyblob';
              break;
            default:
              if (substr($old_type, 0, 4) == 'char')
                $new_type = 'varbinary(255)';
              if (substr($old_type, 0, 7) == 'varchar')
                $new_type = 'varbinary(767)';
              break;
          }

          if ($new_type)
          {
            $alter_pre[] = "ALTER TABLE `" . $table['name'] . "` CHANGE `$f_name` `$f_name` " . $new_type . "";
          }
        }

        if (strtolower($expr_found) !== strtolower($correct_table['fields'][$f_name]))
        {
          $alter[] = "ALTER TABLE `" . $table['name'] . "` CHANGE `$f_name` `$f_name` " . $correct_table['fields'][$f_name];
        }
        unset($correct_table['fields'][$f_name]);
      }
      else
      {
        $return[] = array(
            'message' => 'Un champ pourrait etre supprime',
            'sql' => "ALTER TABLE " . $this->dbname . ".`" . $table['name'] . "` DROP `$f_name`;"
        );
      }
    }

    foreach ($correct_table['fields'] as $f_name => $expr)
    {
      $alter[] = "ALTER TABLE `" . $table['name'] . "` ADD `$f_name` " . $correct_table['fields'][$f_name];
    }

    $tIndex = array();
    $sql = "SHOW INDEXES FROM `" . $table['name'] . "`";
    $stmt = $this->get_connection()->prepare($sql);
    $stmt->execute();
    $rs2 = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    foreach ($rs2 as $row2)
    {
      if (!isset($tIndex[$row2['Key_name']]))
        $tIndex[$row2['Key_name']] = array('unique' => ((int) ($row2['Non_unique']) == 0), 'columns' => array());
      $tIndex[$row2['Key_name']]['columns'][(int) ($row2['Seq_in_index'])] = $row2['Column_name'];
    }

    foreach ($tIndex as $kIndex => $vIndex)
    {
      $strColumns = array();

      foreach ($vIndex['columns'] as $column)
        $strColumns[] = '`' . $column . '`';

      $strColumns = '(' . implode(', ', $strColumns) . ')';

      if ($kIndex == 'PRIMARY')
        $expr_found = 'PRIMARY KEY ' . $strColumns;
      else
      {
        if ($vIndex['unique'])
          $expr_found = 'UNIQUE KEY `' . $kIndex . '` ' . $strColumns;
        else
          $expr_found = 'KEY `' . $kIndex . '` ' . $strColumns;
      }

      $full_name_index = ($kIndex == 'PRIMARY') ? 'PRIMARY KEY' : ('INDEX `' . $kIndex . '`');

      if (isset($correct_table['indexes'][$kIndex]))
      {

        if (mb_strtolower($expr_found) !== mb_strtolower($correct_table['indexes'][$kIndex]))
        {
          $alter[] = 'ALTER TABLE `' . $table['name'] . '` DROP ' . $full_name_index . ', ADD ' . $correct_table['indexes'][$kIndex];
        }

        unset($correct_table['indexes'][$kIndex]);
      }
      else
      {
        $return[] = array(
            'message' => 'Un index pourrait etre supprime',
            'sql' => 'ALTER TABLE ' . $this->dbname . '.`' . $table['name'] . '` DROP ' . $full_name_index . ';'
        );
      }
    }

    foreach ($correct_table['indexes'] as $kIndex => $expr)
      $alter[] = 'ALTER TABLE `' . $table['name'] . '` ADD ' . $expr;

    foreach ($alter_pre as $a)
    {
      try
      {
        $stmt = $this->get_connection()->prepare($a);
        $stmt->execute();
        $stmt->closeCursor();
      }
      catch (Exception $e)
      {
        $return[] = array(
            'message' => sprintf(_('Erreur lors de la tentative ; errreur : %s'), $e->getMessage()),
            'sql' => $a
        );
      }
    }

    foreach ($alter as $a)
    {
      try
      {
        $stmt = $this->get_connection()->prepare($a);
        $stmt->execute();
        $stmt->closeCursor();
      }
      catch (Exception $e)
      {
        $return[] = array(
            'message' => sprintf(_('Erreur lors de la tentative ; errreur : %s'), $e->getMessage()),
            'sql' => $a
        );
      }
    }

    return $return;
  }

  protected function apply_patches($from, $to, $post_process, Setup_Upgrade &$upgrader)
  {
    if (version_compare($from, $to, '='))

      return true;

    $list_patches = array();

    $registry = registry::get_instance();

    $upgrader->add_steps(1)->set_current_message(_('Looking for patches'));

    $iterator = new DirectoryIterator($registry->get('GV_RootPath') . 'lib/classes/patch/');

    foreach ($iterator as $fileinfo)
    {
      if (!$fileinfo->isDot())
      {
        if (substr($fileinfo->getFilename(), 0, 1) == '.')
          continue;

        $classname = 'patch_' . array_pop(array_reverse(explode('.', $fileinfo->getFilename())));

        $patch = new $classname();

        if (!in_array($this->get_base_type(), $patch->concern()))
          continue;

        if (!!$post_process !== !!$patch->require_all_upgrades())
          continue;

        if (!version_compare($patch->get_release(), $from, '>') || !version_compare($patch->get_release(), $to, '<='))
        {
          continue;
        }

        $n = 0;
        do
        {
          $key = $patch->get_release() . '.' . $n;
          $n++;
        }
        while (isset($list_patches[$key]));

        $list_patches[$key] = $patch;
      }
    }

    $upgrader->add_steps_complete(1)
            ->add_steps(count($list_patches))
            ->set_current_message(sprintf(_('Applying patches on %s'), $this->get_dbname()));
    ksort($list_patches);

    $success = true;

    foreach ($list_patches as $patch)
    {
      try
      {
      if (!$patch->apply($this))
        $success = false;
      }
      catch(Exception $e)
      {
        $success = false;
      }
      $upgrader->add_steps_complete(1);
    }

    return $success;
  }

}
