<?php

// src/Btg/MyBudgetBundle/Form/Type/MerchantsType.php

namespace Btg\MyBudgetBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Btg\MyBudgetBundle\Form\Type\TransactionsType; // Required for static funtions.
 

class MerchantsType extends AbstractType
{

	private $category_choices = array();
	private $page_controls;

   function __construct(array $category_choices, FormBuilderInterface $page_controls = null)
	{
		$this->category_choices = $category_choices;
		$this->page_controls = $page_controls;
	}

	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		// Setup a collection of MerchantType forms
		$builder->add('merchants', 'collection', array(
			'type' => new MerchantType($this->category_choices),
			'allow_add' => true,
			'allow_delete' => true,
			'by_reference' => false
		));
      

      // Add a filter for the merchants name
  		$builder->add('filter_merchant_name', 'text');
      // Add a filter for the match pattern 
  		$builder->add('filter_match_pattern', 'text');

      // Add a filter for the categories. First add the ALL CATEGORIES choice.
		$cat_choices = TransactionsType::addArrays(array('all' => 'ALL CATEGORIES'), $this->category_choices);
		$builder->add('filter_category_id', 'choice', array('choices' => $cat_choices));
      
      // Add the page controls if present.
		if (is_null($this->page_controls) == false)
		{
			$builder->add($this->page_controls);
		}

	}

	public function setDefaultOptions(OptionsResolverInterface $options)
	{
		$options->setDefaults(
			array(
				'data_class' => 'Btg\MyBudgetBundle\Entity\Merchants'
		));
	}

	public function getName()
	{
		return 'merchants';
	}

}

?>