<?php

/*
 * src/Btg/MyBudgetBundle/Entity/ImportedTxn.php
 */

namespace Btg\MyBudgetBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="importedtxn")
 */
class ImportedTxn
{

	const IMP_STATUS_PENDING = 0;
	const IMP_STATUS_ACCEPTED = 1;
	const IMP_STATUS_DUPLICATE = 2;
	const IMP_STATUS_FORMAT_ERROR = 3;

	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $imported_txn_id;

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
	 * Can be 0:Pending, 1:Accepted, 2:Duplicate
	 */
	protected $status;

	/**
	 * @ORM\Column(type="integer",nullable=false)
	 */
	protected $fk_import_id;

	/**
	 * @ORM\ManyToOne(targetEntity="Import", inversedBy="ImnportedTxn")
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
		$this->debit = (is_numeric($debit) ? abs($debit) : 0 );
		;
	}

	public function getCredit()
	{
		return $this->credit;
	}

	public function setCredit($credit)
	{
		$this->credit = (is_numeric($credit) ? abs($credit) : 0 );
	}

	public function getImport()
	{
		return $this->import;
	}

	public function setImport($import)
	{
		$this->import = $import;
	}

	public function geStatus()
	{
		return $this->status;
	}

	public function setStatus($status)
	{
		$this->status = $status;
	}

}

?>
