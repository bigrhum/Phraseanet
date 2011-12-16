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
 * @package     Bridge
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Bridge_Account
{

  /**
   *
   * @var int
   */
  protected $id;

  /**
   *
   * @var appbox
   */
  protected $appbox;

  /**
   *
   * @var Bridge_Api
   */
  protected $api;

  /**
   *
   * @var string
   */
  protected $dist_id;

  /**
   *
   * @var User_Adapter
   */
  protected $user;

  /**
   *
   * @var string
   */
  protected $name;

  /**
   *
   * @var Bridge_AccountSettings
   */
  protected $settings;

  /**
   *
   * @var DateTime
   */
  protected $created_on;

  /**
   *
   * @var DateTime
   */
  protected $updated_on;

  /**
   *
   * @param appbox $appbox
   * @param Bridge_Api $api
   * @param int $id
   * @return Bridge_Account
   */
  public function __construct(appbox &$appbox, Bridge_Api &$api, $id)
  {
    $this->id = (int) $id;
    $this->appbox = $appbox;
    $this->api = $api;

    $this->api->get_connector()->set_auth_settings($this->get_settings());

    $sql = 'SELECT id, dist_id, usr_id, name, created_on, updated_on
            FROM bridge_accounts WHERE id = :id';

    $stmt = $this->appbox->get_connection()->prepare($sql);
    $stmt->execute(array(':id' => $this->id));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if (!$row)
      throw new Bridge_Exception_AccountNotFound('Account Not Found');

    $this->dist_id = $row['dist_id'];
    $this->user = User_Adapter::getInstance($row['usr_id'], $this->appbox);
    $this->name = $row['name'];
    $this->updated_on = new DateTime($row['updated_on']);
    $this->created_on = new DateTime($row['created_on']);

    return $this;
  }

  /**
   *
   * @return Bridge_AccountSettings
   */
  public function get_settings()
  {
    if (!$this->settings)
      $this->settings = new Bridge_AccountSettings($this->appbox, $this);

    return $this->settings;
  }

  /**
   *
   * @return int
   */
  public function get_id()
  {
    return $this->id;
  }

  /**
   *
   * @return Bridge_Api
   */
  public function get_api()
  {
    return $this->api;
  }

  /**
   *
   * @return string
   */
  public function get_dist_id()
  {
    return $this->dist_id;
  }

  /**
   *
   * @return User_Adapter
   */
  public function get_user()
  {
    return $this->user;
  }

  /**
   *
   * @return string
   */
  public function get_name()
  {
    return $this->name;
  }

  /**
   *
   * @return DateTime
   */
  public function get_created_on()
  {
    return $this->created_on;
  }

  /**
   *
   * @return DateTime
   */
  public function get_updated_on()
  {
    return $this->updated_on;
  }

  /**
   *
   * @param string $name
   * @return Bridge_Account
   */
  public function set_name($name)
  {
    $this->name = $name;
    $this->updated_on = new DateTime();

    $sql = 'UPDATE bridge_accounts
            SET name = :name, updated_on = :update WHERE id = :id';

    $params = array(
        ':name' => $this->name
        , ':id' => $this->id
        , ':update' => $this->updated_on->format(DATE_ISO8601)
    );

    $stmt = $this->appbox->get_connection()->prepare($sql);
    $stmt->execute($params);
    $stmt->closeCursor();

    return $this;
  }

  /**
   *
   * @return Void
   */
  public function delete()
  {
    do
    {
      $elements = Bridge_Element::get_elements_by_account($this->appbox, $this);
      foreach ($elements as $element)
      {
        $element->delete();
      }
    }
    while (count($elements) > 0);

    $sql = 'DELETE FROM bridge_accounts WHERE id = :id';

    $stmt = $this->appbox->get_connection()->prepare($sql);
    $stmt->execute(array(':id' => $this->id));
    $stmt->closeCursor();

    return;
  }

  /**
   *
   * @param appbox $appbox
   * @param int $account_id
   * @return Bridge_Account
   */
  public static function load_account(appbox &$appbox, $account_id)
  {
    $sql = 'SELECT id, api_id FROM bridge_accounts WHERE id = :account_id';

    $stmt = $appbox->get_connection()->prepare($sql);
    $stmt->execute(array(':account_id' => $account_id));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if (!$row)
      throw new Bridge_Exception_AccountNotFound('Account Not Found');

    $api = new Bridge_Api($appbox, $row['api_id']);
    $api->get_connector()->set_locale($appbox->get_session()->get_locale());

    return new self($appbox, $api, $row['id']);
  }

  /**
   *
   * @param appbox $appbox
   * @param Bridge_Api $api
   * @param User_Adapter $user
   * @param string $distant_id
   * @return Bridge_Account
   */
  public static function load_account_from_distant_id(appbox $appbox, Bridge_Api $api, User_Adapter $user, $distant_id)
  {
    $sql = 'SELECT id FROM bridge_accounts
            WHERE api_id = :api_id AND usr_id = :usr_id AND dist_id = :dist_id';

    $params = array(
        ':api_id' => $api->get_id()
        , ':usr_id' => $user->get_id()
        , ':dist_id' => $distant_id
    );

    $stmt = $appbox->get_connection()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if (!$row)
      throw new Bridge_Exception_AccountNotFound();

    return new Bridge_Account($appbox, $api, $row['id']);
  }

  /**
   *
   * @param appbox $appbox
   * @param Bridge_Api $api
   * @param int $quantity
   * @return Bridge_Account
   */
  public static function get_accounts_by_api(appbox &$appbox, Bridge_Api &$api, $quantity = 50)
  {
    $sql = 'SELECT id FROM bridge_accounts WHERE api_id = :api_id
            LIMIT 0,' . (int) $quantity;

    $stmt = $appbox->get_connection()->prepare($sql);
    $stmt->execute(array(':api_id' => $api->get_id()));
    $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    $results = array();

    foreach ($rs as $row)
    {
      $results[] = new Bridge_Account($appbox, $api, $row['id']);
    }

    return $results;
  }

  /**
   *
   * @param appbox $appbox
   * @param user_adapter $user
   * @return Bridge_Account
   */
  public static function get_accounts_by_user(appbox &$appbox, user_adapter &$user)
  {
    $sql = 'SELECT id, api_id FROM bridge_accounts WHERE usr_id = :usr_id';

    $stmt = $appbox->get_connection()->prepare($sql);
    $stmt->execute(array(':usr_id' => $user->get_id()));
    $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    $results = array();
    $apis = array();


    foreach ($rs as $row)
    {
      $api_id = $row['api_id'];
      if (!isset($apis[$api_id]))
      {
        try
        {
          $apis[$api_id] = new Bridge_Api($appbox, $api_id);
        }
        catch (Exception $e)
        {
          continue;
        }
      }
      $results[] = new Bridge_Account($appbox, $apis[$api_id], $row['id']);
    }

    return $results;
  }

  /**
   *
   * @param appbox $appbox
   * @param Bridge_Api $api
   * @param User_Adapter $user
   * @param string $dist_id
   * @param string $name
   * @return Bridge_Account
   */
  public static function create(appbox &$appbox, Bridge_Api &$api, User_Adapter &$user, $dist_id, $name)
  {
    $sql = 'INSERT INTO bridge_accounts
            (id, api_id, dist_id, usr_id, name, created_on, updated_on)
            VALUES (null, :api_id, :dist_id, :usr_id, :name, NOW(), NOW())';

    $params = array(
        ':api_id' => $api->get_id()
        , ':dist_id' => $dist_id
        , ':usr_id' => $user->get_id()
        , ':name' => $name
    );

    $stmt = $appbox->get_connection()->prepare($sql);
    $stmt->execute($params);
    $stmt->closeCursor();

    $account_id = $appbox->get_connection()->lastInsertId();

    return new self($appbox, $api, $account_id);
  }

}