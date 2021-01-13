<?php

namespace Btg\MyBudgetBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Btg\MyBudgetBundle\Entity\Import;
use Btg\MyBudgetBundle\Form\Type\ImportType;
use Btg\MyBudgetBundle\Entity\FieldPositions;
use Btg\MyBudgetBundle\Entity\Transaction;
use Btg\MyBudgetBundle\Entity\ImportedTxn;
use Btg\MyBudgetBundle\Controller\TxnSinkTxnEntity;
use Importer;   // The dynamic included import class will be extended from this namespace.

class ImportController extends Controller
{

    //TODO: use the one in ImportedTxn class.

    const IMP_STATUS_PENDING = 0;
    const IMP_STATUS_ACCEPTED = 1;
    const IMP_STATUS_DUPLICATE = 2;
    const IMP_STATUS_FORMAT_ERROR = 3;
    const IMP_STATUS_AUTHORISATION = 4;
    const IMP_STATUS_AUTH_FINALISATION = 5;

    // Options:
    private $auth_pattern;
    private $pos_auth_pattern_end;
    private $auth_retention_days;
    private $import_auths;

    private function importFileToDb1($em, $import)
    {
        $filename = $import->getAbsolutePath();

        $fd = fopen($filename, 'r');

        if ($fd === false)
        {
            return array('error' => "Error opening file $filename");
        }

        // Skip the header line
        if (fgets($fd) === false)
        {
            return array('error' => "File is empty:', $filename");
        }

        // First get all the transactions into the ImportedTxn table.
        $pos = new FieldPositions();
        $count = 0;
        while (($vals = fgetcsv($fd)) != null)
        {
            $new_txn = new ImportedTxn();
            $date = \DateTime::createFromFormat('d/m/Y', $vals[$pos->getDateIndex()]);

            if ($date === false)
            {
                return array('error' => "File has an invalid date: $filename," .
                   "Value {$vals[$pos->getDateIndex()]} at line $count");
            }

            $new_txn->setDate($date);
            $new_txn->setDescription($vals[$pos->getDescriptionIndex()]);
            $new_txn->setCredit($vals[$pos->getCreditIndex()]);
            $new_txn->setDebit($vals[$pos->getDebitIndex()]);
            $new_txn->setImport($import);
            $new_txn->setStatus(self::IMP_STATUS_PENDING);
            $em->persist($new_txn);
            $count++;
        }
        fclose($fd);

        return array('count' => $count);
    }
    
    /**
     * Import a file into the Imported table.
     * @param type $em : entity manager object.
     * @param type $import : the import entity.
     */
    public function importFileToDb($em, $import)
    {
        $filename = $import->getAbsolutePath();
        // Format for sources is namespace\class name.
        $sources = array( 'CBA' => 'CbaImporter\CbaImporter', 'BWA' => 'BankwestImporter' );
        $scripts = array( 'CBA' => 'CbaImporter.php', 'BWA' => 'BankwestImporter.php' );
        $client = 'CBA';
        // Include the import class file
        include $scripts[$client];
        $txnSrc = new Importer\TxnSourceFile($filename);
        if ($em == \NULL)
        {
            // Bad as, should be injected but here is a fudge factory
            $txnSink = new Importer\TxnSinkArray();
        }
        else
        {
            $txnSink = new TxnSinkTxnEntity('Btg\MyBudgetBundle\Entity\ImportedTxn', $em, $import);
        }
        $imp = new $sources[$client]($txnSrc, $txnSink);
        $count = $imp->processTxns();
        return array('count' => $count);        
    }

