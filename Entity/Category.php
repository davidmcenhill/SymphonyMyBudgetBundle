<?php

/*
 * src/Btg/MyBudgetBundle/Entity/Category.php
 * Table to hold the transaction categories.
 */

namespace Btg\MyBudgetBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="category")
 */
class Category
{

	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $category_id;

	/**
	 * @ORM\Column(type="string",length=32,nullable=false)
	 */
	protected $name;

	/**
	 * @ORM\Column(type="string",length=256,nullable=true)
	 */
	protected $description;
   
	/**
	 * @ORM\Column(type="integer",nullable=false)
	 */
	protected $deductible;  // (probably) tax deductible;

	/**
	 * @ORM\OneToMany(targetEntity="Merchant", mappedBy="category_id")
	 */
	protected $merchant;

	function __construct()
	{
		$this->merchant = new ArrayCollection();
      $this->deductible = false;
	}

	public function getDescription()
	{
		return $this->description;
	}

	public function setDescription($description)
	{
		$this->description = $description;
	}

	public function getMerchant()
	{
		return $this->merchant;
	}

	public function setMerchant($merchant)
	{
		$this->merchant = $merchant;
	}

	public function getName()
	{
		return $this->name;
	}

	public function setName($name)
	{
		$this->name = $name;
	}

	public function getCategoryId()
	{
		return $this->category_id;
	}
   
   public function getDeductible()
   {
      return ($this->deductible === 1);
   }

   public function setDeductible($deductible)
   {
      $this->deductible = ($deductible ? 1 : 0);
   }

   /**
	 * Used when class cast to a string, for example in array_diff()
	 * @return type String
	 */
	public function __toString()
	{
		return $this->name;
	}

}

?>
