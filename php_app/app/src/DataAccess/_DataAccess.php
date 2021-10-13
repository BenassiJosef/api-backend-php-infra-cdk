<?php

namespace App\DataAccess;

use Psr\Log\LoggerInterface;
use PDO;

/**
 * Class _DataAccess.
 */
class _DataAccess
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var \PDO
     */
    private $pdo;
    
    /**
     * @var \App\DataAccess
     */
    private $maintable;
    
    /**
     * @param \Psr\Log\LoggerInterface $logger
     * @param \PDO                     $pdo
     */
    public function __construct(LoggerInterface $logger, PDO $pdo, $table)
    {
        $this->logger    = $logger;
        $this->pdo       = $pdo;
        $this->maintable = $table;
    }

    /**
     * @return array
     */
    public function getAll($path, $arrparams)
    {
        $this->logger->info(substr(strrchr(rtrim(__CLASS__, '\\'), '\\'), 1).': '.__FUNCTION__);

        $table = $this->maintable != '' ? $this->maintable : $path;

        $orderby = "";
        foreach ($arrparams as $key => $value) {
        	if ($key = "sort") {
        		$orderby = " ORDER BY " . $value;
        		break;
        	}
        }

        $stmt = $this->pdo->prepare('SELECT * FROM '.$table.$orderby);
        $stmt->execute();
        if ($stmt) {
            $result = array();
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $result[] = $row;
            }
        } else {
        	$result = null;
        }

        return $result;
    }
    
    /**
     * @param int $id
     *
     * @return one object
     */
    public function get($path, $args)
    {
        $this->logger->info(substr(strrchr(rtrim(__CLASS__, '\\'), '\\'), 1).': '.__FUNCTION__);

        $table = $this->maintable != '' ? $this->maintable : $path;

        $sql = "SELECT * FROM ". $table . ' WHERE ' . implode(',', array_flip($args)) . ' = :' . implode(',', array_flip($args));

        $stmt = $this->pdo->prepare($sql);
        // bind the key
        $stmt->bindValue(':' . implode(',', array_flip($args)), implode(',', $args));

        $stmt->execute();
        if ($stmt) {
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        } else {
        	$result = null;
        }

        return $result;
    }

    /**
     * @param array $request_data
     *
     * @return int (last inserted id)
     */
    public function add($path, $request_data)
    {
        $this->logger->info(substr(strrchr(rtrim(__CLASS__, '\\'), '\\'), 1).': '.__FUNCTION__);

        $table = $this->maintable != '' ? $this->maintable : $path;

        if ($request_data == null) {
            return false;
        }

        $columnString = implode(',', array_flip($request_data));
        $valueString = ":".implode(',:', array_flip($request_data));

        $sql = "INSERT INTO " . $table . " (" . $columnString . ") VALUES (" . $valueString . ")";
        $stmt = $this->pdo->prepare($sql);

        foreach($request_data as $key => $value){
            $stmt->bindValue(':' . $key,$request_data[$key]);
        }

        $stmt->execute();

        return $this->pdo->lastInsertId();
    }

    /**
     * @param array $request_data
     *
     * @return bool
     */
    public function update($path, $args,$request_data)
    {
        $this->logger->info(substr(strrchr(rtrim(__CLASS__, '\\'), '\\'), 1).': '.__FUNCTION__);

        $table = $this->maintable != '' ? $this->maintable : $path;

        // if no data to update or not key set = return false
        if ($request_data == null || !isset($args[implode(',', array_flip($args))])) {
            return false;
        }

        $sets = 'SET ';
        foreach($request_data as $key => $value){
            $sets = $sets . $key . ' = :' . $key . ', ';
        }
        $sets = rtrim($sets, ", ");

        $sql = "UPDATE ". $table . ' ' . $sets . ' WHERE ' . implode(',', array_flip($args)) . ' = :' . implode(',', array_flip($args));
        
        $stmt = $this->pdo->prepare($sql);

        foreach($request_data as $key => $value){
            $stmt->bindValue(':' . $key,$request_data[$key]);
        }
        
        // bind the key
        $stmt->bindValue(':' . implode(',', array_flip($args)), implode(',', $args));

        $stmt->execute();

      	return ($stmt->rowCount() == 1) ? true : false;
    }

    /**
     * @param int pk
     *
     * @return bool
     */
    public function delete($path, $args)
    {
        $this->logger->info(substr(strrchr(rtrim(__CLASS__, '\\'), '\\'), 1).': '.__FUNCTION__);

        $table = $this->maintable != '' ? $this->maintable : $path;

        $sql = "DELETE FROM ". $table . ' WHERE ' . implode(',', array_flip($args)) . ' = :' . implode(',', array_flip($args));
        
        $stmt = $this->pdo->prepare($sql);
        // bind the key
        $stmt->bindValue(':' . implode(',', array_flip($args)), implode(',', $args));

        $stmt->execute();

      	return ($stmt->rowCount() > 0) ? true : false;
    }
}
