<?php

/*
 * src/Btg/MyBudgetBundle/Entity/Categories.php
 * Table to hold the transaction categories.
 */

namespace Btg\MyBudgetBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

class Categories
{

	protected $categories;

	function __construct()
	{
		//$this->categories = new ArrayCollection();
	}

	public function getCategories()
	{
		return $this->categories;
	}

	public function setCategories($categories)
	{
		$this->categories = $categories;
	}

}

?>
