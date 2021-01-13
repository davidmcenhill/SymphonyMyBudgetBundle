<?php

namespace Btg\MyBudgetBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Btg\MyBudgetBundle\Entity\Categories;
use Btg\MyBudgetBundle\Form\Type\MerchantsType;
use Btg\MyBudgetBundle\Entity\Merchants;
use \Symfony\Component\Form\Extension\Core\Type\IntegerType;

class MatchesController extends Controller
{

   private $nr_trans_page ;
   private $page_nr ;
   private $max_result_count;
   private $filter_match_pattern;
   private $filter_merchant_name;
   private $filter_category_id;
   private $merchants_type;  // Matches selection object.
   private $em;   // Doctrine entity manager

   public function matchesAction(Request $request)
   {
      // Create the merchant form to bind to:
      $this->em = $this->getDoctrine()->getEntityManager();

      $merchants = new Merchants();
      $this->buildMerchantsType();
      $form = $this->createForm($this->merchants_type, $merchants);
      $form->handleRequest($request);

      // Get control vairables from the session or assign defaults.
      $session = $this->getRequest()->getSession();
      //TODO : these session variables need to have a prefix to limit them to use in this merchant form!
      $this->nr_trans_page = $session->get('nr_trans_page', 24);
      $this->page_nr = $session->get('page_nr', 1);
      $this->max_result_count = $session->get('max_result_count', 0);
      $this->filter_match_pattern = $session->get('filter_match_pattern', '');
      $this->filter_merchant_name = $session->get('filter_merchant_name', '');
      $this->filter_category_id = $session->get('filter_category_id', '');
      
      $defaultData = array(
         'message' => 'Select Result Parameters',
         'nr_trans_page' => $this->nr_trans_page,
         'page_nr' => $this->page_nr
      );
      
      $debug_msg = '';

      if ($form->isValid())
      {

         if (($request->request->has('Refresh')) || ($request->request->has('NextPage')) || ($request->request->has('PreviousPage')) || ($request->request->has('Filter')))
         {
            $result = $form->getData()->getForm();
            if (($request->request->has('PreviousPage')) && ($this->page_nr > 1))
            {
               $this->page_nr--;
            }
            else if (($request->request->has('NextPage')) && ($this->page_nr * $this->nr_trans_page < $this->max_result_count))
            {
               $this->page_nr++;
            }
            else
            {
               if (\is_int($result['page_nr']))
               {
                  $this->page_nr = $result['page_nr'];
               }
            }
            $this->nr_trans_page = $result['nr_trans_page'];
            $this->filter_match_pattern = $merchants->getFilterMatchPattern();
            $this->filter_merchant_name = $merchants->getFilterMerchantName();
            $this->filter_category_id = $merchants->getFilterCategoryId();
            unset($result);

            $max_pg_nr = \ceil(($this->max_result_count / $this->nr_trans_page));
            $max_pg_nr = \max($max_pg_nr, 1);
            $this->page_nr = \max($this->page_nr, 1);
            $this->page_nr = \min($this->page_nr, $max_pg_nr);
            //echo "<br>max_pg_nr:$max_pg_nr</br>";
            //echo "<br>max_result_count:$this->max_result_count</br>";
            //echo "<br>nr_trans_page:$this->nr_trans_page</br>";
            //echo "<br>page_nr:$this->page_nr</br>";
            unset($max_pg_nr);

            // Save to session:
            $session->set('nr_trans_page', $this->nr_trans_page);
            $session->set('page_nr', $this->page_nr);
            $session->set('filter_match_pattern', $this->filter_match_pattern);
            $session->set('filter_merchant_name', $this->filter_merchant_name);
            $session->set('filter_category_id', $this->filter_category_id);

            // Update the changed data in the form
            $defaultData['page_nr'] = $this->page_nr;
            $defaultData['nr_trans_page'] = $this->nr_trans_page;
         }
         else if ($request->request->has('Defaults'))
         {
            $session->remove('nr_trans_page');
            $session->remove('page_nr');
            return $this->redirect($this->generateUrl('BtgMyBudgetBundle_matches'));
         }
         if ($request->request->has('Save'))
         {
            // $merchants should now contain the merchants in the form submission by virtue of the createForm and handleRequest above.
            $this->saveMerchants($merchants);
            return $this->redirect($this->generateUrl('BtgMyBudgetBundle_matches'));
         }
      }
      
      // Update merchants to the latest based on the filter variables.
      $merchants = new Merchants();
      $merchants->setForm($defaultData);
      $merchants->getFilterMerchantName($this->filter_merchant_name);
      $merchants->setFilterMatchPattern($this->filter_match_pattern);
      $merchants->setFilterCategoryId($this->filter_category_id);
      $form = $this->createForm($this->merchants_type, $merchants);
      $form->handleRequest($request);
      
      $additional_clauses = $merchants->getFilterClauses('mc');

      // Query to get the count of results available
      $dql = "SELECT COUNT(mc) FROM BtgMyBudgetBundle:Merchant mc WHERE 1=1 $additional_clauses";

      // Update max_result_count:
      $scalar_result = $this->em->createQuery($dql)->getScalarResult();
      $this->max_result_count = $scalar_result[0][1];
      $session->set('max_result_count', $this->max_result_count);
      $max_pg_nr = \ceil(($this->max_result_count / $this->nr_trans_page));
      if ($this->page_nr > $max_pg_nr)
      {
         $this->page_nr = $max_pg_nr;
      }
      // If there are no results $this->page_nr will be 0 hence this:
      if ($this->page_nr < 1)
      {
         $this->page_nr = 1;
      }

      // Query to get all the results:
      $dql = "SELECT mc FROM BtgMyBudgetBundle:Merchant mc " .
         "WHERE 1=1 $additional_clauses " .
         "ORDER BY mc.pattern ASC";
      $dql_query = $this->em->createQuery($dql);
      $dql_query->setFirstResult((($this->page_nr - 1) * $this->nr_trans_page));
      $dql_query->setMaxResults($this->nr_trans_page);
      $result_set = $dql_query->getResult(); // Returns an array of Merchant objects
      
      // Update the form data to the extracted transactions.
      $merchants->setMerchants($result_set);
      $merchants->setForm($defaultData);
      $form = $this->createForm($this->merchants_type, $merchants);
      unset($this->em);

      // Refresh this in case it changed
      //$debug_msg = $dql_query->getSql();
      
      return $this->render('BtgMyBudgetBundle:Import:merchant.html.twig', array(
            'form' => $form->createView(),
            'page' => $this->page_nr,
            'pages' => $max_pg_nr,
            'debug_msg' => $debug_msg
      ));
   }
   
