<?php

require_once dirname(__FILE__) . '/../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

use Symfony\Component\Console\Tester\CommandTester;
use \Symfony\Component\Console\Application;
/**
 * Test class for module_console_schedulerState.
 * Generated by PHPUnit on 2011-11-03 at 16:11:07.
 */
class module_console_schedulerStateTest extends PHPUnit_Framework_TestCase
{

  public function testExecute()
  {
    // mock the Kernel or create one depending on your needs
    $application = new Application();
    $application->add(new module_console_schedulerState('system:schedulerState'));

    $command = $application->find('system:schedulerState');
    $commandTester = new CommandTester($command);
    $commandTester->execute(array('command' => $command->getName()));

    $task_manager = new task_manager(appbox::get_instance());
    $state = $task_manager->get_scheduler_state();

    $sentence = sprintf('Scheduler is %s', $state['schedstatus']);
    $this->assertTrue(strpos($commandTester->getDisplay(), $sentence) !== false);

  }


}

?>