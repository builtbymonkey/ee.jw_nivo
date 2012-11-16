<?php

/**
 * Converts PHP variable or array into a "JSON" (JavaScript value expression
 * or "object notation") string.
 *
 * @compat
 *    Output seems identical to PECL versions. "Only" 20x slower than PECL version.
 * @bugs
 *    Doesn't take care with unicode too much - leaves UTF-8 sequences alone.
 *
 * @param  $var mixed  PHP variable/array/object
 * @return string      transformed into JSON equivalent
 */
if (!defined("JSON_HEX_TAG")) {
   define("JSON_HEX_TAG", 1);
   define("JSON_HEX_AMP", 2);
   define("JSON_HEX_APOS", 4);
   define("JSON_HEX_QUOT", 8);
   define("JSON_FORCE_OBJECT", 16);
 }
if (!defined("JSON_NUMERIC_CHECK")) {
   define("JSON_NUMERIC_CHECK", 32);      // 5.3.3
 }
if (!defined("JSON_UNESCAPED_SLASHES")) {
   define("JSON_UNESCAPED_SLASHES", 64);  // 5.4.0
   define("JSON_PRETTY_PRINT", 128);      // 5.4.0
   define("JSON_UNESCAPED_UNICODE", 256); // 5.4.0
 }
if (!function_exists("json_encode")) {
   function json_encode($var, $options=0, $_indent="") {

      #-- prepare JSON string
      $obj = ($options & JSON_FORCE_OBJECT);
      list($_space, $_tab, $_nl) = ($options & JSON_PRETTY_PRINT) ? array(" ", "    $_indent", "\n") : array("", "", "");
      $json = "$_indent";

      if ($options & JSON_NUMERIC_CHECK and is_string($var) and is_numeric($var)) {
          $var = (strpos($var, ".") || strpos($var, "e")) ? floatval($var) : intval($var);
      }

      #-- add array entries
      if (is_array($var) || ($obj=is_object($var))) {

         #-- check if array is associative
         if (!$obj) {
            $keys = array_keys((array)$var);
            $obj = !($keys == array_keys($keys));   // keys must be in 0,1,2,3, ordering, but PHP treats integers==strings otherwise
         }

         #-- concat individual entries
         $empty = 0; $json = "";
         foreach ((array)$var as $i=>$v) {
            $json .= ($empty++ ? ",$_nl" : "")    // comma separators
                   . $_tab . ($obj ? (json_encode($i, $options, $_tab) . ":$_space") : "")   // assoc prefix
                   . (json_encode($v, $options, $_tab));    // value
         }

         #-- enclose into braces or brackets
         $json = $obj ? "{"."$_nl$json$_nl$_indent}" : "[$_nl$json$_nl$_indent]";
      }

      #-- strings need some care
      elseif (is_string($var)) {

         if (!utf8_decode($var)) {
            trigger_error("json_encode: invalid UTF-8 encoding in string, cannot proceed.", E_USER_WARNING);
            $var = NULL;
         }
         $rewrite = array(
             "\\" => "\\\\",
             "\"" => "\\\"",
           "\010" => "\\b",
             "\f" => "\\f",
             "\n" => "\\n",
             "\r" => "\\r",
             "\t" => "\\t",
             "/"  => $options & JSON_UNESCAPED_SLASHES ? "/" : "\\/",
             "<"  => $options & JSON_HEX_TAG  ? "\\u003C" : "<",
             ">"  => $options & JSON_HEX_TAG  ? "\\u003E" : ">",
             "'"  => $options & JSON_HEX_APOS ? "\\u0027" : "'",
             "\"" => $options & JSON_HEX_QUOT ? "\\u0022" : "\"",
             "&"  => $options & JSON_HEX_AMP  ? "\\u0026" : "&",
         );
         $var = strtr($var, $rewrite);
         //@COMPAT control chars should probably be stripped beforehand, not escaped as here
         if (function_exists("iconv") && ($options & JSON_UNESCAPED_UNICODE) == 0) {
            $var = preg_replace("/[^\\x{0020}-\\x{007F}]/ue", "'\\u'.current(unpack('H*', iconv('UTF-8', 'UCS-2BE', '$0')))", $var);
         }
         $json = '"' . $var . '"';
      }

      #-- basic types
      elseif (is_bool($var)) {
         $json = $var ? "true" : "false";
      }
      elseif ($var === NULL) {
         $json = "null";
      }
      elseif (is_int($var) || is_float($var)) {
         $json = "$var";
      }

      #-- something went wrong
      else {
         trigger_error("json_encode: don't know what a '" .gettype($var). "' is.", E_USER_WARNING);
      }

      #-- done
      return($json);
   }
}


