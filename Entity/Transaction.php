<?php

/*
 * src/Btg/MyBudgetBundle/Entity/Transaction.php
 */

namespace Btg\MyBudgetBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="transaction")
 */
class Transaction
{

	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $transaction_id;

	/**
	 * @ORM\Column(type="date",nullable=false)
	 */
	protected $date;

	/**
	 * @ORM\Column(type="string",length=256,nullable=false)
	 */
	protected $description;

	/**
	 * @ORM\Column(type="decimal",precision=12,scale=2,length=256,nullable=false)
	 */
	protected $debit;

	/**
	 * @ORM\Column(type="decimal",precision=12,scale=2,length=256,nullable=false)
	 */
	protected $credit;

	/**
	 * @ORM\Column(type="integer",nullable=true)
	 */
	protected $fk_merchant_id;

	/**
	 * @ORM\Column(type="integer",nullable=true)
	 */
	protected $fk_category_id;

	/**
	 * @ORM\Column(type="integer",nullable=false)
	 */
	protected $fk_import_id;

	/**
	 * @ORM\ManyToOne(targetEntity="Merchant", inversedBy="Transaction")
	 * @ORM\JoinColumn(name="fk_merchant_id", referencedColumnName="merchant_id")
	 */
	protected $merchant;

	/**
	 * @ORM\ManyToOne(targetEntity="Category", inversedBy="Transaction")
	 * @ORM\JoinColumn(name="fk_category_id", referencedColumnName="category_id")
	 */
	protected $category; // Magic - Confirmed that this is set correct when loaded!

	/**
	 * @ORM\ManyToOne(targetEntity="Import", inversedBy="Transaction")
	 * @ORM\JoinColumn(name="fk_import_id", referencedColumnName="import_id")
	 */
	protected $import;

	function __construct()
	{
		
	}

	public function getDate()
	{
		return $this->date;
	}

	public function setDate($date)
	{
		$this->date = $date;
	}

	public function getDescription()
	{
		return $this->description;
	}

	public function setDescription($description)
	{
		$this->description = $description;
	}

	public function getDebit()
	{
		return $this->debit;
	}

	public function setDebit($debit)
	{
		$this->debit = (is_numeric($debit) ? $debit : 0 );
	}

	public function getCredit()
	{
		return $this->credit;
	}

	public function setCredit($credit)
	{
		$this->credit = (is_numeric($credit) ? $credit : 0 );
	}

	// Not right, for intermediate testing only:
	public function getFkCategoryId()
	{
		return $this->fk_category_id;
	}

	// Not right, for intermediate testing only:
	public function setFkCategoryId($fk_category_id)
	{
		$this->fk_category_id = $fk_category_id;
	}

	public function getMerchant()
	{
		return $this->merchant;
	}

	public function setMerchant($merchant)
	{
		$this->merchant = $merchant;
	}

	public function getFkMerchantId()
	{
		return $this->fk_merchant_id;
	}

	public function setFkMerchantId($fk_merchant_id)
	{
		$this->fk_merchant_id = $fk_merchant_id;
	}

	public function getImport()
	{
		return $this->import;
	}

	public function setImport($import)
	{
		$this->import = $import;
	}

	public function getTransactionId()
	{
		return $this->transaction_id;
	}

	public function setTransactionId($transaction_id)
	{
		$this->transaction_id = $transaction_id;
	}

	/* Used when class cast to a string, for example in array_diff()
	 * @return type String
	 */

	public function __toString()
	{
		return
			"<td>{$this->getTransactionId()}</td>" .
			"<td>{$this->getDescription()}</td>" .
			"<td>{$this->getDebit()}</td>" .
			"<td>{$this->getCredit()}</td>";
	}

}
?>

