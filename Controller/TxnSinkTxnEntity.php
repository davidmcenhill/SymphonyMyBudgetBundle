<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Btg\MyBudgetBundle\Controller;
use Importer;
/**
 * Description of TxnSinkTxnEntity
 *
 * @author Administrator
 */
class TxnSinkTxnEntity extends Importer\TxnSink
{
    private $entityMgr;
    private $import;
    const IMP_STATUS_PENDING = 0;
    /**
     * 
     * @param type $txnClassName Name of the class to write the transaction to.
     * @param type $entityMgr Reference to the database entity manager
     * @param import Import entity.
     */
    function __construct($txnClassName, $entityMgr, $import)
    {
        parent::__construct($txnClassName);
        $this->entityMgr = $entityMgr;
        $this->import = $import;
    }
    
    /**
     * Write out a transction.
     * @param type $result Transaction to write is an array RESULT_KEY => values
     */
    public function setTxn($result)
    {
        $txn = parent::setTxn($result);
        $txn->setImport($this->import);
        $txn->setStatus(self::IMP_STATUS_PENDING);
        $this->entityMgr->persist($txn);
    }

    /**
     * Open the writer. See php fopen() function
     */
    public function fopen()
    {
        
    }

    /**
     * Close the writer.
     */
    public function fclose()
    {
        //TODO: maybe this is where you need to put the $em->persist
    }
}