    // Import action (need to reorganise a bit, move non import actions into their own files.
    public function processAction(Request $request)
    {
        $import = new Import();

        $debug_msg = '';
        $form = $this->createForm(new ImportType(), $import);
        $em = $this->getDoctrine()->getEntityManager();

        $this->loadOptions();

        $form->handleRequest($request);

        if ($form->isValid())
//		if ($request->getMethod() == 'POST')
        {
            // process the file
            //$form->bindRequest($request);
            //if ($form->isValid())
            {
                $import->setDateImported(new \DateTime());
                $import->setTranCount(0);

                // Upload the file
                $import->upload();

                // Now process the file
               $result = $this->importFileToDb1($em, $import); //works for bankwest
               //$result = $this->importFileToDb($em, $import); // CBA

                if (\array_key_exists('error', $result))
                {
                    $this->get('session')->getFlashBag()->add('notice', $result['error']);
                    return $this->redirect($this->generateUrl('BtgMyBudgetBundle_import'));
                }

                $import->setTranCount($result['count']);

                // Persist the import data to the database.
                $em->persist($import);

                // Flush import and all transactions
                $em->flush();

                // Determine duplicates (sorry but using native SQL):
                $em->getConnection()->executeUpdate(
                   "UPDATE importedtxn it SET status = " . self::IMP_STATUS_DUPLICATE .
                   " WHERE (it.date,it.description,it.debit,it.credit) IN " .
                   "(SELECT date,description,debit,credit FROM transaction) " .
                   "AND it.status = " . self::IMP_STATUS_PENDING);

                // Find authorisation finalisations : create a temporary table of import IDs for auth and finalisation of same transaction.
                $em->getConnection()->executeUpdate('DROP TEMPORARY TABLE IF EXISTS finalised_auths');
                $em->getConnection()->executeUpdate('CREATE TEMPORARY TABLE finalised_auths AS' .
                   " SELECT imp.imported_txn_id fin_imported_txn_id,txn.transaction_id txn_id, imp.description fin_descr, imp.date fin_date" .
                   " FROM importedtxn imp, transaction txn" .
                   " WHERE imp.status = " . self::IMP_STATUS_PENDING .
                   " AND imp.description NOT LIKE '{$this->auth_pattern}%'" .
                   " AND txn.description LIKE '{$this->auth_pattern}%'" .
                   " AND imp.date >= txn.date" .
                   " AND DATEDIFF(imp.date,txn.date) < 8" .
                   " AND txn.debit = imp.debit" .
                   " AND txn.credit = imp.credit" .
                   " AND SUBSTRING(txn.description,$this->pos_auth_pattern_end,10) = SUBSTRING(imp.description,1,10)");

                // Mark authorisation finalisations such that they will not be imported below:
                $em->getConnection()->executeUpdate('UPDATE importedtxn it SET status = ' . self::IMP_STATUS_AUTH_FINALISATION .
                   ' WHERE it.imported_txn_id IN (SELECT fin_imported_txn_id FROM finalised_auths)');

                // Update the date and descriptions in transactions
                $em->getConnection()->executeUpdate("UPDATE transaction, finalised_auths SET" .
                   " transaction.description = finalised_auths.fin_descr," .
                   " transaction.date = finalised_auths.fin_date" .
                   " WHERE transaction.transaction_id = finalised_auths.txn_id");

                $em->getConnection()->exec('DROP TEMPORARY TABLE IF EXISTS finalised_auths');

                // Insert into transactions and update status in importedtxn
                $where = array('status' => self::IMP_STATUS_PENDING);  // where clause for query
                $orderby = array('date' => 'ASC'); // order by clause for the query
                $new_txns = $this->getDoctrine()
                   ->getRepository('BtgMyBudgetBundle:ImportedTxn')
                   ->findBy($where, $orderby);


                foreach ($new_txns as $new_txn)
                {
                    $import_txn = FALSE;
                    if (\stripos($new_txn->getDescription(), $this->auth_pattern) !== FALSE)
                    {
                        // Authoriation 
                        $new_txn->setStatus(self::IMP_STATUS_AUTHORISATION);
                        $import_txn = $this->import_auths;
                    }
                    else
                    {
                        $new_txn->setStatus(self::IMP_STATUS_ACCEPTED);
                        $import_txn = TRUE;
                    }

                    if ($import_txn == TRUE)
                    {
                        $txn = new Transaction();
                        $txn->setDate($new_txn->getDate());
                        $txn->setDescription($new_txn->getDescription());
                        $txn->setCredit($new_txn->getCredit());
                        $txn->setDebit($new_txn->getDebit());
                        $txn->setImport($import);
                        $em->persist($txn);
                    }
                    $em->persist($new_txn);
                }
                $em->flush();

                //TODO : following the completion of the above, actual imported and duplicate counts
                // can be made on the importedtxn table for display.

                $filename = $import->getFilename();
                $this->get('session')->getFlashBag()->add('notice', "File $filename has been imported");
                return $this->redirect($this->generateUrl('BtgMyBudgetBundle_import'));
            }
        }

        // Here you accidently found you can pass objects into Twig and use the get methods from Twig eg:
//      $imports = array();
//      $where = array();    // where clause for query (all)
//      $orderby = array('date_imported' => 'DESC'); // order by clause for the query
//      $imports = $this->getDoctrine()
//                     ->getRepository('BtgMyBudgetBundle:Import')
//                     ->findBy($where, $orderby);
//      Then in twig:
//      {% for import_ext in imports_ext %}
//      <li>
//         <div class="col1">{{ import_ext.date_imported|date("d/m/Y") }}</div> 
//      ....
// 

        $auth_actions = $this->deleteFinalisedAuthorisations();
        $debug_msg = "Auths removed: {$auth_actions['nr_finalised']}. Auths Deleted: {$auth_actions['nr_auths_deleted']}";

        // Join import to transaction to get the import details plus earliest and latest transaction dates.
        // Note that since Transaction is the one with the associated Import (variable) we need to start with it.
        // Using a native query (works on MSSQL and MySql) to get the counts:
        $rsm = new \Doctrine\ORM\Query\ResultSetMapping();
        $rsm->AddScalarResult('date_imported', 'date_imported'); //1
        $rsm->AddScalarResult('filename', 'filename'); //2
        $rsm->AddScalarResult('comment', 'comment'); //3
        $rsm->AddScalarResult('date_start', 'date_start'); //4
        $rsm->AddScalarResult('date_end', 'date_end'); //5
        $rsm->AddScalarResult('nr_accepted', 'nr_accepted'); //6   
        $rsm->AddScalarResult('nr_duplicates', 'nr_duplicates'); //7
        $rsm->addScalarResult('nr_auth_finalisations', 'nr_auth_finalisations'); // 8
        $rsm->addScalarResult('nr_authorisations', 'nr_authorisations'); // 9
        $rsm->AddScalarResult('nr_not_imported', 'nr_not_imported'); //10
        // Sorry using native SQL: See http://docs.doctrine-project.org/en/latest/reference/native-sql.html
        $query = $em->createNativeQuery(
           'SELECT ' .
           'imp.date_imported date_imported,' . //1
           'imp.filename filename,' . //2
           'imp.comment comment,' . //3
           'MIN(itxn.date) date_start,' . //4
           'MAX(itxn.date) date_end,' . //5
           'COUNT(CASE WHEN itxn.status = ' . self::IMP_STATUS_ACCEPTED . ' THEN itxn.status  ELSE NULL END) nr_accepted, ' . //6
           'COUNT(CASE WHEN itxn.status = ' . self::IMP_STATUS_DUPLICATE . ' THEN itxn.status ELSE NULL END) nr_duplicates, ' . //7
           'COUNT( CASE WHEN itxn.status = ' . self::IMP_STATUS_AUTH_FINALISATION . ' THEN itxn.status ELSE NULL END) nr_auth_finalisations, ' . //8 
           'COUNT( CASE WHEN itxn.status = ' . self::IMP_STATUS_AUTHORISATION . ' THEN itxn.status ELSE NULL END) nr_authorisations, ' . //9
           'COUNT(CASE WHEN itxn.status = ' . self::IMP_STATUS_FORMAT_ERROR . ' OR ' .
           ' itxn.status = ' . self::IMP_STATUS_PENDING . ' THEN itxn.status ELSE NULL END) nr_not_imported ' . //10
           'FROM import imp ' .
           'JOIN importedtxn itxn ON imp.import_id = itxn.fk_import_id ' .
           'GROUP BY itxn.fk_import_id ' .
           'ORDER BY imp.date_imported DESC', $rsm
        );
        $imports_ext = $query->getResult();

        // To add to array:
        //foreach ($imports_ext as $key => $dummy) {
        //   $imports_ext[$key]['date_start'] = new \DateTime('2012-01-01');
        //Yeah it will be rendered:
        //var_dump($imports_ext);

        return $this->render('BtgMyBudgetBundle:Import:import.html.twig', array(
              'form' => $form->createView(),
              'debug_msg' => $debug_msg,
              //'imports' => $imports,  WONDERFUL YOU CAN PASS MORE THAN ONE RESULT INTO TWIG!
              'imports_ext' => $imports_ext)
        );
    }

