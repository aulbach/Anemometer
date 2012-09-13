<?php
/**
 * class QueryTableParser
 *
 * Very rough class to extract table names from a SQL query.
 *
 * This class simply looks for specific tokens like FROM, JOIN, UPDATE, INTO
 * and collects a list of the very next token after those words.
 *
 * It doesn't attempt to parse aliases, or any other query structure.
 *
 * This probably doesn't handle table names with a space in it like `table name`
 *
 * @author Gavin Towey <gavin@box.com>
 * @created 2012-01-01
 * @license Apache 2.0 license.  See LICENSE document for more info
 *
 * @todo handle table names with spaces wrapped in backticks or quotes
 * @todo stop parsing early if possible -- after the JOIN clause (if any)
 * @todo ignore token values inside string literals or backticks
 */
class QueryTableParser {

    public $pos;
    public $query;
    public $len;
    public $table_tokens = array(
        'from',
        'join',
        'update',
        'into',
    );

    /**
     * parse a query and return an array of table names from it.
     *
     * @param string $query     the sql query
     * @return array    the list of table names.
     */
    public function parse($query) {
        $this->query = preg_replace("/\s+/s", " ", $query);
        $this->pos = 0;
        $this->len = strlen($this->query);
        //print "<pre>";
        //print "parsing {$this->query}; length {$this->len}\n";


        $tables = array();
        while ($this->has_next_token()) {
            $token = $this->get_next_token();
            if (in_array(strtolower($token), $this->table_tokens)) {
                $table = $this->get_tbl_name();
//error_log("--> table: $table");

                if (preg_match("/\w+/", $table)) {
                    if (array_key_exists($table, $tables)) {
                        $tables[$table]++;
                    } else {
                        $tables[$table] = 1;
                    }
                }
            }
        }
        //print "</pre>";

        return array_keys($tables);
    }

    /**
     * return true if we're not at the end of the string yet.
     * @return boolean true if there are more tokens to read
     */
    private function has_next_token() {
        // at end
        if ($this->pos >= $this->len) {
            return false;
        }
        return true;
    }

    /**
     * returns the next whitespace separated string of characters
     * @return string   the token value
     */
    private function get_next_token() {
        // get the pos of the next token boundary
        $pos = strpos($this->query, " ", $this->pos);
        //print "get next token {$this->pos} {$this->len} {$pos}\n";
        if ($pos === false) {
            $pos = $this->len;
        }

        // found next boundary
        $start = $this->pos;
        $len = $pos - $start;
        $this->pos = $pos + 1;
        return substr($this->query, $start, $len);
    }

    /**
     * parses the table name from the current position
     *
     * A table-name looks like:
     * [[`]db_name[`].][`]table_name[`]
     *
     * There needs not to be a space to separate to the next token!
     *
     */
    private function get_tbl_name() {
        $regex = "/
            ^.{" . $this->pos . "} # overread pos chars
           (
               ` .+? ` \\.  ` .+ `   # match `db`.`table`
             | ` .+? ` \\.   \w+     # `db`.table
             | \w+ \\. \w+           # db.table
             | ` .+? ` (?!\\.)       # `table`
             | \w+  (?!\\.)          # table
           )
        /xs"; # with comments, '.' is multiline
        if (preg_match($regex, $this->query, $matches)) {
            error_log("MATCHES " . var_export($matches,1));
            $this->pos = $this->pos + strlen($matches[0]);
            return $matches[1];
            #return preg_replace('/^`(.+)`$/', '\\1', $matches[3]);
        }
        return false;
    }

}

?>