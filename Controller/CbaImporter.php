<?php
/*
 * Import class to import CBA files.
 * A bunch of CBA pdf statements were received that need to be imported. Zamzar.com converted thee these to text.
 * This script will import such files.
 * V1.0.0.1 David McEnhill 25th Feb 2014 
 */
namespace CbaImporter;
require_once("Importer.php");

use Importer;
use Importer\RESULT_KEY1;

abstract class States
{

    const FIND_START_DATE = 1;
    const FIND_END_DATE = 2;
    const FIND_TRANSACTIONS = 3;
    const PROCESS_NEXT_TXN_LINE = 4;

}


class CbaImporter extends Importer\Importer
{

    public $startDate;  // Date statement starts
    public $endDate;    // Date statement ends

    const months = 'Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec';
    // Indices in the results array:

    function __construct($txnSource, $txnSink)
    {
        $this->txnSource = $txnSource;
        $this->txnSink = $txnSink;
    }
    
    protected function open()
    {
        $this->txnSource->fopen();
    }
    
    protected function close()
    {
        $this->txnSource->fclose();        
    }

    private function showState($state)
    {
        $states = array(
            states::FIND_START_DATE => 'FIND_START_DATE',
            states::FIND_END_DATE => 'FIND_END_DATE',
            states::FIND_TRANSACTIONS => 'FIND_TRANSACTIONS',
            states::PROCESS_NEXT_TXN_LINE => 'PROCESS_NEXT_TXN_LINE'
        );
        if (\array_key_exists($state, $states))
        {
            self::cprint( "state:{{$states[$state]}}");
        }
        else
        {
            self::cprint('State:Invalid');
        }
    }
    
    /**
     * Import a CBA format file.
     * @return int Number of transactions in the file.
     * @assert('ValidFile') > 0
     */
    public function processTxns()
    {
        $nrCsvLines = 0;
        $state = States::FIND_START_DATE;

        if ($this->txnSource->fopen() === false)
        {
            throw new Exception("Error opening file:" . $this->txnSource->getFileName());
        }
               
        // Read the lines from file:
        while (($line = $this->txnSource->fgets()) !== \FALSE)
        {
            //$this->showState($state);
            //self::cprint($line);
            switch ($state)
            {
                case States::FIND_START_DATE:
                    if ($this->setStartDate($line))
                    {
                        $state = States::FIND_END_DATE;
                    }
                    break;
                case States::FIND_END_DATE:
                    if ($this->setEndDate($line))
                    {
                        $state = States::FIND_TRANSACTIONS;
                    }
                    break;
                case States::FIND_TRANSACTIONS:
                    if ($this->lookForTransaction($line) == \TRUE)
                    {
                        $more = false;
                        $results = $this->processTransaction($line, $more);
                        if ($more == \FALSE)
                        {
                            // A complete transaction has been read, save it.
                            $results = $this->xformResult($results, $this->startDate);
                            $this->txnSink->setTxn($results);
                            $nrCsvLines++;
                            unset($results);
                        }
                        else
                        {
                            $state = States::PROCESS_NEXT_TXN_LINE;
                        }
                    }
                    break;
                    
                case States::PROCESS_NEXT_TXN_LINE:
                    if ($this->processTransactionNextLine($line, $results) == \FALSE)
                    {
                        // No more lines in this transaction, complete transaction has been read, save it.
                        $results = $this->xformResult($results, $this->startDate);
                        $this->txnSink->setTxn($results);
                        unset($results);
                        $nrCsvLines++;
                        $state = States::FIND_TRANSACTIONS;
                    }
                    break;
                default:
                    throw new Exception("Invalid processing state.");
            }
        }

        // Close the reader
        $this->txnSource->fclose();
        
        return $nrCsvLines;
    }
    
    /**
     * Transform results : transform results as required.
     * @param type $result an array of results with string keys
     * @param startDate date that the statement starts, used to determine year of this transaction.
     * @return an array with the same keys as the input $result but with transformed values.
     */
    public static function xformResult($result, $startDate)
    {
        // Dont have the year yet so assume first it is in the start year:
        $year = $startDate->format('Y');
        $sdate = $result[RESULT_KEY1::DATE] . ' ' . $year;
        $date = \DateTime::createFromFormat('d M Y', $sdate);
        
        // As of php 5.2.2 can compare DateTime objects, before that had to use diff().
        if ($date < $startDate)
        {
            // In the next year hence should be using the year from endDate (assumes may not span more than two years).
            $date->add(new \DateInterval('P1Y'));
        }
        //$result[RESULT_KEY1::DATE] = $date->format('d/m/Y');   // required date as a string - this is how it was during unit testing
        $result[RESULT_KEY1::DATE] = $date;  // for real import.
        // accept the rest of $result as received.
        return $result;
    }
        
    /**
     * Look for the start date.
     * @param type $line : line to instpect
     * @param $pattern : pattern that needs to be matched to extract the date.
     * @assert ('Statement            4 (Page 1 of 16)','Statement begins') == FALSE
     * @assert ('Statement begins     4 September 2011','Statement begins') == '4 September 2011'
     * @return FALSE if not found else the start date string
     */
    public function lookForDate($line, $pattern)
    {
        $offset = stripos($line, $pattern);

        if ($offset === FALSE)
        {
            return FALSE;
        }
        $flags = 0;
        $matches = array();
        // This pattern looks for 1 or more digit, a space, one of more letters, then 4 digits.
        if (preg_match("/[0-9]+ [a-zA-Z]+ [0-9][0-9][0-9][0-9]/", $line, $matches, $flags, $offset) == 1)
        {
            return $matches[0];
        }
        return FALSE;
    }

