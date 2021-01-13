<?php

// src/Btg/MyBudgetBundle/Form/Type/ImportType.php

namespace Btg\MyBudgetBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
//use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormBuilderInterface;

class ImportType extends AbstractType
{

	public function buildForm(FormBuilderInterface $builder, array $options)
	//public function buildForm(FormBuilder $builder, array $options)
	{
		$builder
			->add('file')  // will default to type file
			//->add('file_type', 'text')
			->add('comment', 'text');
	}

	public function getDefaultOptions(array $options)
	{
		return array(
			'data_class' => 'Btg\MyBudgetBundle\Entity\Import',
		);
	}

	public function getName()
	{
		return 'import';
	}

}

?>