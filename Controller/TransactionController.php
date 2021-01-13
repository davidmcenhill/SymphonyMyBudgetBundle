<?php

/*
 * Controller for the Transactions Page.
 */

namespace Btg\MyBudgetBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Btg\MyBudgetBundle\Entity\Categories;
use Btg\MyBudgetBundle\Entity\Merchant;
use Btg\MyBudgetBundle\Entity\Merchants;
use Btg\MyBudgetBundle\Entity\Transactions;
use Btg\MyBudgetBundle\Form\Type\TransactionsType;

class TransactionController extends Controller
{

    private $transactions_type;  // Transaction selection object.

    /**
     * Entry point function to this controller.
     * Called when the Transactions form needs to be shown and when the user presses any button or selects a control.
     * This function handles the users request and selects the transactions to be displayed.
     * 
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return object containing the transaction information to be displayed.
     */

    public function transactionsAction(Request $request)
    {
        $debug_msg = '';

        // Build the transactions selection object:
        $this->buildTransactionsType();

        // Create the transactions form to bind to:
        $transactions = new Transactions();
        $form = $this->createForm($this->transactions_type, $transactions);
        $form->handleRequest($request);

        // Get control vairables from the session or assign defaults.
        $session = $this->getRequest()->getSession();

        $default_end_date = new \DateTime();
        $default_start_date = new \DateTime(); // Now
        $default_start_date->sub(new \DateInterval('P3M')); // Three Months ago
        //echo "<br>default_start_date:" . $default_start_date->format("d/M/Y") . "</br>";
        //echo "<br>default_end_date:{$default_end_date->format('d/M/Y')}</br>";

        $start_date = $session->get('startdate', $default_start_date);
        $end_date = $session->get('enddate', $default_end_date);
        $nr_trans_page = $session->get('nr_trans_page', 48);
        $page_nr = $session->get('page_nr', 1);
        $max_result_count = $session->get('max_result_count', 0);
        $result_order = $session->get('result_order', 'DESC');

        $defaultData = array(
           'message' => 'Select Result Parameters',
           'startdate' => $start_date,
           'enddate' => $end_date,
           'nr_trans_page' => $nr_trans_page,
           'page_nr' => $page_nr
        );

        // Now create a form containing the transactions specified by the page and filter controls:
        // First handle any actions requiring the saving of data:
        if ($form->isValid())
        {
            if (($request->request->has('Save')) || ($request->request->has('Assign')) || ($request->request->has('SetToUnknown')))
            {
                $status = $this->transactionsSave($request);

                if ($status['ok'] == false)
                {
                    $this->get('session')->getFlashBag()->add('notice', $status['message']);
                    return $this->redirect($this->generateUrl('BtgMyBudgetBundle_transactions'));
                }

                if ($request->request->has('Save'))
                {
                    $this->get('session')->getFlashBag()->add('notice', $status['message']);
                    return $this->redirect($this->generateUrl('BtgMyBudgetBundle_transactions'));
                }

                if ($request->request->has('Assign'))
                {
                    // Assign transactions to categories
                    $status = $this->transactionsCategorise();
                    $msg = "{$status['match_count']} of {$status['txn_count']}";
                    $this->get('session')->getFlashBag()->add('notice', "$msg categorised and changes saved!");
                    return $this->redirect($this->generateUrl('BtgMyBudgetBundle_transactions'));
                }

                if ($request->request->has('SetToUnknown'))
                {
                    // Set unassigned transactions to Unknown
                    $nr_updated = $this->transactionsSetToUnknown($start_date, $end_date);
                    $msg = "Categorised $nr_updated transactions as Unknown";
                    $this->get('session')->getFlashBag()->add('notice', "$msg and changes saved!");
                    return $this->redirect($this->generateUrl('BtgMyBudgetBundle_transactions'));
                }
            }
            else if ($request->request->has('Decategorise'))
            {
                // Decategorise the transactions in the filter.
                $msg = $this->transactionsDecategorise($request);
                $this->get('session')->getFlashBag()->add('notice', $msg);
                return $this->redirect($this->generateUrl('BtgMyBudgetBundle_transactions'));
            }
        }

        if ($form->isValid())
        //if ($request->getMethod() == 'POST')
        {
            if (($request->request->has('Refresh')) || ($request->request->has('NextPage')) || ($request->request->has('PreviousPage')) || ($request->request->has('Filter')))
            {
                //$form->bindRequest($request);
                $result = $form->getData()->getForm();
                if (($request->request->has('PreviousPage')) && ($page_nr > 1))
                {
                    $page_nr--;
                }
                else if (($request->request->has('NextPage')) && ($page_nr * $nr_trans_page < $max_result_count))
                {
                    $page_nr++;
                }
                else
                {
                    if (\is_int($result['page_nr']))
                    {
                        $page_nr = $result['page_nr'];
                    }
                }
                $start_date = $result['startdate'];
                $end_date = $result['enddate'];
                $nr_trans_page = $result['nr_trans_page'];

                unset($result);

                $max_pg_nr = \ceil(($max_result_count / $nr_trans_page));
                $max_pg_nr = \max($max_pg_nr, 1);
                $page_nr = \max($page_nr, 1);
                $page_nr = \min($page_nr, $max_pg_nr);
                //echo "<br>max_pg_nr:$max_pg_nr</br>";
                //echo "<br>max_result_count:$max_result_count</br>";
                //echo "<br>nr_trans_page:$nr_trans_page</br>";
                //echo "<br>page_nr:$page_nr</br>";
                unset($max_pg_nr);

                // Save to session:
                $session->set('startdate', $start_date);
                $session->set('enddate', $end_date);
                $session->set('nr_trans_page', $nr_trans_page);
                $session->set('page_nr', $page_nr);

                // Update the changed data in the form
                $defaultData['page_nr'] = $page_nr;
                $defaultData['startdate'] = $start_date;
                $defaultData['enddate'] = $end_date;
                $defaultData['nr_trans_page'] = $nr_trans_page;
            }
            else if ($request->request->has('OrderByDateAsc'))
            {
                $result_order = 'ASC';
                $page_nr = 1;
                $defaultData['page_nr'] = $page_nr;
                $session->set('result_order', $result_order);
                $session->set('page_nr', $page_nr);
            }
            else if ($request->request->has('OrderByDateDesc'))
            {
                $result_order = 'DESC';
                $page_nr = 1;
                $defaultData['page_nr'] = $page_nr;
                $session->set('result_order', $result_order);
                $session->set('page_nr', $page_nr);
            }
            else if ($request->request->has('Defaults'))
            {
                $session->remove('startdate');
                $session->remove('enddate');
                $session->remove('nr_trans_page');
                $session->remove('page_nr');
                return $this->redirect($this->generateUrl('BtgMyBudgetBundle_transactions'));
            }
        }

        // Update transactions to the latest:
        $transactions = new Transactions();
        $transactions->setForm($defaultData);
        $form = $this->createForm($this->transactions_type, $transactions);
        $form->handleRequest($request);

        // Get existing transactions from the parameters specified
        $s_start_date = $start_date->Format('Y-m-d');
        $s_end_date = $end_date->Format('Y-m-d');

        $additional_clauses = $transactions->getFilterClauses('t');
        echo "<br>$additional_clauses</br>";

        $em = $this->getDoctrine()->getEntityManager();
        // Query to get the count of results available and sums of credit and debit.
        $dql = "SELECT COUNT(t),SUM(t.debit),SUM(t.credit) FROM BtgMyBudgetBundle:Transaction t " .
           "WHERE t.date >= '$s_start_date' AND  t.date <= '$s_end_date' " .
           $additional_clauses;

        // Update max_result_count:
        $scalar_result = $em->createQuery($dql)->getScalarResult();
        $max_result_count = $scalar_result[0][1];
        $totals = array(
           'debit' => $scalar_result[0][2],
           'credit' => $scalar_result[0][3]);
        $session->set('max_result_count', $max_result_count);

        // Query to get all the results:
        $dql = "SELECT t FROM BtgMyBudgetBundle:Transaction t " .
           "WHERE t.date >= '$s_start_date' AND  t.date <= '$s_end_date' " .
           $additional_clauses .
           "ORDER BY t.date $result_order";
        $dql_query = $em->createQuery($dql);
        $dql_query->setFirstResult((($page_nr - 1) * $nr_trans_page));
        $dql_query->setMaxResults($nr_trans_page);
        $result_set = $dql_query->getResult(); // Returns an array of Transaction objects
        $transactions->setTransactions($result_set);

        unset($em);

        // Create the transactions form
        $transactions->setForm($defaultData);
        $form = $this->createForm($this->transactions_type, $transactions);

        // Refresh this in case it changed
        $max_pg_nr = \ceil(($max_result_count / $nr_trans_page));

        return $this->render('BtgMyBudgetBundle:Import:transaction1.html.twig', array(
              'form' => $form->createView(),
              'page' => $page_nr,
              'pages' => $max_pg_nr,
              'totals' => $totals,
              'order_by_date_asc' => ($result_order == 'ASC'),
              'debug_msg' => $debug_msg
        ));
    }

