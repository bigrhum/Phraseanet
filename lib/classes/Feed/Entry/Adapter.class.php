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
 * @package     Feeds
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Feed_Entry_Adapter implements Feed_Entry_Interface, cache_cacheableInterface
{

  /**
   *
   * @var appbox
   */
  protected $appbox;

  /**
   *
   * @var int
   */
  protected $id;

  /**
   *
   * @var string
   */
  protected $title;

  /**
   *
   * @var string
   */
  protected $subtitle;

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
   * @var Feed_Publisher_Adapter
   */
  protected $publisher;

  /**
   *
   * @var int
   */
  protected $publisher_id;

  /**
   *
   * @var String
   */
  protected $author_name;

  /**
   *
   * @var String
   */
  protected $author_email;

  /**
   *
   * @var Feed_Adapter
   */
  protected $feed;

  /**
   *
   * @var array
   */
  protected $items;

  const CACHE_ELEMENTS = 'elements';

  /**
   *
   * @param appbox $appbox
   * @param Feed_Adapter $feed
   * @param int $id
   * @return Feed_Entry_Adapter
   */
  public function __construct(appbox &$appbox, Feed_Adapter &$feed, $id)
  {
    $this->appbox = $appbox;
    $this->feed = $feed;
    $this->id = (int) $id;
    $this->load();

    return $this;
  }

  /**
   *
   * @return Feed_Entry_Adapter
   */
  protected function load()
  {
    try
    {
      $datas = $this->get_data_from_cache();

      $this->title = $datas['title'];
      $this->subtitle = $datas['subtitle'];
      $this->author_name = $datas['author_name'];
      $this->author_email = $datas['author_email'];
      $this->publisher_id = $datas['publisher_id'];
      $this->updated_on = $datas['updated_on'];
      $this->created_on = $datas['created_on'];

      return $this;
    }
    catch (Exception $e)
    {

    }

    $sql = 'SELECT publisher, title, description, created_on, updated_on
              , author_name, author_email
            FROM feed_entries
            WHERE id = :id ';

    $stmt = $this->appbox->get_connection()->prepare($sql);
    $stmt->execute(array(':id' => $this->id));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if (!$row)
      throw new Exception_Feed_EntryNotFound();

    $this->title = $row['title'];
    $this->subtitle = $row['description'];
    $this->author_name = $row['author_name'];
    $this->author_email = $row['author_email'];
    $this->publisher_id = $row['publisher'];
    $this->updated_on = new DateTime($row['updated_on']);
    $this->created_on = new DateTime($row['created_on']);

    $datas = array(
        'title' => $this->title
        , 'subtitle' => $this->subtitle
        , 'author_name' => $this->author_name
        , 'author_email' => $this->author_email
        , 'publisher_id' => $this->publisher_id
        , 'updated_on' => $this->updated_on
        , 'created_on' => $this->created_on
    );

    $this->set_data_to_cache($datas);

    return $this;
  }

  public function get_link()
  {
    $registry = registry::get_instance();

    $href = sprintf(
            '%slightbox/feeds/entry/%d/'
            , $registry->get('GV_ServerName')
            , $this->get_id()
    );

    return new Feed_Link($href, $this->get_title(), 'text/html');
  }

  /**
   *
   * @return Feed_Adapter
   */
  public function get_feed()
  {
    return $this->feed;
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
   * @return string
   */
  public function get_title()
  {
    return $this->title;
  }

  /**
   *
   * @return string
   */
  public function get_subtitle()
  {
    return $this->subtitle;
  }

  /**
   *
   * @param string $title
   * @return Feed_Entry_Adapter
   */
  public function set_title($title)
  {
    $title = trim(strip_tags($title));

    if ($title === '')
      throw new Exception_InvalidArgument();

    $sql = 'UPDATE feed_entries
            SET title = :title, updated_on = NOW() WHERE id = :entry_id';
    $stmt = $this->appbox->get_connection()->prepare($sql);
    $stmt->execute(array(':title' => $title, ':entry_id' => $this->get_id()));
    $stmt->closeCursor();
    $this->title = $title;
    $this->delete_data_from_cache();

    return $this;
  }

  /**
   *
   * @param string $subtitle
   * @return Feed_Entry_Adapter
   */
  public function set_subtitle($subtitle)
  {
    $subtitle = strip_tags($subtitle);

    $sql = 'UPDATE feed_entries
            SET description = :subtitle, updated_on = NOW()
            WHERE id = :entry_id';
    $params = array(':subtitle' => $subtitle, ':entry_id' => $this->get_id());
    $stmt = $this->appbox->get_connection()->prepare($sql);
    $stmt->execute($params);
    $stmt->closeCursor();
    $this->subtitle = $subtitle;
    $this->delete_data_from_cache();

    return $this;
  }

  /**
   *
   * @param string $author_name
   * @return Feed_Entry_Adapter
   */
  public function set_author_name($author_name)
  {
    $sql = 'UPDATE feed_entries
            SET author_name = :author_name, updated_on = NOW()
            WHERE id = :entry_id';
    $params = array(
        ':author_name' => $author_name,
        ':entry_id' => $this->get_id()
    );
    $stmt = $this->appbox->get_connection()->prepare($sql);
    $stmt->execute($params);
    $stmt->closeCursor();
    $this->author_name = $author_name;
    $this->delete_data_from_cache();

    return $this;
  }

  /**
   *
   * @param string $author_email
   * @return Feed_Entry_Adapter
   */
  public function set_author_email($author_email)
  {
    $sql = 'UPDATE feed_entries
            SET author_email = :author_email, updated_on = NOW()
            WHERE id = :entry_id';
    $params = array(
        ':author_email' => $author_email,
        ':entry_id' => $this->get_id()
    );
    $stmt = $this->appbox->get_connection()->prepare($sql);
    $stmt->execute($params);
    $stmt->closeCursor();
    $this->author_email = $author_email;
    $this->delete_data_from_cache();

    return $this;
  }

  public function set_created_on(DateTime $datetime)
  {
    $sql = 'UPDATE feed_entries
            SET created_on = :created_on
            WHERE id = :entry_id';
    $params = array(
        ':created_on' => $datetime->format(DATE_ISO8601),
        ':entry_id' => $this->get_id()
    );
    $stmt = $this->appbox->get_connection()->prepare($sql);
    $stmt->execute($params);
    $stmt->closeCursor();
    $this->created_on = $datetime;
    $this->delete_data_from_cache();

    return $this;
  }

  public function set_updated_on(DateTime $datetime)
  {
    $sql = 'UPDATE feed_entries
            SET updated_on = :updated_on
            WHERE id = :entry_id';
    $params = array(
        ':updated_on' => $datetime->format(DATE_ISO8601),
        ':entry_id' => $this->get_id()
    );
    $stmt = $this->appbox->get_connection()->prepare($sql);
    $stmt->execute($params);
    $stmt->closeCursor();
    $this->updated_on = $datetime;
    $this->delete_data_from_cache();

    return $this;
  }

  /**
   *
   * @return Feed_Publisher_Adapter
   */
  public function get_publisher()
  {
    if (!$this->publisher instanceof Feed_Publisher_Adapter)
      $this->publisher = new Feed_Publisher_Adapter($this->appbox, $this->publisher_id);

    return $this->publisher;
  }
  
  /**
   *
   * @param User_adapter $user
   * @return boolean 
   */
  public function is_publisher(User_adapter $user)
  {
    return $user->get_id() === $this->get_publisher()->get_user()->get_id();
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
   * @return string
   */
  public function get_author_name()
  {
    return $this->author_name;
  }

  /**
   *
   * @return string
   */
  public function get_author_email()
  {
    return $this->author_email;
  }

  /**
   *
   * @return array
   */
  public function get_content()
  {
    if ($this->items)

      return $this->items;

    $rs = $this->retrieve_elements();
    $items = array();
    foreach ($rs as $item_id)
    {
      try
      {
        $items[] = new Feed_Entry_Item($this->appbox, $this, $item_id);
      }
      catch (Exception_NotFound $e)
      {

      }
    }

    $this->items = $items;

    return $this->items;
  }

  protected function retrieve_elements()
  {
    try
    {
      return $this->get_data_from_cache(self::CACHE_ELEMENTS);
    }
    catch (Exception $e)
    {

    }

    $sql = 'SELECT id FROM feed_entry_elements
            WHERE entry_id = :entry_id ORDER BY ord ASC';
    $stmt = $this->appbox->get_connection()->prepare($sql);
    $stmt->execute(array(':entry_id' => $this->get_id()));
    $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    $items = array();

    foreach ($rs as $row)
    {
      $items[] = (int) $row['id'];
    }

    $this->set_data_to_cache($items, self::CACHE_ELEMENTS);

    return $items;
  }

  /**
   *
   * @return void
   */
  public function delete()
  {
    foreach ($this->get_content() as $content)
    {
      $content->delete();
    }

    $sql = 'DELETE FROM feed_entries WHERE id = :entry_id';
    $stmt = $this->appbox->get_connection()->prepare($sql);
    $stmt->execute(array(':entry_id' => $this->get_id()));
    $stmt->closeCursor();

    return;
  }

  /**
   *
   * @param appbox $appbox
   * @param Feed_Adapter $feed
   * @param Feed_Publisher_Adapter $publisher
   * @param string $title
   * @param string $subtitle
   * @param string $author_name
   * @param string $author_mail
   * @return Feed_Entry_Adapter
   */
  public static function create(appbox &$appbox, Feed_Adapter $feed
  , Feed_Publisher_Adapter $publisher, $title, $subtitle, $author_name, $author_mail)
  {
    if (!$feed->is_publisher($publisher->get_user()))
    {
      throw new Exception_Feed_PublisherNotFound("Publisher not found");
    }

    if (!$feed->is_public() && $feed->get_collection() instanceof Collection)
    {
      if (!$publisher->get_user()->ACL()->has_access_to_base($feed->get_collection()->get_base_id()))
      {
        throw new Exception_Unauthorized("User has no rights to publish in current feed");
      }
    }

    $sql = 'INSERT INTO feed_entries (id, feed_id, publisher, title
              , description, created_on, updated_on, author_name, author_email)
            VALUES (null, :feed_id, :publisher_id, :title
              , :description, NOW(), NOW(), :author_name, :author_email)';

    $params = array(
        ':feed_id' => $feed->get_id()
        , ':publisher_id' => $publisher->get_id()
        , ':title' => trim($title)
        , ':description' => trim($subtitle)
        , ':author_name' => trim($author_name)
        , ':author_email' => trim($author_mail)
    );

    $stmt = $appbox->get_connection()->prepare($sql);
    $stmt->execute($params);
    $stmt->closeCursor();

    $entry_id = $appbox->get_connection()->lastInsertId();

    $feed->delete_data_from_cache();

    return new self($appbox, $feed, $entry_id);
  }

  /**
   *
   * @param appbox $appbox
   * @param type $id
   * @return Feed_Entry_Adapter
   */
  public static function load_from_id(appbox $appbox, $id)
  {
    $sql = 'SELECT feed_id FROM feed_entries WHERE id = :entry_id';
    $stmt = $appbox->get_connection()->prepare($sql);
    $stmt->execute(array(':entry_id' => $id));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if (!$row)
      throw new Exception_Feed_EntryNotFound();

    $feed = new Feed_Adapter($appbox, $row['feed_id']);

    return new self($appbox, $feed, $id);
  }

  public function get_cache_key($option = null)
  {
    return 'feedentry_' . $this->get_id() . '_' . ($option ? '_' . $option : '');
  }

  public function get_data_from_cache($option = null)
  {
    return $this->appbox->get_data_from_cache($this->get_cache_key($option));
  }

  public function set_data_to_cache($value, $option = null, $duration = 0)
  {
    return $this->appbox->set_data_to_cache($value, $this->get_cache_key($option), $duration);
  }

  public function delete_data_from_cache($option = null)
  {
    return $this->appbox->delete_data_from_cache($this->get_cache_key($option));
  }

}