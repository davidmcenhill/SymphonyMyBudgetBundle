<?php
/* 
 * Abstract call that the Import Controller will use to import files.
 * Implementations of this class are used to cater for different file formats.
 */
namespace Importer;

/**
 * Class to hold the keys for the results.
 */
abstract class RESULT_KEY1
{
    const DATE = 'Date';
    const CREDIT = 'Credit';
    const DEBIT = 'Debit';
    const DESCRIPTION = 'Description';
}

/**
 * Class to handle reading a record from the transaction source, such as a file.
 */
abstract class TxnSource
{   
    /**
     * Open the reader. See php fopen() function
     */
    abstract public function fopen();

    /**
     * Close the reader.
     */
    abstract public function fclose();

    /**
     * Get a line (see php fgets()).
     */
    abstract public function fgets();
    
}

/**
 * For testing, a transaction source consisting of an array of strings (each string is a record).
 */
class TxnSourceArray extends TxnSource
{

    private $lineNr;
    private $lines;

    function __construct($lines)
    {
        $this->lines = $lines;
        $this->lineNr = 0;
    }

    public function setLines($lines)
    {
        $this->lines = $lines;
        $this->lineNr = 0;
        //var_dump($lines);
    }

    public function fopen()
    {
        return true;
    }

    /**
     * Close the reader.
     */
    public function fclose()
    {
        return true;
    }

    /**
     * Get a line (see php fgets()).
     */
    public function fgets()
    {
        if (\array_key_exists($this->lineNr, $this->lines) == true)
        {
            //echo "fgets():" . $this->lines[$this->lineNr];
            return $this->lines[$this->lineNr++];
        }
        //echo "fgets():";
        return FALSE;
    }

}

/**
 * Class to receive a transaction for testing (Transaction Entity)
 */
class ArrayTransaction
{
    private $result;
    
    function __construct()
    {
        $this->result = array();
    }

    public function setDescription($description)
    {
        $this->result[RESULT_KEY1::DESCRIPTION] = $description;
    }
    public function setDate($date)
    {
        $this->result[RESULT_KEY1::DATE] = $date;
    }
    public function setDebit($debit)
    {
        $this->result[RESULT_KEY1::DEBIT] = $debit;
    }
    public function setCredit($credit)
    {
        $this->result[RESULT_KEY1::CREDIT] = $credit;
    }
    public function getResult()
    {
        return $this->result;
    }
}

/**
 * Class to handle writing of a transaction record.
 */
abstract class TxnSink
{
    private $txnClassName;  // This is the name of the class implementing the Transaction Entity.
    
    /**
     * 
     * @param type $txnClassName Name of the class to write the transaction to.
     */
    function __construct( $txnClassName )
    {
        $this->txnClassName = $txnClassName;
    }

    /**
     * Open the writer. See php fopen() function
     */
    abstract public function fopen();

    /**
     * Close the writer.
     */
    abstract public function fclose();

    /**
     * Write a line (see php fgets()).
     */
    //abstract public function fputs($line);
    
    /**
     * Sets the data for a transaction. 
     * @param type $result an array of results with keys in RESULT_KEY
     */
    public function setTxn($result)
    {
        $txn = new $this->txnClassName();
        
        foreach ($result as $fieldName => $value)
        {
            $functionName = "set$fieldName";
            $txn->$functionName($value);
        }
        return $txn;
    }

    public function toString()
    {
        $str = "TxnSink" . (isset($this->txnClassName) ? $this->txnClassName : 'Null');
        return $str;
    }
}

abstract class Importer
{
    protected $txnClassName;  // This is the name of the class implementing the Transaction Entity.
    protected $txnSource;
    protected $txnSink;
    
    /**
     * Construct the Importer object
     * @param type $txnClassName : string containing the name of the class for transaction persistence (entity)
     */
    function __construct($txnClassName)
    {
        $this->txnClassName = $txnClassName;
    }

    protected abstract function open();   
    
    /**
     * @return number of transactions extracted
     */
    public abstract function processTxns();
    
    protected abstract function close();   
    
    /**
     * Print a line to the console with a newline at the end.
     * @param type $line string to print
     */
    public static function cprint($line)
    {
       // Commented out because does now work when running from a webpage:
        //fwrite(STDOUT, "$line\n");
    }

}

/**
 * Implementation of TxnSource where each transaction is a line in a file. 
 */
class TxnSourceFile
{
    protected $fileName;
    protected $handle;
    function __construct($fileName)
    {
        $this->fileName = $fileName;
    }


    /**
     * Open the reader. See php fopen() function
     */
    public function fopen()
    {
        $this->handle = fopen($this->fileName, 'r');
        //$this->handle = fopen($fileName, 'a+');

        if ($this->handle === false)
        {
            // TODO : Throw exception
        }
    }

    /**
     * Close the reader.
     */
    public function fclose()
    {
        fclose($this->handle);   
        unset($this->handle);
        unset($this->fileName);
    }

    /**
     * Get a line (see php fgets()).
     */
    public function fgets()
    {
        $line = fgets($this->handle);
        //echo ($line !== null ? $line : '<empty>');
        return $line;
    }
    
    /**
     * 
     * @return string filename
     */
    public function getFileName()
    {
        return $this->fileName;
    }
}

/**
 * Transaction Sink used for testing: put the sinking transactions into an array.
 */
class TxnSinkArray extends TxnSink
{
   private $resultArrayTransaction;
    
    function __construct()
    {
        $txnClassName = 'Importer\\ArrayTransaction';    
        $this->resultArrayTransaction = array();
        parent::__construct( $txnClassName);
    }
    
    /**
     * Get the last tranaction.
     * @return Last transaction sent here, else NULL;
     */
    public function getLastTxn()
    {
        if (isset($this->resultArrayTransaction) === \FALSE)
        {
            return NULL;
        }
        $cnt = count($this->resultArrayTransaction);
        if ($cnt == 0)
        {
            return NULL;
        }
        return $this->resultArrayTransaction[$cnt-1]->getResult();
    }
    
    /**
     * Get the Nth transaction.
     * @param index 0..N of the transaction to get.
     * @return The Nth transation el
     */
    public function getNthTxn($index)
    {
        if (isset($this->resultArrayTransaction) === \FALSE)
        {
            return NULL;
        }
        $cnt = count($this->resultArrayTransaction);
        if ($cnt <= $index)
        {
            return NULL;
        }
        return $this->resultArrayTransaction[$index]->getResult();
    }
    /**
     * Write out a transction.
     * @param type $result Transaction to write is an array RESULT_KEY => values
     */
    public function setTxn($result)
    {
        $this->resultArrayTransaction[] = parent::setTxn($result);
    }

    /**
     * Open the writer. See php fopen() function
     */
    public function fopen() 
    {        
    }

    /**
     * Close the writer.
     */
    public function fclose()
    {
    }
 
    /**
     * Do a var_dump into a string.
     * @param type $var Variable to dump
     * @return string containing the var dump
     */
    private static function varDumpToString ($var)
    {
        ob_start();
        var_dump($var);
        $result = ob_get_clean();
        return $result;
    }
}


?>