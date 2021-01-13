<?php

/*
 * src/Btg/MyBudgetBundle/Entity/Import.php
 */

namespace Btg\MyBudgetBundle\Entity;

// Following is needed to run command line app/console doctrine:schema:update --force
use Doctrine\ORM\Mapping as ORM;
// Following is needed for the file object
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="import")
 */
class Import
{
	/*
	 * Try to avoid text type because it can not be a key or index in MySql because
	 * of it's unlimited length.
	 */

	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $import_id;

	/**
	 * @ORM\Column(type="string",length=256,nullable=true)
	 */
	protected $comment;

	/**
	 * @ORM\Column(type="string",length=256,nullable=false)
	 */
	protected $filename;

	/**
	 * @ORM\Column(type="date",nullable=false)
	 */
	protected $date_imported;

	/**
	 * @ORM\Column(type="string",length=32,nullable=false)
	 */
	protected $status;

	/**
	 * @ORM\Column(type="integer",nullable=false)
	 */
	protected $tran_count;

	/**
	 * @Assert\File(maxSize=6000000)
	 */
	public $file;

	function __construct()
	{
		$this->filename = '';
	}

	public function getFilename()
	{
		return $this->filename;
	}

	public function setFilename($filename)
	{
		$this->filename = $filename;
	}

	public function getComment()
	{
		return $this->comment;
	}

	public function setComment($comment)
	{
		$this->comment = $comment;
	}

	// Get full path to the uploaded file on this server.
	public function getAbsolutePath()
	{
		return ($this->filename === null) ? null : ($this->getUploadRootDir() . '/' . $this->filename);
	}

	public function getWebPath()
	{
		return ($this->filename === null) ? null : ($this->getUploadDir() . '/' . $this->filename);
	}

	// Get last part of upload directory
	protected function getUploadDir()
	{
		// get rid of the __DIR__ so it doesn't screw when displaying uploaded doc/image
		// in the view.
		return 'uploads/mybudget';
	}

	// Get full upload directory
	protected function getUploadRootDir()
	{
		// the absolute directory path where uploaded documents should be saved
		return __DIR__ . '/../../../../web/' . $this->getUploadDir();
	}

	public function getStatus()
	{
		return $this->status;
	}

	public function setStatus($status)
	{
		$this->status = $status;
	}

	public function getDateImported()
	{
		return $this->date_imported;
	}

	public function setDateImported($date_imported)
	{
		$this->date_imported = $date_imported;
	}

	public function getTranCount()
	{
		return $this->tran_count;
	}

	public function setTranCount($tran_count)
	{
		$this->tran_count = $tran_count;
	}

	// Upload the file 
	public function upload()
	{
		// the file property can be empty if the field is not required
		if ($this->file === null)
		{
			return;
		}
		// we use the original file name here but you should
		// sanitize it at least to avoid any security issues
		// move takes the target directory and then the target filename to move to
		$this->file->move($this->getUploadRootDir(), $this->file->getClientOriginalName());
		// set the filename property to the filename where you'ved saved the file
		$this->filename = $this->file->getClientOriginalName();
		// clean up the file property as you won't need it anymore
		$this->file = null;

		// Set status to Uploaded
		$this->setStatus("Uploaded");
	}

}

?>