   private function dumpMerchantArray($description,$merchants)
   {
      $msg = $description . ': count ' . \count($merchants) . ' ';
      foreach ($merchants as $key => $merchant)
      {
         $msg .= $key . ':' . $merchant->getMerchantId() . ' ' . $merchant->getPattern() . ' ' . $merchant->getName() . ' ' . $merchant->getFkCategoryId() . ' | ';
      }
      return $msg;
   }
   
   /**
    * Make an array of entity objects into same array but each managed by Doctrine.
    * Calling this is sufficient to update existing and create new entities in the database,
    * provided flush is called later.
    * @param Array of entity objects
    * @return Array of managed entity objects
    */
   private function makeMerchantObjectsManaged($arrObj)
   {
      foreach ($arrObj as $key => $obj)
      {
         // First fix up the category:
         $cat_id = $obj->getFkCategoryId();
         $category = $this->getDoctrine()
            ->getRepository('BtgMyBudgetBundle:Category')
            ->find($cat_id);
         $obj->setCategory($category);
         $arrObj[$key] = $this->em->merge($obj);
      }
      return $arrObj;
   }
   private function saveMerchants($merchants)
   {
      $msg1 = '';

      // Get an array of the merchants present on the rendered form
      $pre_merchants = $this->getFilteredMerchants();

      // Get an array of the merchants on the submitted form and have it managed by doctrine.
      $post_merchants = $this->makeMerchantObjectsManaged($merchants->getMerchants());

      // First work out which merchants have been added:
      // Note: array_diff will use the __toString() method in entity Merchant to compare arrays.
      $added_merchants = \array_diff($post_merchants, $pre_merchants);
      //$msg1 = $this->dumpMerchantArray('$pre_merchants',$pre_merchants);
      //$msg1 .= $this->dumpMerchantArray('$post_merchants',$post_merchants);
      
      /*foreach ($added_merchants as $merchant)
      {
         // Must set the foreign key variable for the foreign key to be persisted.
         // Otherwise it is set to null.
         $cat_id = $merchant->getFkCategoryId();
         $category = $this->getDoctrine()
            ->getRepository('BtgMyBudgetBundle:Category')
            ->find($cat_id);
         $merchant->setCategory($category);
         $this->em->persist($merchant);
      }*/

      // Find the removed merchants
      $deleted_merchants = \array_diff($pre_merchants, $post_merchants);

      //$msg1 .= $this->dumpMerchantArray('$added_merchants',$added_merchants);
      //$msg1 .= $this->dumpMerchantArray('$deleted_merchants',$deleted_merchants);

      // Update merchants that have changed, 
      /*foreach ($post_merchants as $merchant)
      {      
         $pattern = $merchant->getPattern();
         if (empty($pattern) == true)
         {
            // Dont want to save empty patterns, hence add to deleted.
            $deleted_merchants[] = $merchant;
         }
         else
         {
            if ($merchant->getFkCategoryId() == 0)
            {
               $merchant->setFkCategoryId(null);
            }
            $cat_id = $merchant->getFkCategoryId();
            $category = $this->getDoctrine()
               ->getRepository('BtgMyBudgetBundle:Category')
               ->find($cat_id);
            $merchant->setCategory($category);
            $msg1 .= $this->dumpMerchantArray(" Save:",array($merchant));
            $this->em->merge($merchant);
         }
      }*/

      $transactions = $this->em->getRepository('BtgMyBudgetBundle:Transaction');
      foreach ($deleted_merchants as $merchant)
      {
         // Unset foreign keys in transaction effected by this action:
         $effected_transactions = $transactions->findBy(array('fk_merchant_id' => $merchant->getMerchantId()));
         if (isset($effected_transactions))
         {
            foreach ($effected_transactions as $transaction)
            {
               $transaction->SetFkMerchantId(null);
               $this->em->persist($transaction);
            }
         }
         $this->em->remove($merchant);
      }

      // Apply all the changes in the managed objects:
      $this->em->flush();
      $msg = $msg1 . " Your changes were saved. Added " . \count($added_merchants) .
         ". Deleted " . \count($deleted_merchants) . ".";
      $this->get('session')->getFlashBag()->add('notice', "$msg");
   }

