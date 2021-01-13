<?php

/*
 * src/Btg/MyBudgetBundle/Entity/Transactions.php
 * Table to hold the transaction transactions.
 */

namespace Btg\MyBudgetBundle\Entity;

//use Doctrine\Common\Collections\ArrayCollection;
//use Doctrine\ORM\Mapping as ORM;

class Transactions
{

    protected $transactions;
    protected $filt_fk_category_id;
    protected $filt_find_unset_category_id;
    protected $filt_fk_merchant_id;
    protected $filt_credit;
    protected $filt_debit;
    protected $filt_description;
    protected $page_control_default_data;

    function __construct()
    {
        $this->filt_fk_category_id = null;
        $this->filt_fk_merchant_id = null;
        $this->filt_credit = null;
        $this->filt_debit = null;
        $this->filt_description = null;
    }

    public function getTransactions()
    {
        return $this->transactions;
    }

    public function setTransactions($transactions)
    {
        $this->transactions = $transactions;
    }

    /**
     * Clone the array of transactions to this objects array.
     * @param type $transactions
     */
    public function cloneTransactions($transactions)
    {
        foreach ($transactions as $key => $txn)
        {
            $this->transactions[$key] = clone $txn;
        }
    }

    public function getFilterCategoryId()
    {
        return $this->filt_fk_category_id;
    }

    public function setFilterCategoryId($filt_fk_category_id)
    {
        $this->filt_fk_category_id = $filt_fk_category_id;
    }

    public function getFilterMerchantId()
    {
        return $this->filt_fk_merchant_id;
    }

    public function setFilterMerchantId($filt_fk_merchant_id)
    {
        $this->filt_fk_merchant_id = $filt_fk_merchant_id;
    }

    public function getFilterCredit()
    {
        return $this->filt_credit;
    }

    public function setFilterCredit($filt_credit)
    {
        $this->filt_credit = $filt_credit;
    }

    public function getFilterDebit()
    {
        return $this->filt_debit;
    }

    public function setFilterDebit($filt_debit)
    {
        $this->filt_debit = $filt_debit;
    }

    public function getFilterDescription()
    {
        return $this->filt_description;
    }

    public function setFilterDescription($filt_description)
    {
        $this->filt_description = $filt_description;
    }

    /**
     * Get the filter where clauses
     * @param string $tn	Table name or alias
     * @return string WHERE clauses as a DQL string
     */
    public function getFilterClauses($tn)
    {
        $clauses = '';
        switch ($this->filt_fk_category_id)
        {
            case null:
            case 'all':
                // Get all results hence do not set any clause. 
                break;
            case 'none':
                // Get transactions with unset categories.
                $clauses .= "AND ($tn.fk_category_id IS NULL) ";
                break;
            default:
                $clauses .= "AND ($tn.fk_category_id = {$this->filt_fk_category_id}) ";
                break;
        }

        switch ($this->filt_fk_merchant_id)
        {
            case null:  // No key set hence find all.
            case 'all':
                break;
            case 'none':
                // Not set
                $clauses .= "AND ($tn.fk_merchant_id IS NULL) ";
                break;
            default:
                $clauses .= "AND ($tn.fk_merchant_id = {$this->filt_fk_merchant_id}) ";
                break;
        }

        if ($this->filt_credit != null)
        {
            $clauses .= "AND ($tn.credit = {$this->filt_credit}) ";
        }

        if ($this->filt_debit != null)
        {
            $clauses .= "AND ($tn.debit = {$this->filt_debit}) ";
        }

        if ($this->filt_description != null)
        {
            $clauses .= "AND ($tn.description LIKE '%{$this->filt_description}%') ";
        }

        // TODO find out if and how to do LIKE for description and amounts


        return $clauses;
    }

    // In TransactionsType the FormBuilder for the page controls is added, but somehow
    // the framework expects the data for that to be in Form, hence:
    public function getForm()
    {
        return $this->page_control_default_data;
    }

    public function setForm($page_control_default_data)
    {
        $this->page_control_default_data = $page_control_default_data;
    }

    /**
     * Used when class cast to a string, for example in array_diff()
     * @return type String
     */
    public function __toString()
    {
        $out = '<table>';
        foreach ($this->transactions as $txn)
        {
            $out .= '<tr>';
            $out .= $txn->__toString();
            $out .= '</tr>';
        }
        $out .= '</table>';
        return 'right:' . $out;
    }

}

?>
