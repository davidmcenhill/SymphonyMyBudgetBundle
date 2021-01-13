<?php
namespace Btg\MyBudgetBundle\Tests\Controller\ImportControllerTest;

require_once("../BaseTestCase.php");

use BaseTestCase;
use Btg\MyBudgetBundle\Controller\ImportController;

class ImportControllerTest extends BaseTestCase
{

    protected function setUp()
    {
    }
    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {        
    }
    
    public function testFileImport()
    {
        $imp = new ImportController();
        $filename = 'C:\\tmp\\txns.txt';
        $em = \NULL;
        $result = $imp->importFileToDb($em, $filename);
        $this->assertEquals(2, $result['count']);
    }
}
?>
