<?php

/*
 * src/Btg/MyBudgetBundle/Entity/Merchant.php
 */

namespace Btg\MyBudgetBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="merchant")
 */
class Merchant
{

	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $merchant_id;
	// The pattern used to match the transaction description to the category.
	/**
	 * @ORM\Column(type="string",length=256,nullable=false)
	 */
	protected $pattern;

	/**
	 * @ORM\Column(type="string",length=256,nullable=true)
	 */
	protected $name;

	/**
	 * @ORM\Column(type="integer",nullable=false)
	 */
	protected $fk_category_id;

	/**
	 * @ORM\ManyToOne(targetEntity="Category", inversedBy="Merchant")
	 * @ORM\JoinColumn(name="fk_category_id", referencedColumnName="category_id")
	 */
	protected $category;

	/**
	 * @ORM\OneToMany(targetEntity="Transaction", mappedBy="merchant_id")
	 */
	protected $transaction;

	function __construct()
	{
		$this->transaction = new ArrayCollection();
	}

	public function getPattern()
	{
		return $this->pattern;
	}

	public function setPattern($pattern)
	{
		$this->pattern = $pattern;
	}

	/**
	 * Used when class cast to a string, for example in array_diff()
	 * @return type String
	 */
	public function __toString()
	{
		return '' . $this->merchant_id;
	}

	public function getFkCategoryId()
	{
		return $this->fk_category_id;
	}

	public function setFkCategoryId($fk_category_id)
	{
		$this->fk_category_id = $fk_category_id;
	}

	public function getName()
	{
		return $this->name;
	}

	public function setName($name)
	{
		$this->name = $name;
	}

	public function getMerchantId()
	{
		return $this->merchant_id;
	}

	public function setMerchantId($merchant_id)
	{
		$this->merchant_id = $merchant_id;
	}

	public function getCategory()
	{
		return $this->category;
	}

	public function setCategory($category)
	{
		$this->category = $category;
	}

}

?>
