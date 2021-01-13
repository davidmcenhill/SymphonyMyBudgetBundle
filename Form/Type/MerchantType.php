<?php

// src/Btg/MyBudgetBundle/Form/Type/MerchantType.php

namespace Btg\MyBudgetBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class MerchantType extends AbstractType
{

	private $category_choices = array();

	function __construct(array $category_choices)
	{
		$this->category_choices = $category_choices;
	}

	/**
	* Build the merchant form.
	 * 
	 * @param \Btg\MyBudgetBundle\Form\Type\FormBuilderInterface $builder
	 * @param array $options
	 */
	public function buildForm(FormBuilderInterface $builder, array $options)
	{

		$builder
         ->add('merchant_id', 'hidden')
			->add('name', 'text')
			->add('pattern', 'text')
			->add('fk_category_id', 'choice', array('choices' => $this->category_choices));
	}

	public function setDefaultOptions(OptionsResolverInterface $options)
	{
		$options->setDefaults(
			array(
				'data_class' => 'Btg\MyBudgetBundle\Entity\Merchant',
		));
	}

	public function getName()
	{
		return 'merchant';
	}

}

?>