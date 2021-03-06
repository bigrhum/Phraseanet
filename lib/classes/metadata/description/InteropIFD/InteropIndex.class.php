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
class metadata_description_InteropIFD_InteropIndex extends metadata_Abstract implements metadata_Interface
{
  const SOURCE = '/rdf:RDF/rdf:Description/InteropIFD:InteropIndex';
  const NAME_SPACE = 'InteropIFD';
  const TAGNAME = 'InteropIndex';
  const MAX_LENGTH = 0;
  const TYPE = self::TYPE_STRING;
  const MANDATORY = false;
  const MULTI = false;
  const READONLY = false;

  public static function available_values()
  {
    return array(
        '\'R03\'' => 'R03 - DCF option file (Adobe RGB)'
        , '\'R98\'' => 'R98 - DCF basic file (sRGB)'
        , '\'THM\'' => 'THM - DCF thumbnail file'
    );
  }

}
