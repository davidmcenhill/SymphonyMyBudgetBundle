<?php

namespace Btg\MyBudgetBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Btg\MyBudgetBundle\Entity\Options;

class OptionsController extends Controller
{

	public function editAction(Request $request)
	{
		$where = array();
		$orderby = array('name' => 'ASC'); // order by clause for the query
		$options = $this->getDoctrine()
			->getRepository('BtgMyBudgetBundle:Options')
			->findBy($where, $orderby);

		$debug_msg = '';

		// Build a form where each row of Options table becomes a field:
		$values = array();
		foreach ($options as $option)
		{
			switch ($option->getType())
			{
				case 'checkbox':
					$value = ($option->getValue() === '1') ? true : false;
					break;
				default:
					$value = $option->getValue();
					break;
			}
			$values[$option->getName()] = $value;
		}

		$formbuilder = $this->createFormBuilder($values);

		foreach ($options as $option)
		{
			$formbuilder->add($option->getName(), $option->getType());
		}
		$form = $formbuilder->getForm();
		$form->handleRequest($request);

		if ($form->isValid())
		{
			if ($request->request->has('Save'))
			{
				$result = $form->getData();
				$em = $this->getDoctrine()->getEntityManager();
				foreach ($options as $option)
				{
					$form_key = $option->getName();
					$value = $result[$form_key];

					if ($option->getType() == 'checkbox')
					{
						// As you know unchecked checkboxes do not return anything.
						$value = ($value == null) ? '0' : $value;
					}

					$option->setValue($value);
					echo "$form_key,$value";
					$em->persist($option);
				}
				$em->flush();
				$this->get('session')->getFlashBag()->add('notice', "Changes have been saved");
			}
			else if ($request->request->has('Defaults'))
			{
				// Add the missing ones
				$missing_options = array_diff($this->getDefaults(), $options);
				$em = $this->getDoctrine()->getEntityManager();
				foreach ($missing_options as $option)
				{
					$em->persist($option);
				}

				// Assign default values to existing ones
				$default_values = array();
				foreach ($this->getDefaults() as $default)
				{
					$default_values[$default->getName()] = $default->getInitValue();
				}

				foreach ($options as $option)
				{
					if (\array_key_exists($option->getName(), $default_values))
					{
						$value = $default_values[$option->getName()];
						$option->setValue($value);
						$em->persist($option);
					}
					else
					{
						$em->remove($option);
					}
				}

				$em->flush();
				return $this->redirect($this->generateUrl('BtgMyBudgetBundle_options'));
			}
		}

		return $this->render('BtgMyBudgetBundle:Options:options.html.twig', array(
				'form' => $form->createView(),
				'debug_msg' => $debug_msg
				)
		);
	}

	private function getDefaults()
	{
		return array(
			new Options('ImportAuthorisations', 'Import Authorisations', 'checkbox', '1'),
			new Options('AuthorisationsRetainedDays', 'Authorisations Retained Days', 'text', '30'),
			new Options('AuthorisationPattern', 'Authorisation Match Pattern', 'text', '[Authorisation Only - ]'),
			new Options('OverrideAssignments', 'Override Assignments', 'checkbox', '0'),
			new Options('DateFormat', 'Date Format', 'text', 'YYYYMMDD')
		);
	}

}
