<?php

class xs_products_database
{
        private $conn = NULL;
        
        function __construct()
        {
                $this->init_db();
        }

        function init_db()
        {
                if(isset($this->conn))
                        return;
                
                $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

                if (mysqli_connect_error()) {
                        die("Connection to database failed: " . mysqli_connect_error());
                }
                if(is_resource($this->conn)) 
                { 
                        $this->conn->query($this->conn, "SET NAMES 'utf8'"); 
                        $this->conn->query($this->conn, "SET CHARACTER SET 'utf8'"); 
                } 
                $result = $this->conn->query("SELECT 1 FROM `xs_products` LIMIT 1");
                if($result === FALSE)
                        $this->conn->query("CREATE TABLE xs_products ( `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, `name` VARCHAR(64) NOT NULL, `lang` VARCHAR(16) NOT NULL, title VARCHAR(64) NOT NULL, `img` VARCHAR(256), `descr` VARCHAR(1024));");
        }
        
        function execute_query($sql_query)
        {
                $offset = $this->conn->query($sql_query);
                if (!$offset) {
                        echo "Could not run query: SQL_ERROR -> " . $this->conn->error . " SQL_QUERY -> " . $sql_query;
                        exit;
                }
                return $offset;
        }
        
        function fields_get()
        {
                $offset = array();
                $result = $this->execute_query("SHOW COLUMNS FROM xs_products");
                if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                                $offset[] = $row;
                        }
                }
                return $offset;
        }
        
        function fields_get_skip($array)
        {
                $offset = array();
                $result = $this->execute_query("SHOW COLUMNS FROM xs_products");
                if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                                if(!in_array($row['Field'], $array))
                                        $offset[] = $row;
                        }
                }
                return $offset;
        }
        
        function fields_count()
        {
                $result = $this->execute_query("SHOW COLUMNS FROM xs_products");
                return $result->num_rows;
        }
        
        function field_add($name, $type)
        {
                $sql_query = 'ALTER TABLE xs_products ADD `' . $name . '` '. $type;
                $this->execute_query($sql_query);
        }
        
        function field_remove($name)
        {
                $sql_query = 'ALTER TABLE xs_products DROP `' . $name . '`';
                $this->execute_query($sql_query);
        }
        
        function products_get()
        {
                $offset = array();
                $result = $this->execute_query("SELECT * FROM xs_products");
                if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                                $offset[] = $row;
                        }
                }
                return $offset;
        }
        
        function products_get_lang($lang)
        {
                $offset = array();
                $result = $this->execute_query('SELECT * FROM xs_products WHERE lang="' . $lang . '"');
                if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                                $offset[] = $row;
                        }
                }
                return $offset;
        }
        
        function products_count()
        {
                $result = $this->execute_query("SELECT count(id) FROM xs_products");
                $row = $result->fetch_assoc();
                return $row['count(id)'];
        }
        
        
        function products_update($input)
        {
                $size_products = $this->products_count();
                
                $fields = $this->fields_get();
                $size_fields = count($fields);
                
                $sql_update = 'UPDATE xs_products SET ';
                for($i = 0; $i < $size_products; $i++) {
                        for($k = 0; $k < $size_fields; $k++) {
                                $current_field = $fields[$k]['Field'];
                                $sql_update .= '`' . $current_field . '` = "'. sanitize_text_field($input[$i][$current_field]) . '"';
                                if($k < $size_fields - 1) {
                                        $sql_update .= ', ';
                                } else {
                                        $sql_update .= ' WHERE id = "' . $input[$i]['id'] . '";';
                                        $this->execute_query($sql_update);
                                        $sql_update = 'UPDATE xs_products SET '; 
                                }
                                
                        }
                        
                }
        }
        
        function products_add($input)
        {
                $fields = $this->fields_get_skip(array('id'));
                $size_fields = count($fields);
                
                $size_fields = $size_fields - 1;
                
                
                $sql_insert = 'INSERT INTO xs_products (';
                for($i = 0; $i < $size_fields; $i++) {
                        $current_field = $fields[$i]['Field'];
                        $sql_insert .= '`' . $current_field . '`';
                        if($i < $size_fields - 1)
                                $sql_insert .= ', ';
                        else
                                $sql_insert .= ' ) VALUES ( ';
                                
                }
                for($i = 0; $i < $size_fields; $i++) {
                        $current_field = $fields[$i]['Field'];
                        $sql_insert .= '"' . $input[$current_field] . '"';
                        if($i < $size_fields - 1)
                                $sql_insert .= ', ';
                        else
                                $sql_insert .= ' )';
                }
                $this->execute_query($sql_insert);
        }
        
        function products_remove($input)
        {
                $this->execute_query('DELETE FROM xs_products WHERE `id`= "'. $input . '"');
        }
        

}

?>
