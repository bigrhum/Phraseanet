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
class patch_320g implements patchInterface
{

  /**
   *
   * @var string
   */
  private $release = '3.2.0.0.a8';
  /**
   *
   * @var Array
   */
  private $concern = array(base::APPLICATION_BOX);

  /**
   *
   * @return string
   */
  function get_release()
  {
    return $this->release;
  }

  public function require_all_upgrades()
  {
    return true;
  }

  /**
   *
   * @return Array
   */
  function concern()
  {
    return $this->concern;
  }

  function apply(base &$appbox)
  {
    $sql = 'INSERT INTO bridge_site (id, `type`) VALUES (null, "dailymotion")';

    $stmt = $appbox->get_connection()->prepare($sql);
    $stmt->execute();
    $stmt->closeCursor();

    return true;
  }

}
