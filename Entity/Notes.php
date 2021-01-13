<?php

/*
 * src/Btg/MyBudgetBundle/Entity/Notes.php
 * Table to hold the arbitrary notes.
 */

namespace Btg\MyBudgetBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="notes")
 */
class Notes
{

	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $note_id;

	/**
	 * @ORM\Column(type="date",nullable=false)
	 */
	protected $date;

	/**
	 * @ORM\Column(type="string",length=256,nullable=false)
	 */
	protected $author;

	/**
	 * @ORM\Column(type="string",length=4096,nullable=false)
	 */
	protected $note;

	public function getDate()
	{
		return $this->date;
	}

	public function setDate($date)
	{
		$this->date = $date;
	}

	public function getAuthor()
	{
		return $this->author;
	}

	public function setAuthor($author)
	{
		$this->author = $author;
	}

	public function getNote()
	{
		return $this->note;
	}

	public function setNote($note)
	{
		$this->note = $note;
	}

}

?>
