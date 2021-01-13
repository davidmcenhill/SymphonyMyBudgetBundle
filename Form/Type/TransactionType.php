<?php

// src/Btg/MyBudgetBundle/Form/Type/TransactionType.php

namespace Btg\MyBudgetBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class TransactionType extends AbstractType
{

	private $category_choices = array();
	private $merchant_choices = array();

	// DAM : not sure if this is the best way to do this
	function __construct(array $category_choices, array $merchant_choices)
	{
		$this->category_choices = $category_choices;
		$this->merchant_choices = $merchant_choices;
	}

	public function buildForm(FormBuilderInterface $builder, array $options)
	{

		$builder
			//->add('date', 'datetime', array('widget' => 'single_text'))
			->add('transaction_id', 'hidden')
			->add('description', 'text')
			->add('debit', 'text')
			->add('credit', 'text')
			->add('fk_merchant_id', 'choice', array('choices' => $this->merchant_choices))
			->add('fk_category_id', 'choice', array('choices' => $this->category_choices));
	}

	public function setDefaultOptions(OptionsResolverInterface $options)
	{
		$options->setDefaults(
			array(
				'data_class' => 'Btg\MyBudgetBundle\Entity\Transaction'
		));
	}

	public function getName()
	{
		return 'transaction';
	}

}

?>