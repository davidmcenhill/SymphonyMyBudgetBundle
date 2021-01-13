<?php
/*
 * src/Btg/MyBudgetBundle/OptionsContainer.php
 * Class to access the options via a service.
 * 
 * This needs to be congigured in app/config/config.yml:
 * services:
  btg_options:
    class: Btg\MyBudgetBundle\OptionsContainer
    arguments:
      - @doctrine.orm.entity_manager
 */

namespace Btg\MyBudgetBundle;

use Doctrine\ORM\EntityManager;

class OptionsContainer
{
   protected $em;
   
   function __construct(EntityManager $em)
    {
        $this->em = $em;
    }
 
	/**
	 * 
	 * @param type $name	Name of the option to find
	 * @return type the value of the option as a string.
	 */
	public function GetOption($name)
	{
      $option = $this->em
            ->getRepository('BtgMyBudgetBundle:Options')
            ->findOneBy(array('name' => $name));
				
		return ($option != null) ? $option->getValue() : null;
	}
	
	/**
	 * 
	 * @param type $name	Name of the option to find
	 * @return type the value of the option as a BOOLEAN.
	 */
	public function GetOptionBoolean($name)
	{
		$value = $this->getOption($name);
		if ($value == null)
		{
			return null;
		}
		return ($value === '1') ? true : false;
	}
	

}
