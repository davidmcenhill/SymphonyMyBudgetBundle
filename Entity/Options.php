<?php

/*
 * src/Btg/MyBudgetBundle/Entity/Category.php
 * Table to hold the transaction categories.
 */

namespace Btg\MyBudgetBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="options")
 */
class Options
{

	public function __construct($name, $display_name, $type, $value)
	{
		$this->name = $name;
		$this->display_name = $display_name;
		$this->init_value = $value;
		$this->type = $type;
		$this->value = $value;
	}

	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $option_id;

	/**
	 * @ORM\Column(type="string",length=32,nullable=false)
	 */
	protected $name;

	/**
	 * @ORM\Column(type="string",length=64,nullable=false)
	 */
	protected $display_name;

	/**
	 * @ORM\Column(type="string",length=32,nullable=false)
	 */
	protected $type;

	/**
	 * @ORM\Column(type="string",length=256,nullable=true)
	 */
	protected $init_value;

	/**
	 * @ORM\Column(type="string",length=256,nullable=false)
	 */
	protected $value;

	public function getOptionId()
	{
		return $this->option_id;
	}

	public function getName()
	{
		return $this->name;
	}

	public function setName($name)
	{
		$this->name = $name;
	}

	public function getType()
	{
		return $this->type;
	}

	public function setType($type)
	{
		$this->type = $type;
	}

	public function getInitValue()
	{
		return $this->init_value;
	}

	public function setInitValue($init_value)
	{
		$this->init_value = $init_value;
	}

	public function getValue()
	{
		return $this->value;
	}

	public function setValue($value)
	{
		$this->value = $value;
	}

	public function getDisplayName()
	{
		return $this->display_name;
	}

	public function setDisplayName($display_name)
	{
		$this->display_name = $display_name;
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