    /**
     * Look for a debit amount in a string.
     * @param type $line
     * @param type $matches An array of the resulting matches. See preg_match().
     * @return boolean true if found.
     */
    private function lookForDebit($line, &$matches)
    {
        // Pattern matches 1 or more digits, the dot, then 2 digits then a space or two spaces, then a minus (or digit, not sure why data contains that).
        $pattern = '/ [0-9,]+\.[0-9][0-9]( |  )(\-|[0-9])/';
        if (preg_match($pattern, $line, $matches, PREG_OFFSET_CAPTURE) == 1)
        {
            return true;
        }
        return false;
    }

    /**
     * Look for a credit amount in a string.
     * @param type $line
     * @param type $matches An array of the resulting matches. See preg_match().
     * Assumes lookForDebit returns false.
     * @return boolean true if found.
     */
    private function lookForCredit($line, &$matches)
    {
        // Pattern matches 1 or more digits, the dot, then 2 digits 
        $pattern = '/ [0-9,]+\.[0-9][0-9]/';
        if (preg_match($pattern, $line, $matches, PREG_OFFSET_CAPTURE) == 1)
        {
            return true;
        }
        return false;
    }

    /**
     * Process a transaction.
     * @param string $line line containing the transaction
     * @param type $more : true if a transaction continues onto the next line.
     * @throws Exception if invalid data.
     * @return an array of results
     */
    public function processTransaction($line, &$more)
    {
        $more = false;
        // This pattern looks for 1 or more digits, a space and then a month as Jan,Feb...
        $patternDate = "/[0-9]+ (" . self::months . ")/";
        $matchesDate = array();
        $flags = PREG_OFFSET_CAPTURE;
        if (preg_match($patternDate, $line, $matchesDate, $flags) !== 1)
        {
            throw new Exception('No date found');
        }
        
        $result = array();
        $result[RESULT_KEY1::DATE] = $matchesDate[0][0];
        $result[RESULT_KEY1::DEBIT] = 0;
        $result[RESULT_KEY1::CREDIT] = 0;

        $dateEnd = $matchesDate[0][1] + strlen($matchesDate[0][0]); // Position at which date ends plus one.

        $matchesCrDr = array();
        if ($this->lookForDebit($line, $matchesCrDr) == true)
        {
            // Debit transaction : strip out + or 2 at the end, and thousands delimiter.
            $debit = \trim($matchesCrDr[0][0]);
            // Remove thousands comma
            $debit = \str_replace(",","",$debit);
            $result[RESULT_KEY1::DEBIT] = \substr($debit, 0, \strpos($debit, ' '));
            // Description is the string from the end of date until the debit.
            $description = \substr($line, $dateEnd, $matchesCrDr[0][1] - $dateEnd);
        }
        else if ($this->lookForCredit($line, $matchesCrDr) == true)
        {
            // Credit transaction
            $credit = \trim($matchesCrDr[0][0]);
            // Remove thousands comma
            $credit = \str_replace(",", "", $credit);
            $result[RESULT_KEY1::CREDIT] = $credit;
            // Description is the string from the end of date until the credit.
            $description = \substr($line, $dateEnd, $matchesCrDr[0][1] - $dateEnd);
        }
        else
        {
            // Multi line transaction:
            $more = true;
            // Description is the rest of the line after date.
            $description = \substr($line, $dateEnd);
        }
        $result[RESULT_KEY1::DESCRIPTION] = \trim($description);

        return $result;
    }

    /**
     * Continue processing a multi-line transaction.
     * @param string $line line containing the transaction
     * @param result [IN/OUT] $result Array containing results
     * @return boolean true if there are lines remaining, else false.
     */
    public function processTransactionNextLine($line, &$result)
    {
        $more = false;
        $matchesCrDr = array();
        if ($this->lookForDebit($line, $matchesCrDr) == true)
        {
            // Debit transaction : strip out + or 2 at the end.
            $debit = \trim($matchesCrDr[0][0]);
            // Remove thousands comma
            $debit = \str_replace(",","",$debit);
            $result[RESULT_KEY1::DEBIT] = \substr($debit, 0, \strpos($debit, ' '));
        }
        else if ($this->lookForCredit($line, $matchesCrDr) == true)
        {
            // Credit transaction
           $credit = \trim($matchesCrDr[0][0]);
           $credit = \str_replace(",", "", $credit);
           $result[RESULT_KEY1::CREDIT] = $credit;
        }
        else
        {
            // Have not found amount hence lines are remaining.
            $more = true;
        }
        return $more;
    }

    /**
     * Look for a transaction.
     * @param string $line line that might contain the transaction
     * @return boolean true if a transaction was found.
     */
    public function lookForTransaction($line)
    {
        // This pattern looks for 1 or more digits, a space and then a month as Jan,Feb...
        // and not followed by a 2 (^2) and three other digits (this is to prevent catching Opening Balance etc.
        $pattern = "/[0-9]+ (" . self::months . ") [^2][^0-9]{3}/";
        if (preg_match($pattern, $line) == 1)
        {
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Set the start date from a line containing it
     * @param type $line
     * @return boolean true if found.
     */
    public function setStartDate($line)
    {
        $sDate = $this->lookForDate($line, 'Statement begins');
        if ($sDate !== FALSE)
        {
            $this->startDate = \DateTime::createFromFormat('d M Y', $sDate);
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Set the start date from a line containing it
     * @param type $line
     * @return boolean true if found.
     */
    public function setEndDate($line)
    {
        $sDate = $this->lookForDate($line, 'Statement ends');
        if ($sDate !== FALSE)
        {
            $this->endDate = \DateTime::createFromFormat('d M Y', $sDate);
            return TRUE;
        }
        return FALSE;
    }
}

?>