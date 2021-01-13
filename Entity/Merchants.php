<?php

/*
 * src/Btg/MyBudgetBundle/Entity/Merchants.php
 * Table to hold the transaction merchants.
 */

namespace Btg\MyBudgetBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

class Merchants
{

	protected $merchants;
   
   protected $filter_merchant_name;
   protected $filter_match_pattern;
   protected $filter_category_id;
   protected $page_control_default_data;

	function __construct()
	{
		//$this->categories = new ArrayCollection();
      $this->filter_category_id = null;
	}

	public function getMerchants()
	{
		return $this->merchants;
	}

	public function setMerchants($merchants)
	{
		$this->merchants = $merchants;
	}
   public function getFilterMerchantName()
   {
      return $this->filter_merchant_name;
   }

   public function getFilterMatchPattern()
   {
      return $this->filter_match_pattern;
   }

   public function getFilterCategoryId()
   {
      return $this->filter_category_id;
   }

   public function setFilterMerchantName($filter_merchant_name)
   {
      $this->filter_merchant_name = $filter_merchant_name;
   }

   public function setFilterMatchPattern($filter_match_pattern)
   {
      $this->filter_match_pattern = $filter_match_pattern;
   }

   public function setFilterCategoryId($filter_category_id)
   {
      $this->filter_category_id = $filter_category_id;
   }

    // In MerchantesType the FormBuilder for the page controls is added, but somehow
    // the framework expects the data for that to be in Form, hence:
    public function getForm()
    {
        return $this->page_control_default_data;
    }

    public function setForm($page_control_default_data)
    {
        $this->page_control_default_data = $page_control_default_data;
    }
    
    //The call from MerchantController and then sort out the layout (or maybe easier to do that first).
    /**
     * Get the filter where clauses
     * @param string $tn	Table name or alias
     * @return string WHERE clauses as a DQL string
     */
    public function getFilterClauses($tn)
    {
        $clauses = '';
        switch ($this->filter_category_id)
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
                $clauses .= "AND ($tn.fk_category_id = {$this->filter_category_id}) ";
                break;
        }

        if ($this->filter_merchant_name != null)
        {
            $clauses .= "AND ($tn.name LIKE '%{$this->filter_merchant_name}%') ";
        }

        if ($this->filter_match_pattern != null)
        {
            $clauses .= "AND ($tn.pattern LIKE '%{$this->filter_match_pattern}%') ";
        }

        return $clauses;
    }


}

?>