    /**
     * Function to populate the transaction selection information used
     * in the transactions form.
     * Populates transaction_type used above.
     */
    private function buildTransactionsType()
    {
        // Retrieve all categories for use in the category adjustment choice (dropdown)
        $categories = new Categories();
        $where = array();  // where clause for query (all)
        $orderby = array('name' => 'ASC'); // order by clause for the query
        $categories->setCategories(
           $this->getDoctrine()
              ->getRepository('BtgMyBudgetBundle:Category')
              ->findBy($where, $orderby)
        );

        $merchants = new Merchants();
        $where = array();  // where clause for query (all)
        $orderby = array('name' => 'ASC', 'pattern' => 'ASC'); // order by clause for the query
        $merchants->setMerchants(
           $this->getDoctrine()
              ->getRepository('BtgMyBudgetBundle:Merchant')
              ->findBy($where, $orderby)
        );

        $category_choices = Array();
        $category_choices['none'] = ''; // Blank one to indicate unassigned.
        foreach ($categories->getCategories() as $category)
        {
            $category_choices[$category->getCategoryId()] = $category->getName();
        }

        $merchant_choices = Array();
        $merchant_choices['none'] = ''; // Blank one to indicate unassigned.
        foreach ($merchants->getMerchants() as $merchant)
        {
            $name = $merchant->getName();
            if (isset($name) == false)
            {
                $name = $merchant->getPattern();
            }
            $merchant_choices[$merchant->getMerchantId()] = $name;
        }

        $date_selection_fmt = 'dd-MMM-yyyy';
        $choices_nr_trans_page = array(12 => '12 per page',
           24 => '24 per page',
           48 => '48 per page',
           96 => '96 per page');

        $today = \getdate();
        $this_year = $today['year'];
        $years = array($this_year, $this_year - 1, $this_year - 2, $this_year - 3, $this_year - 4);

        // Create the pagination and content controls
        // Notice how you can pass a Type class instead of a string to specify the type / constraints.
        $form_page_controls = $this->createFormBuilder(/* $defaultData */)
           ->add('startdate', 'date', array('format' => $date_selection_fmt, 'years' => $years))
           ->add('enddate', 'date', array('format' => $date_selection_fmt, 'years' => $years))
           ->add('nr_trans_page', 'choice', array('choices' => $choices_nr_trans_page))
           ->add('page_nr', new IntegerType(), array());

        $this->transactions_type = new TransactionsType($category_choices, $merchant_choices, $form_page_controls);
    }