    /**
     * Load some widely used options by import.
     */
    private function loadOptions()
    {
        $options = $this->get('btg_options');
        $this->auth_pattern = $options->GetOption('AuthorisationPattern');

        if ($this->auth_pattern != null)
        {
            $this->auth_pattern = \trim($this->auth_pattern, '[]');
        }
        else
        {
            $this->auth_pattern = 'Authorisation Only - ';
        }

        $this->pos_auth_pattern_end = \strlen($this->auth_pattern) + 1;
        $this->auth_retention_days = $options->GetOption('AuthorisationsRetainedDays');
        $this->auth_retention_days = ($this->auth_retention_days != null) ? $this->auth_retention_days : 90;
        $this->import_auths = $options->GetOptionBoolean('ImportAuthorisations');

        /*
          echo "<br>auth_pattern:{$this->auth_pattern}<br>";
          echo "auth_retention_days:{$this->auth_retention_days}<br>";
          echo "import_auths:{$this->import_auths}<br>";
          echo "import_auths:{$this->import_auths}<br>";
          echo "pos_auth_pattern_end:{$this->pos_auth_pattern_end}<br>";
         */
    }

    /**
     * Used to remove authorisations that are older than the retention period.
     * Also attempts to sort out transactions from before authorisation functionality was added.
     */
    private function deleteFinalisedAuthorisations()
    {
        $em = $this->getDoctrine()->getEntityManager();

        // Update import status for authorisations:
        $em->getConnection()->executeUpdate(
           "UPDATE importedtxn SET status = " . self::IMP_STATUS_AUTHORISATION .
           " WHERE description LIKE '{$this->auth_pattern}%'");

        // Find authorisation finalisations : create a temporary table of import IDs for auth and finalisation of same transaction.
        $em->getConnection()->executeUpdate('DROP TEMPORARY TABLE IF EXISTS finalised_auths');
        $em->getConnection()->executeUpdate(
           "CREATE TEMPORARY TABLE finalised_auths AS" .
           " SELECT imp.imported_txn_id fin_imported_txn_id,txn.transaction_id txn_id, imp.description fin_descr, imp.date fin_date" .
           " FROM importedtxn imp, transaction txn" .
           " WHERE imp.status = " . self::IMP_STATUS_ACCEPTED .
           " AND imp.description NOT LIKE '{$this->auth_pattern}%'" .
           " AND txn.description LIKE '{$this->auth_pattern}%'" .
           " AND imp.date >= txn.date" .
           " AND DATEDIFF(imp.date,txn.date) < 8" .
           " AND txn.debit = imp.debit" .
           " AND txn.credit = imp.credit" .
           " AND SUBSTRING(txn.description,$this->pos_auth_pattern_end,10) = SUBSTRING(imp.description,1,10)");

        // Mark authorisation finalisations such that they will not be imported below:
        $em->getConnection()->executeUpdate('UPDATE importedtxn it SET status = ' . self::IMP_STATUS_AUTH_FINALISATION .
           ' WHERE it.imported_txn_id IN (SELECT fin_imported_txn_id FROM finalised_auths)');

        // Delete the authorisation transactions
        $nr_finalised = $em->getConnection()->executeUpdate("DELETE FROM transaction" .
           " WHERE transaction.transaction_id IN" .
           " (SELECT txn_id FROM finalised_auths)");

        $em->getConnection()->executeUpdate('DROP TEMPORARY TABLE IF EXISTS finalised_auths');

        // Delete any other remaining old authorisations (even if not finalised):
        $nr_auths_deleted = $em->getConnection()->executeUpdate(
           "DELETE FROM transaction" .
           " WHERE DATEDIFF( CURDATE(), transaction.date) > $this->auth_retention_days" .
           " AND transaction.description LIKE '{$this->auth_pattern}%'");

        return array('nr_finalised' => $nr_finalised, 'nr_auths_deleted' => $nr_auths_deleted);
    }

}

?>
