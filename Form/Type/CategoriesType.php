<?php

// src/Btg/MyBudgetBundle/Form/Type/CategoriesType.php

namespace Btg\MyBudgetBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class CategoriesType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->add('categories', 'collection', array(
			'type' => new CategoryType(),
			'allow_add' => true,
			'allow_delete' => true,
			'by_reference' => false
		));
	}

	public function setDefaultOptions(OptionsResolverInterface $options)
	{
		$options->setDefaults(
			array(
			'data_class' => 'Btg\MyBudgetBundle\Entity\Categories',
		));
	}

	public function getName()
	{
		return 'categories';
	}

}

?>