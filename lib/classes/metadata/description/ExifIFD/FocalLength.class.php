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
class metadata_description_ExifIFD_FocalLength extends metadata_Abstract implements metadata_Interface
{

  const SOURCE = '/rdf:RDF/rdf:Description/ExifIFD:FocalLength';
  const NAME_SPACE = 'ExifIFD';
  const TAGNAME = 'FocalLength';
  const MAX_LENGTH = 0;
  const TYPE = self::TYPE_RATIONAL64;
  const MANDATORY = false;
  const MULTI = false;
  const READONLY = false;


}
