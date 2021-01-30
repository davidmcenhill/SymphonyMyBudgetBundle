<?php

namespace Btg\MyBudgetBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DefaultController extends AbstractController
{

	public function indexAction($name)
	{
		return $this->render('BtgMyBudgetBundle:Default:index.html.twig', array('name' => $name));
	}

}