    /**
     * Save the transactions in the request identified by the transaction IDs,
     * not by index as usual because may not match because of filter.
     * @param \Symfony\Component\HttpFoundation\Request $request : form object
     * @return array containing keys 'ok' => boolean, 'message' => string description.
     */
    private function transactionsSave(Request $request)
    {
        // Get the values submitted in the form.
        //(Basically need to rebuild the form as per the transaction IDs in the $request

        $empty = array();
        $form_array = $request->request->get('transactions[transactions]', $empty, true);
        $debug_msg = '';

        $txns_from_db = array();
        $original_fk_cat_id = array();

        foreach ($form_array as $row_nr => $row)
        {
            $txn_id = $row['transaction_id'];

            // Get the Transaction object from the database for this row 
            $txn = $this->getDoctrine()->getRepository('BtgMyBudgetBundle:Transaction')->find($txn_id);

            // Add to the transactions array
            $txns_from_db[$row_nr] = $txn;
            $original_fk_cat_id[$row_nr] = $txn->getFkCategoryId();
        }

        // Build a transactions object
        $transactions = new Transactions();
        $transactions->setTransactions($txns_from_db);

        // Bind the form to the transactions object (ie $txns_from_db) 
        $form = $this->createForm($this->transactions_type, $transactions);
        $form->handleRequest($request);
        //$form->bindRequest($request);

        if ($form->isValid() !== false)
        {
            // Save to database.
            $em = $this->getDoctrine()->getEntityManager();
            $new_merchants = array();

            // Persist transactions
            foreach ($transactions->getTransactions() as $row_nr => $transaction)
            {
                if ($transaction->getFkCategoryId() == 0)
                {
                    $transaction->setFkCategoryId(null);
                }
                else
                {
                    //$debug_msg .= "<br>Possible New match {$transaction->getFkCategoryId()} : {$txns_from_db[$row_nr]->getFkCategoryId()} : {$txns_from_db[$row_nr]->getFkCategoryId()}</br>";
                    // If original transaction did not have the category set
                    if (\is_null($original_fk_cat_id[$row_nr]) == true)
                    {
                        // Create a new match 
                        $new_merchant = $this->createNewMatch($em, $transaction);
                        if (\is_null($new_merchant) == false)
                        {
                            $transaction->setMerchant($new_merchant);
                            //$transaction->setFkMerchantId($new_merchant->getMerchantId());
                            $new_merchants[] = $new_merchant;
                        }
                    }
                }
                if ($transaction->getFkMerchantId() == 0)
                {
                    $transaction->setFkMerchantId(null);
                }
                $em->persist($transaction);
            }
            $em->flush();

            $new_matches_made = \count($new_merchants);
            if ($new_matches_made > 0)
            {
                $merchants = new Merchants();
                $merchants->setMerchants($new_merchants);
                $this->transactionsCategorise($merchants, false);
            }
        }
        else
        {
            $err = '';
            foreach ($form->getErrors() as $e)
            {
                $err .= $e->getMessageTemplate();
            }
            return array('ok' => false, 'message' => "An error occurred saving your changes: $err");
        }

        $status = "Changes were saved. $debug_msg";
        if ($new_matches_made > 0)
        {
            $status .= " $new_matches_made new matches were made.";
        }
        return array('ok' => true, 'message' => $status);
    }

