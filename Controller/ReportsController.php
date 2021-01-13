<?php

namespace Btg\MyBudgetBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Btg\MyBudgetBundle\Entity\Categories;

class ReportsController extends Controller
{
    // Used in MySQL for WEEK(), YEARWEEK() etc mode 

    const SUNDAY_1_53 = 2;
    const MONDAY_1_53 = 3;

    protected $week_to_date = array(
       self::SUNDAY_1_53 => '%X%V %W', // Must have a day of the week to get a date!
       self::MONDAY_1_53 => '%x%v %W'
    );

    // Numeric report action 
    public function numericAction(Request $request)
    {
        // Set the defaults
        $default_end_date = new \DateTime();
        $default_start_date = new \DateTime(); // Now
        $default_start_date->sub(new \DateInterval('P3M')); // Three Months ago
        $default_interval = 'weekly';
        $default_show_gaps = false; // whether to show data where all the results are zero
        // Get control vairables from the session or use the defaults.
        $session = $this->getRequest()->getSession();
        $start_date = $session->get('rp_startdate', $default_start_date);
        $end_date = $session->get('rp_enddate', $default_end_date);
        $interval = $session->get('rp_interval', $default_interval);
        $show_gaps = $session->get('rp_show_gaps', $default_show_gaps);

        $debug_msg = '';
        // The years to be available in the choice:
        $today = \getdate();
        $this_year = $today['year'];
        $years = array($this_year, $this_year - 1, $this_year - 2, $this_year - 3, $this_year - 4);

        $intervals = array('daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly');

        $defaultData = array(
           'message' => 'Select Result Parameters',
           'startdate' => $start_date,
           'enddate' => $end_date,
           'required_interval' => $interval,
           'showgaps' => $show_gaps);

        $date_selection_fmt = 'dd-MMM-yyyy';

        $form = $this->createFormBuilder($defaultData)
           ->add('startdate', 'date', array('format' => $date_selection_fmt, 'years' => $years))
           ->add('enddate', 'date', array('format' => $date_selection_fmt, 'years' => $years))
           ->add('required_interval', 'choice', array('choices' => $intervals))
           ->add('showgaps', 'checkbox', array())
           ->getForm();
	$form->handleRequest($request);

        if ($form->isValid())
        //if ($request->getMethod() == 'POST')
        {
            if ($request->request->has('Refresh'))
            {
                //$form->bindRequest($request);
                $result = $form->getData();
                $start_date = $result['startdate'];
                $end_date = $result['enddate'];
                $show_gaps = $result['showgaps'];
                $interval = $result['required_interval'];

                // Save to session data
                $session->set('rp_startdate', $start_date);
                $session->set('rp_enddate', $end_date);
                $session->set('rp_interval', $interval);
                $session->set('rp_show_gaps', $show_gaps);
            }
            else if ($request->request->has('Defaults'))
            {
                // Clear cookie data to get the defaults
                $session->remove('startdate');
                $session->remove('enddate');
                return $this->redirect($this->generateUrl('BtgMyBudgetBundle_reports'));
            }
        }

        switch ($interval)
        {
            default:
            case 'daily':
                $date_grouper = 'txn.date';
                $date_formatter = 'DATE_FORMAT(txn.date,\'%d-%b-%Y\')';
                $delta_date = new \DateInterval('P1D'); // That is 1 day
                $php_date_format = 'd-M-Y';
                break;
            case 'weekly':
                // Week starting Monday
                $start_day = 'Monday';
                $mode = self::MONDAY_1_53;
                $date_grouper = "YEARWEEK(txn.date, $mode)";
                $wtd = $this->week_to_date[$mode];
                $date_formatter = "DATE_FORMAT(STR_TO_DATE(CONCAT($date_grouper,' $start_day'),'$wtd'),'%d-%b-%Y')";
                $delta_date = new \DateInterval('P7D'); // That is 1 week
                $php_date_format = 'd-M-Y';
                //TODO adjust start and end dates to begin and end on a week (Monday)
                break;
            case 'monthly':
                $date_grouper = "DATE_FORMAT(txn.date,'%Y%m')";
                $date_formatter = "DATE_FORMAT(txn.date,'%b-%Y')";
                $delta_date = new \DateInterval('P1M'); // That is 1 month
                $php_date_format = 'M-Y';
                break;
        }


        //
        // Build an array of the dates, date => as presented
        $xdates = array();
        if ($show_gaps == true)
        {
            $thedate = new \DateTime($start_date->format('d-M-Y'));

            if ($interval == 'weekly')
            {
                // Need to adjust to start on Monday
                echo "<br>Before thedate:{$thedate->format($php_date_format)}</br>";
                $thedate->modify('Monday this week');
                echo "<br>Before thedate:{$thedate->format($php_date_format)}</br>";
            }

            // $diff = $end_date - $thedate , DateInterval is returned where invert=1 means negative.
            while ((($diff = $thedate->diff($end_date, false)) !== false) && ($diff->invert == 0))
            {
                $sdate = $thedate->format($php_date_format);
                $xdates[$sdate] = $sdate;
                $thedate = $thedate->add($delta_date);
            }
        }

        // Get all categories in the order you want them rendered.
        $categories = new Categories();
        $where = array();  // where clause for query (all)
        $orderby = array('name' => 'ASC'); // order by clause for the query
        $categories->setCategories(
           $this->getDoctrine()
              ->getRepository('BtgMyBudgetBundle:Category')
              ->findBy($where, $orderby)
        );

        $xcategories = Array();
        foreach ($categories->getCategories() as $category)
        {
            $xcategories[$category->getCategoryId()] = $category->getName();
        }

        $em = $this->getDoctrine()->getEntityManager();
        $rsm = new \Doctrine\ORM\Query\ResultSetMapping();
        $rsm->AddScalarResult('datestamp', 'datestamp');
        $rsm->AddScalarResult('credit', 'credit');
        $rsm->AddScalarResult('debit', 'debit');
        $rsm->AddScalarResult('amount', 'amount');
        $rsm->AddScalarResult('category', 'category');

        // Sorry using native SQL: See http://docs.doctrine-project.org/en/latest/reference/native-sql.html
        $query = $em->createNativeQuery(
           "SELECT $date_formatter datestamp, cat.name category, SUM(txn.credit) credit, SUM(txn.debit) debit, FORMAT((SUM(txn.debit)- SUM(txn.credit)),2) amount " .
           'FROM transaction txn ' .
           'JOIN category cat ON cat.category_id = txn.fk_category_id ' .
           'WHERE (txn.date >= ? AND txn.date <= ?) ' .
           "GROUP BY $date_grouper, cat.category_id " .
           "ORDER BY $date_grouper, cat.name", $rsm
        );

        //echo $query->getSQL();

        $query->setParameter(1, $start_date);
        $query->setParameter(2, $end_date);
        $results = $query->getResult();

        //var_dump($results);

        $xresults = array(); // transformed results
        // Transform the results array into an array where the datestamp and category are the keys.
        foreach ($results as $result)
        {
            $datestamp = $result['datestamp'];
            $category = $result['category'];
            if (\array_key_exists($datestamp, $xresults) == false)
            {
                // Create the array element for the date if does not exist already.
                $xresults[$datestamp] = array();
            }
            $xresults[$datestamp][$category] = $result['amount'];

            if ($show_gaps == false)
            {
                $xdates[$datestamp] = $datestamp;
            }

            // Useful debug to see if problem is SQL,this transformation or presentation:
            //if ($category == 'Groceries')
            //{
            //   echo "<br>$datestamp $category:" . $result['amount'] . " => ". $xresults[$datestamp][$category] . "</br>";
            //}
        }

        //var_dump($xdates);
        //$debug_msg = "<h1>I am the debug message</h1>";
        // TODO: make this selectable:
        
        $view = 'BtgMyBudgetBundle:Reports:numeric1.html.twig'; // each category has a column
        //$view = 'BtgMyBudgetBundle:Reports:numeric_rows.html.twig'; // each category has a row

        $wtfResult = $this->dumpToFile($xdates,$xcategories,$xresults);
        if ($wtfResult !== true)
        {
          $debug_msg = $wtfResult;
        }
        
        return $this->render($view, array(
              'form' => $form->createView(),
              'debug_msg' => $debug_msg,
              'xdates' => $xdates,
              'xcategories' => $xcategories,
              'xresults' => $xresults)
        );
    }

    function dumpToFile($xdates,$xcategories,$xresults)
    {
      $downloadDir = $this->get('kernel')->getRootDir() . '/../web/downloads';
      $filename = "expenses.csv";
      $file= $downloadDir . '/' . $filename;
      $fd = fopen($file, 'w');
      if ($fd === false) return "Sorry error creating file.";

      if (fputcsv($fd,$xcategories) === false) return "Sorry error writing headings.";

      foreach ($xdates as $date)
      {
        $result = $xresults[$date];
        $line = array();
        $line[] = $date;
        foreach ($xcategories as $category)
        {
          if (array_key_exists($category, $result))
          {
            $line[] = str_replace(',','',$result[$category]);
          }
          else
          {   
            $line[] = '0.00';
          }
        }
        if (fputcsv($fd,$line) === false) return "Sorry error writing file.";
      }
      fclose($fd);
      return true;
    }
}
