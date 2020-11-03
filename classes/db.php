<?php
/*
 * A general DB utility class using PDO.
 * Copyright for this package of code: https://waggies.net/ws/copyright.txt.
 */

class Db {
	public $dbh;
	public $prep;
	public $executeReturn;

	function __construct()
	{
		$this->dbh = new PDO("mysql:dbname=dbyctjn2y3yw66;host=localhost", 'uz975kr7ndv2n', 'i%11#]*117|b');
		$this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
		$this->dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
	}


	function prepare($stmt)
	{
		$this->prep = $this->dbh->prepare($stmt);
		return $this->prep;
	}


	function execute($arr = array()) {
		$this->executeReturn = $this->prep->execute($arr);
		return $this->executeReturn;
	}


	function exec($stmt) {
		$this->execReturn = $this->dbh->exec($stmt);
		return $this->execReturn;
	}


	function fetch($tablename, $where = '', $orderby = '') {
	    $stmt = "select * FROM $tablename";
	    if ($where) {
	        $stmt .= " WHERE $where";
	    }
	    if ($orderby) {
	        $stmt .= " ORDER BY $orderby";
	    }
	    $prep = $this->prepare($stmt);
	    $prep->execute();
	    $rows = $prep->fetch(PDO::FETCH_ASSOC);
	    return $rows;
	}


	function fetchLast($tablename) {
	    $stmt = 'select * FROM ' . $tablename . ' ORDER BY id DESC';
	    $prep = $this->prepare($stmt);
	    $prep->execute();
	    $rows = $prep->fetch(PDO::FETCH_ASSOC);
	    return $rows;
	}


	function fetchLastN($tablename, $n = 1) {
	    $stmt = "select * FROM $tablename  ORDER BY id DESC LIMIT $n";
	    $prep = $this->prepare($stmt);
	    $prep->execute();
	    $rows = $prep->fetchAll(PDO::FETCH_ASSOC);
	    return $rows;
	}


	function fetchAll($tablename, $where = '', $orderby = '') {
	    $stmt = "select * FROM $tablename";
	    if ($where) {
	        $stmt .= " WHERE $where";
	    }
	    if ($orderby) {
	        $stmt .= " ORDER BY $orderby";
	    }
	    $prep = $this->prepare($stmt);
	    $prep->execute();
	    $rows = $prep->fetchAll(PDO::FETCH_ASSOC);
	    return $rows;
	}


	function insert($tablename, $data) {
		$columns = '(';
		$values = '(';
		$firstTime = true;
		foreach ($data as $k => $v) {
			if ($firstTime) {
				$firstTime = false;
			} else {
				$columns .= ', ';
				$values .= ', ';
			}
			$columns .= $k;
			$values .= $v;
		}
		$columns .= ')';
		$values .= ')';
	
		$stmt = "insert INTO $tablename $columns VALUES $values";
		
		$prep = $this->prepare($stmt);
	    return $prep->execute();
	}


	function createTable($tablename, $items, $debug = false) {
	    // init the statement
	    $stmt = "CREATE TABLE $tablename (id INT NOT NULL AUTO_INCREMENT, ";

        // compose the list of columns, all text type
        foreach ($items as $key => $item) {
            $stmt .= "$key text,";
        }
        //$stmt = rtrim($stmt, ',');
        $stmt .= 'PRIMARY KEY (id)';
        $stmt .= ')';

        if ($debug) {
            return $stmt;
        }
        else {
            // execute it
            $prep = $this->prepare($stmt);
            $result = $this->execute();
            return $result;
        }
	}


	function dropTable($tablename) {
	    print("<p>Dropping table '$tablename'...</p>");
	    $prep = $this->prepare("DROP TABLE $tablename");
	    $result = $this->execute();
	    return $result;
	}
}