    /**
     * Set unassigned transactions to the unknown category.
     * @param type $start_date : start date of the range to update
     * @param type $end_date : end date of the range to update
     * @return int the number of transactions assigned.
     */
    public function transactionsSetToUnknown($start_date, $end_date)
    {
        // Retrieve all categories for use in the category adjustment choice (dropdown)
        $where = array('name' => 'Unknown');
        $category_unknown = $this->getDoctrine()
           ->getRepository('BtgMyBudgetBundle:Category')
           ->findOneBy($where);

        if (\is_null($category_unknown))
        {
            return 0;
        }

        $cat_id_unknown = $category_unknown->getCategoryId();

        $s_start_date = $start_date->Format('Y-m-d');
        $s_end_date = $end_date->Format('Y-m-d');

        // Unfortunately what I find with Doctrine is that the ORM does not cover everything I need to do.
        // Here we want to run an update and get the number of rows updated. How would one do that in the ORM?
        // So we use DBAL Prepared Statement 
        // (see http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/data-retrieval-and-manipulation.html)
        $em = $this->getDoctrine()->getEntityManager();
        $conn = $em->getConnection();

        $sql = "UPDATE transaction SET fk_category_id = ? WHERE fk_category_id IS NULL " .
           "AND date >= ? AND  date <=  ? ";

        return $conn->executeUpdate($sql, array($cat_id_unknown, $s_start_date, $s_end_date));
    }

