<?php

namespace Btg\MyBudgetBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Btg\MyBudgetBundle\Form\Type\CategoriesType;
use Btg\MyBudgetBundle\Entity\Categories;

class CategoryController extends Controller
{

	public function categoriesAction(Request $request)
	{
		// Get existing categories
		$categories = new Categories();

		$categories->setCategories(
			$this->getDoctrine()
				->getRepository('BtgMyBudgetBundle:Category')
				->findAll());

		$existing_categories = $categories->getCategories();

		$debug_msg = '';
		$form = $this->createForm(new CategoriesType(), $categories);
		$form->handleRequest($request);

      if ($form->isValid())
		{
         if ($request->request->has('Save'))
         {
            // Save to database.
            $em = $this->getDoctrine()->getEntityManager();

            // Get transactions in case foreign keys needs to be deleted
            $transactions = $em->getRepository('BtgMyBudgetBundle:Transaction');

            // Find the removed categories
            $deleted_categories = array_diff($existing_categories, $categories->getCategories());
            foreach ($deleted_categories as $category)
            {
               // Unset foreign keys in transaction effected by this action:
               $effected_transactions = $transactions->findBy(array('fk_category_id' => $category->getCategoryId()));
               if (isset($effected_transactions))
               {
                  foreach ($effected_transactions as $transaction)
                  {
                     $transaction->SetFkCategoryId(null);
                     $em->persist($transaction);
                  }
               }

               $em->remove($category);
            }

            foreach ($categories->getCategories() as $category)
            {
               $em->persist($category);
               $debug_msg .= $category->getName() . ' ';
            }
            $em->flush();

            $this->get('session')->getFlashBag()->add('notice', 'Your changes were saved!');
            return $this->redirect($this->generateUrl('BtgMyBudgetBundle_categories'));
         }
         else if ($request->request->has('Cancel'))
         {
            return $this->redirect($this->generateUrl('BtgMyBudgetBundle_categories'));
         }
		}
		return $this->render('BtgMyBudgetBundle:Import:category.html.twig', array(
				'form' => $form->createView(),
				'debug_msg' => $debug_msg
			));
	}

}