   /**
    * Function to build the matches type including selection information used
    * in the matches form.
    * Sets merchants_type used above.
    */
   private function buildMerchantsType()
   {
      // Retrieve all categories for use in the category choice (dropdown)
      $categories = new Categories();
      $where = array();  // where clause for query (all)
      $orderby = array('name' => 'ASC'); // order by clause for the query
      $categories->setCategories(
         $this->getDoctrine()
            ->getRepository('BtgMyBudgetBundle:Category')
            ->findBy($where, $orderby)
      );

      $category_choices = Array();
      foreach ($categories->getCategories() as $category)
      {
         $category_choices[$category->getCategoryId()] = $category->getName();
      }

      /*
      $merchant_choices = Array();
      foreach ($merchants->getMerchants() as $merchant)
      {
         $name = $merchant->getName();
         if (isset($name) == false)
         {
            $name = $merchant->getPattern();
         }
         $merchant_choices[$merchant->getMerchantId()] = $name;
      }
      */

      $choices_nr_trans_page = array(12 => '12 per page',
         24 => '24 per page',
         48 => '48 per page',
         96 => '96 per page');

      // Create the pagination and content controls
      // Notice how you can pass a Type class instead of a string to specify the type / constraints.
      $form_page_controls = $this->createFormBuilder(/* $defaultData */)
         ->add('nr_trans_page', 'choice', array('choices' => $choices_nr_trans_page))
         ->add('page_nr', new IntegerType(), array());

      $this->merchants_type = new MerchantsType($category_choices, $form_page_controls);
   }

   /**
    * Get an array of the merchant objects that will be present on the form
    * based on the filter parameters and page control data in this object
    *
    * @return array of Merchant objects
    */
   function getFilteredMerchants()
   {
      $merchants = new Merchants();
      $merchants->setFilterMatchPattern($this->filter_match_pattern);
      $merchants->setFilterMerchantName($this->filter_merchant_name);
      $merchants->setFilterCategoryId($this->filter_category_id);
      $additional_clauses = $merchants->getFilterClauses('mc');

      // Query to get all the results:
      $dql = "SELECT mc FROM BtgMyBudgetBundle:Merchant mc " .
         "WHERE 1=1 $additional_clauses " .
         "ORDER BY mc.pattern ASC";
      $dql_query = $this->em->createQuery($dql);
      $dql_query->setFirstResult((($this->page_nr - 1) * $this->nr_trans_page));
      $dql_query->setMaxResults($this->nr_trans_page);
      return $dql_query->getResult(); // Returns an array of Merchant objects
    }
}