    /**
     * Return a $transactions object that were in the $request
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return type
     */
    private function getFormTransactions(Request $request)
    {
        $empty = array();
        $form_array = $request->request->get('transactions[transactions]', $empty, true);

        $txns_from_db = array();

        foreach ($form_array as $row_nr => $row)
        {
            $txn_id = $row['transaction_id'];

            // Get the Transaction object from the database for this row 
            $txn = $this->getDoctrine()->getRepository('BtgMyBudgetBundle:Transaction')->find($txn_id);

            // Add to the transactions array
            $txns_from_db[$row_nr] = $txn;
        }

        // Build a transactions object
        $transactions = new Transactions();
        $transactions->setTransactions($txns_from_db);

        // Bind the form to the transactions object (ie $txns_from_db) 
        $form = $this->createForm($this->transactions_type, $transactions);
        $form->bindRequest($request);

        if ($form->isValid() !== false)
        {
            return $transactions;
        }
        return null;
    }

    /**
     * Decategorise the transactions in the form
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return type
     */
    public function transactionsDecategorise(Request $request)
    {
        $transactions = $this->getFormTransactions($request);

        if (\is_null($transactions))
        {
            return 'No transactions were modified.';
        }
        $em = $this->getDoctrine()->getEntityManager();
        $mod_cnt = 0; // This will be the number of transactions modified
        foreach ($transactions->getTransactions() as $txn)
        {
            if (($txn->getFkCategoryId() != 0) || ($txn->getFkMerchantId() != 0))
            {
                // Was not already null.
                $mod_cnt++;
            }
            $txn->setFkCategoryId(null);
            $txn->setFkMerchantId(null);
            $em->persist($txn);
        }
        $em->flush();

        return "Modified $mod_cnt transactions : merchant and/or category were set to unassigned.";
    }

    /**
     * Function to categorise transactions based on the description.
     * Categorise all transactions by:
     * - Identify the merchant by matching each transaction decsription with the merchant default patterns.
     * - Map to a category via the merchant.
     * 
     * @param [IN] [Optional] $merchants : arrays of the merchants to match on.
     * @param [IN] [Optional] $override : if true, override already assigned transactions.
     * 												  if false only assign transactoins where category is null
     * 												  if absent (or null), read from options service.
     * @return type Array containing the total number of transactions and the number of matches made.
     */
    public function transactionsCategorise($merchants = null, $override = null)
    {
        if (\is_null($merchants))
        {
            // Get all merchants such that we have the patterns.
            $merchants = new Merchants();
            $merchants->setMerchants(
               $this->getDoctrine()
                  ->getRepository('BtgMyBudgetBundle:Merchant')
                  ->findAll()
            );
        }

        $merchant_default_categorories = Array();
        $merchant_pattern = Array();
        foreach ($merchants->getMerchants() as $merchant)
        {
            $merchant_default_categorories[$merchant->getMerchantId()] = $merchant->getFkCategoryId();
            $merchant_pattern[$merchant->getMerchantId()] = $merchant->getPattern();
        }

        // Get existing transactions 
        $transactions = new Transactions();
        $where = array();

        $options = $this->get('btg_options');

        if (\is_null($override))
        {
            $override = $options->GetOptionBoolean('OverrideAssignments');
        }

        if ($override == false)
        {
            $where['fk_category_id'] = null;
        }

        $transactions->setTransactions(
           $this->getDoctrine()
              ->getRepository('BtgMyBudgetBundle:Transaction')
              ->findBy($where)
        );

        $em = $this->getDoctrine()->getEntityManager();

        $txn_count = \count($transactions->getTransactions());
        $match_count = 0;


        foreach ($transactions->getTransactions() as $transaction)
        {
            // Determine matching merchant
            $transaction->setFkMerchantId(NULL);
            $transaction->setFkCategoryId(NULL);

            foreach ($merchant_pattern as $merchant_id => $pattern)
            {
                // stripos is case insensitive unlike strpos
                if (stripos($transaction->getDescription(), $pattern) !== false)
                {
                    // Match found
                    $transaction->setFkMerchantId($merchant_id);
                    $transaction->setFkCategoryId($merchant_default_categorories[$merchant_id]);
                    $match_count++;
                    break;
                }
            }

            $em->persist($transaction);
        }
        $em->flush();
        return array('txn_count' => $txn_count, 'match_count' => $match_count);
    }

