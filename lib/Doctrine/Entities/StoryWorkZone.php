<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Entities;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */

require_once __DIR__ . '/../../classes/cache/cacheableInterface.class.php';
require_once __DIR__ . '/../../classes/User/Interface.class.php';
require_once __DIR__ . '/../../classes/User/Adapter.class.php';

class StoryWorkZone
{
  
    /**
     * @var integer $id
     */
    private $id;

    /**
     * @var integer $sbas_id
     */
    private $sbas_id;

    /**
     * @var integer $record_id
     */
    private $record_id;

    /**
     * @var integer $usr_id
     */
    private $usr_id;

    /**
     * @var datetime $created
     */
    private $created;


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set sbas_id
     *
     * @param integer $sbasId
     */
    public function setSbasId($sbasId)
    {
        $this->sbas_id = $sbasId;
    }

    /**
     * Get sbas_id
     *
     * @return integer 
     */
    public function getSbasId()
    {
        return $this->sbas_id;
    }

    /**
     * Set record_id
     *
     * @param integer $recordId
     */
    public function setRecordId($recordId)
    {
        $this->record_id = $recordId;
    }

    /**
     * Get record_id
     *
     * @return integer 
     */
    public function getRecordId()
    {
        return $this->record_id;
    }

    /**
     * Set usr_id
     *
     * @param integer $usrId
     */
    public function setUsrId($usrId)
    {
        $this->usr_id = $usrId;
    }

    /**
     * Get usr_id
     *
     * @return integer 
     */
    public function getUsrId()
    {
        return $this->usr_id;
    }

    /**
     * Set created
     *
     * @param datetime $created
     */
    public function setCreated($created)
    {
        $this->created = $created;
    }

    /**
     * Get created
     *
     * @return datetime 
     */
    public function getCreated()
    {
        return $this->created;
    }
    
    public function getRecord()
    {
      return new \record_adapter($this->getSbasId(), $this->getRecordId());
    }
    
    public function setRecord(\record_adapter $record)
    {
      if(!$record->is_grouping())
      {
        throw new \Exception('Only storie allowed');
      }
      
      $this->setRecordId($record->get_record_id());
      $this->setSbasId($record->get_sbas_id());
      
      return;
    }
    
    public function setUser(\User_Adapter $user)
    {
      $this->setUsrId($user->get_id());
      return;
    }
    
    public function getUser()
    {
      return \User_Adapter::getInstance($this->getUsrId(), appbox::get_instance());
    }
}