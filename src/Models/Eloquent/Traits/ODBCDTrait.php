<?php namespace Sreynoldsjr\ReynoldsDbf\Models\Eloquent\Traits;
//Instructions:
//be sure to add "uui" to $appends array

class ODBC {

  function __construct($dsn){

    $this->dsn = $dsn;
    $this->connect = false;
    $this->sql = false;

    $this->connect();
  }

  function connect(){
    $this->connect = odbc_connect($this->dsn,'','');
    if (!$this->connect)
      {exit("Connection Failed: " . $this->conn);}
    return $this;
  }

  function sql($sql){
    //$sql="UPDATE vendor SET KEY='X' WHERE KEY='0082000000020'";
    $this->sql = $sql;
    return $this;
  }

  function echo(){
    $rs=@odbc_exec($this->connect,$this->sql);

    echo "<table><tr>";
    echo "<th>Companyname</th>";
    echo "<th>Contactname</th></tr>";
    while (odbc_fetch_row($rs))
    {
    $compname=odbc_result($rs,"KEY");
    $conname=odbc_result($rs,"ORGNAME");
    echo "<tr><td>$compname</td>";
    echo "<td>$conname</td></tr>";
    }
    $this->close();
    echo "</table>";
  }

  function close(){
    odbc_close($this->connect);
    return $this;
  }
}

trait ODBCDTrait {

  public function odbc($sql)
    {
    $odbc = new ODBC('WEBDBFs');
    $odbc->sql($sql);
    return $odbc;
    }
		
}
