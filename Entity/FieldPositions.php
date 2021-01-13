<?php

/*
 * src/Btg/MyBudgetBundle/Entity/FieldPositions.php
 * Class to give the field positions in a transaction line.
 */

namespace Btg\MyBudgetBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="fieldpositions")
 */
class FieldPositions
{

	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $file_type_id;
	// Identifies file format
	/**
	 * @ORM\Column(type="string",length=256,nullable=false)
	 */
	protected $file_type_description;  // may need to have a seperate table for name etc
	// Position on the line starting of date starting at 0.
	/**
	 * @ORM\Column(type="integer",nullable=false)
	 */
	protected $date_index;
	// Position on the line starting of description starting at 0.
	/**
	 * @ORM\Column(type="integer",nullable=false)
	 */
	protected $description_index;
	// Position on the line starting of credit starting at 0.
	/**
	 * @ORM\Column(type="integer",nullable=false)
	 */
	protected $credit_index;
	// Position on the line starting of debit starting at 0.
	/**
	 * @ORM\Column(type="integer",nullable=false)
	 */
	protected $debit_index;

	function __construct()
	{
		// TODO: get from table, setting to Bankwest for the moment.
		$this->file_type_id = 1;
		$this->date_index = 2;
		$this->description_index = 3;
		$this->credit_index = 6;
		$this->debit_index = 5;
	}

	function getFieldPos()
	{
		$positions = array(
			'date' => $this->date_index,
			'description' => $this->description_index,
			'credit' => $this->credit_index,
			'debit' => $this->debit_index
		);
		return $postions;
	}

	public function getDateIndex()
	{
		return $this->date_index;
	}

	public function getDescriptionIndex()
	{
		return $this->description_index;
	}

	public function getCreditIndex()
	{
		return $this->credit_index;
	}

	public function getDebitIndex()
	{
		return $this->debit_index;
	}

}

?>