    private function createNewMatch(&$em, $txn)
    {
        $cat_id = $txn->getFkCategoryId();
        $category = $this->getDoctrine()->getRepository('BtgMyBudgetBundle:Category')->find($cat_id);

        // Take the first 10 characters as the pattern
        //TODO make this configurable:
        $pattern = \substr($txn->getDescription(), 0, 20);
        // TODO : check that this does not already exist, if it does use more chars

        $merchant = new Merchant();
        $merchant->setPattern($pattern);
        $merchant->setName($txn->getDescription());
        $merchant->setFkCategoryId($cat_id);
        // Dont know why but under cetain circumstances setting of foreign object must be done:
        $merchant->setCategory($category);
        $em->persist($merchant);
        return $merchant;
    }

    /**
     * This function is used to create new matches based on the users input.
     * @param type $txns : an array containing transaction IDs as key and boolean as value.
     * @return number of new matches created.
     */
    private function createNewMatches($txns)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $new_merchants = array();

        foreach ($txns as $txn_id => $new_match)
        {
            if ($new_match === true)
            {
                $txn = $this->getDoctrine()->getRepository('BtgMyBudgetBundle:Transaction')->find($txn_id);
                $cat_id = $txn->getFkCategoryId();

                // Take the first 10 characters as the pattern
                $pattern = \substr($txn->getDescription(), 0, 20);
                // TODO : check that this does not already exist, if it does use more chars

                $merchant = new Merchant();
                $merchant->setPattern($pattern);
                $merchant->setName($txn->getDescription());
                $merchant->setFkCategoryId($cat_id);
                // Under cetain circumstances setting of foreign object must be done:
                $category = $this->getDoctrine()->getRepository('BtgMyBudgetBundle:Category')->find($cat_id);
                $merchant->setCategory($category);
                $em->persist($merchant);
                $new_merchants[$txn_id] = $merchant;
            }
        }
        $em->flush();

        // Update merchant and category ID of unallocaterd transactions that match this pattern
        if (\count($new_merchants) > 0)
        {
            // Now update the transactions to the new merchants
            foreach ($new_merchants as $txn_id => $merchant)
            {
                $txn = $this->getDoctrine()->getRepository('BtgMyBudgetBundle:Transaction')->find($txn_id);
                $txn->setMerchant($merchant);
                $txn->setFkMerchantId($merchant->getMerchantId());
                $em->persist($txn);
            }
            $em->flush();

            // Now auto-assign unassigned transactions in case they match one of the new patterns.
            $merchants = new Merchants();
            $merchants->setMerchants($new_merchants);
            $this->transactionsCategorise($merchants, false);
        }

        return \count($new_merchants);
    }

}
