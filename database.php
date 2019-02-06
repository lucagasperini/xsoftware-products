<?php

if(!defined("ABSPATH")) die;

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
                if(is_resource($this->conn)) { 
                        $this->conn->query($this->conn, "SET NAMES 'utf8'"); 
                        $this->conn->query($this->conn, "SET CHARACTER SET 'utf8'"); 
                } 
                $result = $this->conn->query("SELECT 1 FROM `xs_products` LIMIT 1");
                if($result === FALSE)
                        $this->conn->query("CREATE TABLE xs_products ( 
                        `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, 
                        `name` VARCHAR(64) NOT NULL, 
                        `lang` VARCHAR(16) NOT NULL, 
                        `title` VARCHAR(64) NOT NULL, 
                        `img` VARCHAR(256), 
                        `descr` VARCHAR(1024)
                        );");
        }
        
        function type2char($type)
        {
                if(strstr($type, 'char') !== FALSE || strstr($type, 'text') !== FALSE)
                        return 's';
                if(strstr($type, 'double') !== FALSE || strstr($type, 'float') !== FALSE || strstr($type, 'real') !== FALSE)
                        return 'd';
                if(strstr($type, 'int') !== FALSE)
                        return 'i';
                if(strstr($type, 'blob') !== FALSE)
                        return 'b';
                
                return FALSE;
        }
        
        
        /* RESOURCE: https://www.pontikis.net/blog/dynamically-bind_param-array-mysqli */ 
        function multiple_bind($sql, $a_param_type, $a_bind_params)
        {
                /* Bind parameters. Types: s = string, i = integer, d = double,  b = blob */
                $a_params = array();
                
                $param_type = '';
                $n = count($a_param_type);
                for($i = 0; $i < $n; $i++) {
                $param_type .= $a_param_type[$i];
                }
                
                /* with call_user_func_array, array params must be passed by reference */
                $a_params[] = & $param_type;
                
                for($i = 0; $i < $n; $i++) {
                /* with call_user_func_array, array params must be passed by reference */
                $a_params[] = & $a_bind_params[$i];
                }
                
                /* Prepare statement */
                $stmt = $this->conn->prepare($sql);
                if($stmt === false) {
                trigger_error('Wrong SQL: ' . $sql . ' Error: ' . $this->conn->errno . ' ' . $this->conn->error, E_USER_ERROR);
                }
                
                /* use call_user_func_array, as $stmt->bind_param('s', $param); does not accept params array */
                call_user_func_array(array($stmt, 'bind_param'), $a_params);
                
                /* Execute statement */
                $stmt->execute();
                
                /* Fetch result to array */
                $res = $stmt->get_result();
                if($res === FALSE) //if there aren't result
                        return TRUE;
                
                while($row = $res->fetch_array(MYSQLI_ASSOC)) {
                array_push($a_data, $row);
                }
                
                return $a_data;
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
        
        function prepared_query($query)
        {
                $offset = array();
                if(!$query->execute()) {
                        echo "Could not run query: SQL_ERROR -> " . $query->error . " SQL_QUERY -> " . $string;
                        exit;
                }
                $meta = $query->result_metadata(); 
                while ($field = $meta->fetch_field()) 
                { 
                        $params[] = &$row[$field->name]; 
                } 

                call_user_func_array(array($query, 'bind_result'), $params); 

                while ($query->fetch()) { 
                        foreach($row as $key => $val) 
                        { 
                                $c[$key] = $val; 
                        } 
                        $offset[] = $c; 
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
        
        function fields_get_name()
        {
                $offset = array();
                $result = $this->execute_query("SHOW COLUMNS FROM xs_products");
                if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                                $offset[] = $row['Field'];
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
        
        function fields_get_name_skip($array)
        {
                $offset = array();
                $result = $this->execute_query("SHOW COLUMNS FROM xs_products");
                if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                                if(!in_array($row['Field'], $array))
                                        $offset[] = $row['Field'];
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
        
        function products_get($lang = NULL, $id = NULL)
        {
                $offset = array();
                $string = NULL;

                if($lang == NULL && $id == NULL)
                        $string = "SELECT * FROM xs_products";
                if($lang == NULL && $id != NULL)
                        $string = "SELECT * FROM xs_products WHERE id=\"".$id."\"";
                if($lang != NULL && $id == NULL)
                        $string = "SELECT * FROM xs_products WHERE lang=\"" . $lang . "\"";
                if($lang != NULL && $id != NULL)
                        $string = "SELECT * FROM xs_products WHERE id=\"".$id."\" AND lang=\"" . $lang . "\"";
                        
                        
                $result = $this->execute_query($string);
                if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                                $offset[] = $row;
                        }
                }
                return $offset;
        }
        
        function products_get_by_name($name, $lang = NULL)
        {
                if($lang != NULL)
                        $string = "SELECT * FROM xs_products WHERE lang=\"" . $lang . "\" AND name=?";
                else
                        $string = "SELECT * FROM xs_products WHERE name=?";

                $query = $this->conn->prepare($string);
                $query->bind_param("s", $name);
                $offset = $this->prepared_query($query);
                return $offset;
        }
        function products_get_id($name, $lang = NULL)
        {
                if($lang != NULL)
                        $string = "SELECT id FROM xs_products WHERE lang=\"" . $lang . "\" AND name=?";
                else
                        $string = "SELECT id FROM xs_products WHERE name=?";

                $query = $this->conn->prepare($string);
                $query->bind_param("s", $name);
                $offset = $this->prepared_query($query);
                if(isset($offset[0]))
                        return $offset[0]["id"];
                else
                        return -1;
        }
        function products_exists($name, $lang = NULL)
        {
                if($lang != NULL)
                        $string = "SELECT COUNT(id) FROM xs_products WHERE lang=\"" . $lang . "\" AND name=?";
                else
                        $string = "SELECT COUNT(id) FROM xs_products WHERE name=?";

                $query = $this->conn->prepare($string);
                $query->bind_param("s", $name);
                $offset = $this->prepared_query($query);
                return $offset[0]["COUNT(id)"] == 1;
        }
        
        function products_count()
        {
                $result = $this->execute_query("SELECT count(id) FROM xs_products");
                $row = $result->fetch_assoc();
                return $row['count(id)'];
        }
        
        
        function products_update($input)
        {
                $size_products = count($input);
                $fields = $this->fields_get();
                
                for($i = 0; $i < $size_products; $i++) {
                        $this->products_update_single($input[$i], $fields, $input[$i]['id']);
                }
        }
        
        function products_update_single($single, $fields, $id)
        {
                $found = $this->products_get_id($single["name"], $single["lang"]); //find id this product from database
                if( $id != $found  && $found > 0) { //if id is the same of product to override or is not found
                        return FALSE;
                }
                        
                $size_fields = count($fields);
                $last_field = $fields[$size_fields - 1]['Field'];
                
                $sql_update = 'UPDATE xs_products SET ';
                
                foreach($fields as $current_field) {
                        $sql_update .= '`' . $current_field['Field'] . '`=?';
                        if($current_field['Field'] != $last_field) //if is not last element
                                $sql_update .= ', '; //insert a comma
                        else
                                $sql_update .= ' WHERE `id` = "' . $id . '";'; //add where clause
                }
                
                foreach($fields as $current_field) {
                        $type_array[] = $this->type2char($current_field['Type']);
                        $value_array[] = $single[$current_field['Field']];
                }
                
                $return = $this->multiple_bind($sql_update, $type_array, $value_array);
                if($return === TRUE)
                        return TRUE;
        }
        
        function products_add($input)
        {
                if( $this->products_exists($input["name"], $input["lang"]))
                        return FALSE;
                        
                $fields = $this->fields_get_skip(array('id'));
                
                $size_fields = count($fields);
                $last_field = $fields[$size_fields - 1]['Field'];
                
                $sql_insert = 'INSERT INTO xs_products (';
                foreach($fields as $current_field) {
                        $sql_insert .= '`' . $current_field['Field'] . '`';
                        if($current_field['Field'] != $last_field)
                                $sql_insert .= ', ';
                        else
                                $sql_insert .= ' ) VALUES ( ';
                                
                }
                
                foreach($fields as $current_field) {
                        $sql_insert .= '?';
                        if($current_field['Field'] != $last_field)
                                $sql_insert .= ', ';
                        else
                                $sql_insert .= ' )';
                }
                
                foreach($fields as $current_field) {
                        $type_array[] = $this->type2char($current_field['Type']);
                        $value_array[] = $input[$current_field['Field']];
                }
                
                $return = $this->multiple_bind($sql_insert, $type_array, $value_array);
                if($return === TRUE)
                        return TRUE;
        }
        
        function products_remove($input)
        {
                $this->execute_query('DELETE FROM xs_products WHERE `id`= "'. $input . '"');
        }
        

}

?>
