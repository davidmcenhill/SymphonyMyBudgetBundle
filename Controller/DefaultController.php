<?php

namespace Btg\MyBudgetBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{

	public function indexAction($name)
	{
		return $this->render('BtgMyBudgetBundle:Default:index.html.twig', array('name' => $name));
	}

}
