<?php

require_once dirname(__FILE__) . '/../../PhraseanetPHPUnitAuthenticatedAbstract.class.inc';
require_once dirname(__FILE__) . '/../Bridge_datas.inc';

/**
 * Test class for Bridge_Api_ElementCollection.
 * Generated by PHPUnit on 2011-10-12 at 17:59:33.
 */
class Bridge_Api_ElementCollectionTest extends PHPUnit_Framework_TestCase
{

  public function testAdd_element()
  {
    $collection = new Bridge_Api_ElementCollection();
    $i = 0;
    while($i < 5)
    {
      $element = $this->getMock("Bridge_Api_ElementInterface");
      $collection->add_element(new $element);
      $i++;
    }
    $this->assertEquals(5, sizeof($collection->get_elements()));
  }

}

?>
