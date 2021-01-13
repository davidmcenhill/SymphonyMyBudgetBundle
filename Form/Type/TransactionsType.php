<?php

// src/Btg/MyBudgetBundle/Form/Type/TransactionsType.php
// Class to hold the transaction selection information and to build the form controls for that.
//

namespace Btg\MyBudgetBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class TransactionsType extends AbstractType
{

	private $category_choices = array();
	private $merchant_choices = array();
	private $page_controls;

	// DAM : not sure if this is the best way to do this.
	// One would think $options is the answer but messed around with that way too long.
	function __construct(array $category_choices, array $merchant_choices, FormBuilderInterface $page_controls = null)
	{
		$this->category_choices = $category_choices;
		$this->merchant_choices = $merchant_choices;
		$this->page_controls = $page_controls;
	}

	/**
	 * Add two arrays together arr1 to arr2 with the keys intact.
	 * (PHPs array functions reorder the keys which we dont want here)
	 * @param array $in : array to prepend to.
	 * @param array $prepend : array to prepend
	 * @return $arr1 combined with $arr2, keys in the same order.
	 */
	public static function addArrays(array $arr1, array $arr2)
	{
		$result = $arr1;

		foreach ($arr2 as $key => $value)
		{
			$result[$key] = $value;
		}
		return $result;
	}

	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		// Setup a collection of TransactionType forms
		$builder->add('transactions', 'collection', array(
			'type' => new TransactionType($this->category_choices, $this->merchant_choices),
			'allow_add' => true,
			'allow_delete' => true,
			'by_reference' => false
		));

                // Only the filter must have the ALL CATEGORIES choice, hence add it here:
		$cat_choices = $this->addArrays(array('all' => 'ALL CATEGORIES'), $this->category_choices);
		$builder->add('filter_category_id', 'choice', array('choices' => $cat_choices));

                // Only the filter must have the ALL MERCHANTS choice, hence add it here:
		$merch_choices = $this->addArrays(array('all' => 'ALL MERCHANTS'), $this->merchant_choices);
		$builder->add('filter_merchant_id', 'choice', array('choices' => $merch_choices));

		$builder->add('filter_description', 'text');
		$builder->add('filter_debit', 'text');
		$builder->add('filter_credit', 'text');

		if (is_null($this->page_controls) == false)
		{
			$builder->add($this->page_controls);
		}
	}

	public function setDefaultOptions(OptionsResolverInterface $options)
	{
		$options->setDefaults(
			array(
				'data_class' => 'Btg\MyBudgetBundle\Entity\Transactions'
		));
	}

	public function getName()
	{
		return 'transactions';
	}

}

?>