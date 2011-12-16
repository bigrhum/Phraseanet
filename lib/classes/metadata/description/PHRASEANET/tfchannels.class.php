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
class metadata_description_PHRASEANET_tfchannels extends metadata_Abstract implements metadata_Interface
{

  const SOURCE = '/rdf:RDF/rdf:Description/PHRASEANET:tf-channels';
  const NAME_SPACE = 'PHRASEANET';
  const TAGNAME = 'tf-channels';
  const MAX_LENGTH = 0;
  const TYPE = self::TYPE_DIGITS;
  const READONLY = true;

}