/**
 * Parses a JSON (JavaScript value expression) string into a PHP variable
 * (array or object).
 *
 * @compat
 *    Behaves similar to PECL version, but is less quiet on errors.
 *    Now even decodes unicode \uXXXX string escapes into UTF-8.
 *    "Only" 27 times slower than native function.
 * @bugs
 *    Might parse some misformed representations, when other implementations
 *    would scream error or explode.
 * @code
 *    This is state machine spaghetti code. Needs the extranous parameters to
 *    process subarrays, etc. When it recursively calls itself, $n is the
 *    current position, and $waitfor a string with possible end-tokens.
 *
 * @param   $json string   JSON encoded values
 * @param   $assoc bool    pack data into php array/hashes instead of objects
 * @return  mixed          parsed into PHP variable/array/object
 */
if (!function_exists("json_decode")) {

   define("JSON_OBJECT_AS_ARRAY", 1);     // undocumented
   define("JSON_BIGINT_AS_STRING", 2);    // 5.4.0
   define("JSON_PARSE_JAVASCRIPT", 4);    // unquoted object keys, and single quotes ' strings identical to double quoted, more relaxed parsing

   function json_decode($json, $assoc=FALSE, $limit=512, $options=0, /*emu_args*/$n=0,$state=0,$waitfor=0) {
      global ${'.json_last_error'}; ${'.json_last_error'} = JSON_ERROR_NONE;

      #-- result var
      $val = NULL;
      $FAILURE = array(/*$val:=*/ NULL, /*$n:=*/ 1<<31);
      static $lang_eq = array("true" => TRUE, "false" => FALSE, "null" => NULL);
      static $str_eq = array("n"=>"\012", "r"=>"\015", "\\"=>"\\", '"'=>'"', "f"=>"\f", "b"=>"\010", "t"=>"\t", "/"=>"/");
      if ($limit<0) { ${'.json_last_error'} = JSON_ERROR_DEPTH; return /* __cannot_compensate */; }

      #-- strip UTF-8 BOM (the native version doesn't do this, but .. should)
      while (strncmp($json, "\xEF\xBB\xBF", 3) == 0) {
          trigger_error("UTF-8 BOM prefaces JSON, that's invalid for PHPs native json_decode", E_USER_ERROR);
          $json = substr($json, 3);
      }

      #-- flat char-wise parsing
      for (/*$n=0,*/ $len = strlen($json); $n<$len; /*$n++*/) {
         $c = $json[$n];

         #-= in-string
         if ($state==='"' or $state==="'") {

            if ($c == '\\') {
               $c = $json[++$n];

               // simple C escapes
               if (isset($str_eq[$c])) {
                  $val .= $str_eq[$c];
               }

               // here we transform \uXXXX Unicode (always 4 nibbles) references to UTF-8
               elseif ($c == "u") {
                  // read just 16bit (therefore value can't be negative)
                  $hex = hexdec( substr($json, $n+1, 4) );
                  $n += 4;
                  // Unicode ranges
                  if ($hex < 0x80) {    // plain ASCII character
                     $val .= chr($hex);
                  }
                  elseif ($hex < 0x800) {   // 110xxxxx 10xxxxxx
                     $val .= chr(0xC0 + $hex>>6) . chr(0x80 + $hex&63);
                  }
                  elseif ($hex <= 0xFFFF) { // 1110xxxx 10xxxxxx 10xxxxxx
                     $val .= chr(0xE0 + $hex>>12) . chr(0x80 + ($hex>>6)&63) . chr(0x80 + $hex&63);
                  }
                  // other ranges, like 0x1FFFFF=0xF0, 0x3FFFFFF=0xF8 and 0x7FFFFFFF=0xFC do not apply
               }

               // for JS (not JSON) the extraneous backslash just gets omitted
               elseif ($options & JSON_PARSE_JAVASCRIPT) {
                  if (is_numeric($c) and preg_match("/[0-3][0-7][0-7]|[0-7]{1,2}/", substr($json, $n), $m)) {
                     $val .= chr(octdec($m[0]));
                     $n += strlen($m[0]) - 1;
                  }
                  else {
                     $val .= $c;
                  }
               }

               // redundant backslashes disallowed in JSON
               else {
                  $val .= "\\$c";
                  ${'.json_last_error'} = JSON_ERROR_CTRL_CHAR; // not quite, but
                  trigger_error("Invalid backslash escape for JSON \\$c", E_USER_WARNING);
                  return $FAILURE;
               }
            }

            // end of string
            elseif ($c == $state) {
               $state = 0;
            }

            //@COMPAT: specialchars check - but native json doesn't do it?
            #elseif (ord($c) < 32) && !in_array($c, $str_eq)) {
            #   ${'.json_last_error'} = JSON_ERROR_CTRL_CHAR;
            #}

            // a single character was found
            else/*if (ord($c) >= 32)*/ {
               $val .= $c;
            }
         }

         #-> end of sub-call (array/object)
         elseif ($waitfor && (strpos($waitfor, $c) !== false)) {
            return array($val, $n);  // return current value and state
         }

         #-= in-array
         elseif ($state===']') {
            list($v, $n) = json_decode($json, $assoc, $limit, $options, $n, 0, ",]");
            $val[] = $v;
            if ($json[$n] == "]") { return array($val, $n); }
         }

         #-= in-object
         elseif ($state==='}') {
            // quick regex parsing cheat for unquoted JS object keys
            if ($options & JSON_PARSE_JAVASCRIPT and $c != '"' and preg_match("/^\s*(?!\d)(\w\pL*)\s*/u", substr($json, $n), $m)) {
                $i = $m[1];
                $n = $n + strlen($m[0]);
            }
            else {
                // this allowed non-string indicies
                list($i, $n) = json_decode($json, $assoc, $limit, $options, $n, 0, ":");
            }
            list($v, $n) = json_decode($json, $assoc, $limit, $options, $n+1, 0, ",}");
            $val[$i] = $v;
            if ($json[$n] == "}") { return array($val, $n); }
         }

         #-- looking for next item (0)
         else {

            #-> whitespace
            if (preg_match("/\s/", $c)) {
               // skip
            }

            #-> string begin
            elseif ($c == '"') {
               $state = $c;
            }

            #-> object
            elseif ($c == "{") {
               list($val, $n) = json_decode($json, $assoc, $limit-1, $options, $n+1, '}', "}");

               if ($val && $n) {
                  $val = $assoc ? (array)$val : (object)$val;
               }
            }

            #-> array
            elseif ($c == "[") {
               list($val, $n) = json_decode($json, $assoc, $limit-1, $options, $n+1, ']', "]");
            }

            #-> numbers
            elseif (preg_match("#^(-?\d+(?:\.\d+)?)(?:[eE]([-+]?\d+))?#", substr($json, $n), $uu)) {
               $val = $uu[1];
               $n += strlen($uu[0]) - 1;
               if (strpos($val, ".")) {  // float
                  $val = floatval($val);
               }
               elseif ($val[0] == "0") {  // oct
                  $val = octdec($val);
               }
               else {
                  $toobig = strval(intval($val)) !== strval($val);
                  if ($toobig and !isset($uu[2]) and ($options & JSON_BIGINT_AS_STRING)) {
                      $val = $val;  // keep lengthy numbers as string
                  }
                  elseif ($toobig or isset($uu[2])) {  // must become float anyway
                      $val = floatval($val);
                  }
                  else {  // int
                      $val = intval($val);
                  }
               }
               // exponent?
               if (isset($uu[2])) {
                  $val *= pow(10, (int)$uu[2]);
               }
            }

            #-> boolean or null
            elseif (preg_match("#^(true|false|null)\b#", substr($json, $n), $uu)) {
               $val = $lang_eq[$uu[1]];
               $n += strlen($uu[1]) - 1;
            }

            #-> JS-string begin
            elseif ($options & JSON_PARSE_JAVASCRIPT and $c == "'") {
               $state = $c;
            }

            #-> comment
            elseif ($options & JSON_PARSE_JAVASCRIPT and ($c == "/") and ($json[$n+1]=="*")) {
               // just find end, skip over
               ($n = strpos($json, "*/", $n+1)) or ($n = strlen($json));
            }

            #-- parsing error
            else {
               // PHPs native json_decode() breaks here usually and QUIETLY
               trigger_error("json_decode: error parsing '$c' at position $n", E_USER_WARNING);
               ${'.json_last_error'} = JSON_ERROR_SYNTAX;
               return $waitfor ? $FAILURE : NULL;
            }

         }//state

         #-- next char
         if ($n === NULL) { ${'.json_last_error'} = JSON_ERROR_STATE_MISMATCH; return NULL; }   // ooops, seems we have two failure modes
         $n++;
      }//for

      #-- final result
      return ($val);
   }
}
