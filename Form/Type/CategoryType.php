<?php

// src/Btg/MyBudgetBundle/Form/Type/CategoryType.php

namespace Btg\MyBudgetBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class CategoryType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder
			->add('name', 'text')
			->add('description', 'text')
         ->add('deductible', 'checkbox');
	}

	public function setDefaultOptions(OptionsResolverInterface $options)
	{
		$options->setDefaults(
			array(
			'data_class' => 'Btg\MyBudgetBundle\Entity\Category',
		));
	}

	public function getName()
	{
		return 'category';
	}

}

?>