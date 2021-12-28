<?php

include 'config.php';

Class DB{
    
    /**
     * 
     * The link that is established between the database and the user.
     * 
     */
    private $link;
    
    public $sql_command;
    
    /**
     * 
     * Creates a connection between the user and the database.
     * 
     */
     function __construct()
     {
         try {
             $this->link = new PDO(SERVER_NAME, USERNAME, PASSWORD);
             $this->link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
         }
         catch(PDOException $e)
         {
             echo "Connection failed: " . $e->getMessage();
         }
    }
    
    /**
     * 
     * Magic method that is called any time when unrecognized function are called (mainly query command).
     * 
     * @param string $name = the name of the method that was invoked.
     * @param array $arguments = the arguments that are passed together with the method.
     * @return DB = the db class with the query command in it.          
     * 
     */
    public function __call($name,$arguments){
        
        // Arranges the name and the arguments that were passed in order to be passed as a query command.
        $args = implode(",",$arguments);
        $name = str_replace("_"," ",$name);
        $result = $name." ".$args;
        
        // The query command is saved in the db entity.
        $this->sql_command = $this->sql_command.$result." ";
        return $this;
    }
    
    /**
     * 
     * @return the query command to be executed on the database.
     * 
     */
       public function execute(){
         $result = $this->link->query($this->sql_command);
         $this->sql_command = null;
         return $result;
     }

     /**
      * Create a table with the given table name and columns.
      * @param $name = the name of the new table to create.
      * @param $columns = the columns that will populate the table.
      */
     public function createTable($name, $columns) {
         // Create a new table only if there are no tables with the same name.
        $this->sql_command = "CREATE TABLE IF NOT EXISTS ". $name . " (
            id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,";
        // Add the columns to the query.
        foreach($columns as $column){
            $this->sql_command .= $column . " VARCHAR(40),";
        }
        // Change the last element of the string (which is currently ',') to ')' to indicate end of query.
        $this->sql_command[-1] = ")";
        // Execute the query.
        $this->execute();
     }
}

?>