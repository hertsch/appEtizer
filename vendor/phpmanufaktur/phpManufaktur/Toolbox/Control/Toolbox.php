<?php

/**
 * extendedWYSIWYG
 *
 * @author Team phpManufaktur <team@phpmanufaktur.de>
 * @link https://addons.phpmanufaktur.de/extendedWYSIWYG
 * @copyright 2012 Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

namespace phpManufaktur\Toolbox\Control;

use phpManufaktur\CMS\Bridge\Control\boneClass;

class Toolbox extends boneClass {

  /**
   * Sanitize variables and prepare them for saving in a MySQL record
   *
   * @param mixed $item
   * @return mixed
   */
  public static function sanitizeVariable($item) {
    if (!is_array($item)) {
      // undoing 'magic_quotes_gpc = On' directive
      if (get_magic_quotes_gpc())
        $item = stripcslashes($item);
      $item = self::sanitizeText($item);
    }
    return $item;
  } // sanitizeVariable()

  /**
   * Sanitize a text variable and prepare ist for saving in a MySQL record
   *
   * @param string $text
   * @return string
   */
  public static function sanitizeText($text) {
    $text = str_replace(array("<",">","\"","'"), array("&lt;","&gt;","&quot;","&#039;"), $text);
    $text = mysql_real_escape_string($text);
    return $text;
  } // sanitizeText()

  /**
   * Unsanitize a text variable and prepare it for output
   *
   * @param string $text
   * @return string
   */
  public static function unsanitizeText($text) {
    $text = stripcslashes($text);
    $text = str_replace(array("&lt;","&gt;","&quot;","&#039;"), array("<",">","\"","'"), $text);
    return $text;
  } // unsanitizeText()

  /**
   * Count words as proposed from stano110@azet.sk at
   * http://de2.php.net/manual/de/function.str-word-count.php
   * and works well with UTF-8
   *
   * @param string $string
   * @return number
   */
  public static function countWords($string)  {
    $string = htmlspecialchars_decode(strip_tags($string));
    if (strlen($string)==0)
      return 0;
    // separators
    $t = array(' '=>1, '_'=>1, "\x20"=>1, "\xA0"=>1, "\x0A"=>1, "\x0D"=>1, "\x09"=>1,
        "\x0B"=>1, "\x2E"=>1, "\t"=>1, '='=>1, '+'=>1, '-'=>1, '*'=>1, '/'=>1, '\\'=>1,
        ','=>1, '.'=>1, ';'=>1, ':'=>1, '"'=>1, '\''=>1, '['=>1, ']'=>1, '{'=>1, '}'=>1,
        '('=>1, ')'=>1, '<'=>1, '>'=>1, '&'=>1, '%'=>1, '$'=>1, '@'=>1, '#'=>1, '^'=>1,
        '!'=>1, '?'=>1);
    $count= isset($t[$string[0]]) ? 0:1;
    if (strlen($string) == 1)
      return $count;
    for ($i=1; $i<strlen($string); $i++)
      // if a new word start count
    if (isset($t[$string[$i-1]]) && !isset($t[$string[$i]])) $count++;
    return $count;
  } // countWords()

  /**
   * Generate a random password of $length
   *
   * @param integer $length
   * @return string password
   */
  public static function generatePassword($length=12) {
    $password = '';
    $salt = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz123456789';
    srand((double) microtime() * 1000000);
    for ($i=0; $i < $length; $i++) {
      $num = rand() % 33;
      $tmp = substr($salt, $num, 1);
      $password .= $tmp;
    }
    return $password;
  } // generatePassword()

  /**
   * Check if the desired $path exists and try to create it also with nested
   * subdirectories if $create is true
   *
   * @param string $path
   * @param boolean $create try to create the directory
   * @return boolean
   */
  public function checkDirectory($path, $create=true) {
    global $I18n;

    if (!file_exists($path)) {
      if ($create) {
        if (!mkdir($path, 0755, true)) {
          $this->setError($I18n->translate("Can't create the directory <b>{{ directory }}</b>!",
              array('directory', $path)), __METHOD__, __LINE__);
          return false;
        }
        $this->setInfo($I18n->translate('Created directory {{ directory }}.'), __METHOD__, __LINE__);
      }
      else {
        $this->setError($I18n->translate('The directory {{ directory }} does not exists!',
            array('directory' => $path)), __METHOD__, __LINE__);
        return false;
      }
    }
    return true;
  } // checkPath()

  /**
   * Delete a directory recursivly
   *
   * @param string $directory_path
   */
  public function deleteDirectory($directory_path) {
    global $I18n;

    if (is_dir($directory_path)) {
      $items = scandir($directory_path);
      foreach ($items as $item) {
        if (($item != '.') && ($item != '..')) {
          if (filetype($directory_path.'/'.$item) == 'dir')
            $this->deleteDirectory($directory_path.'/'.$item);
          elseif (!unlink($directory_path.'/'.$item)) {
            $this->setError($I18n->translate("Can't delete the file {{ file }}.",
                array('file' => $directory_path.'/'.$item)), __METHOD__, __LINE__);
            return false;
          }
        }
      }
      reset($items);
      if (!rmdir($directory_path)) {
        $this->setError($I18n->translate("Can't delete the directory {{ directory }}.",
            array('directory' => $directory_path)), __METHOD__, __LINE__);
        return false;
      }
    }
    return true;
  } // deleteDirectory()

  /**
   * Converts human readable file size (e.g. 10 MB, 200.20 GB) into bytes.
   *
   * @param string $str
   * @return int the result is in bytes
   * @author Svetoslav Marinov
   * @author http://slavi.biz
   */
  function filesize2bytes($str) {
    $bytes = 0;
    $bytes_array = array(
        'B' => 1,
        'KB' => 1024,
        'MB' => 1024 * 1024,
        'GB' => 1024 * 1024 * 1024,
        'TB' => 1024 * 1024 * 1024 * 1024,
        'PB' => 1024 * 1024 * 1024 * 1024 * 1024,
    );
    $bytes = floatval($str);
    if (preg_match('#([KMGTP]?B)$#si', $str, $matches) && !empty($bytes_array[$matches[1]])) {
      $bytes *= $bytes_array[$matches[1]];
    }
    $bytes = intval(round($bytes, 2));
    return $bytes;
  } // filesize2bytes()

  /**
   * fixes a path by removing //, /../ and other things
   *
   * @param  string  $path - path to fix
   * @return string
   * @author Bianka Martinovic <blackbird@webbird.de>
   */
  public function sanitizePath($path) {
    // remove / at end of string; this will make sanitizePath fail otherwise!
    $path = preg_replace('~/{1,}$~', '', $path);

    // make all slashes forward
    $path = str_replace('\\', '/', $path);

    // bla/./bloo ==> bla/bloo
    $path = preg_replace('~/\./~', '/', $path);

    // resolve /../
    // loop through all the parts, popping whenever there's a .., pushing otherwise.
    $parts  = array();
    foreach (explode('/', preg_replace('~/+~', '/', $path)) as $part) {
      if ($part === ".." || $part == '')
        array_pop($parts);
      elseif ($part!="")
        $parts[] = $part;
    }
    $new_path = implode("/", $parts);
    // windows
    if (!preg_match('/^[a-z]\:/i', $new_path))
      $new_path = '/' . $new_path;

    return $new_path;
  } // sanitizePath()

  /**
   * Iterate directory tree very efficient
   * Function postet from donovan.pp@gmail.com at
   * http://www.php.net/manual/de/function.scandir.php
   *
   * @param sting $dir
   * @return array - directoryTree
   */
  public static function directoryTree($dir) {
    if (substr($dir,-1) == "/")
      $dir = substr($dir,0,-1);
    $path = array();
    $stack = array();
    $stack[] = $dir;
    while ($stack) {
      $thisdir = array_pop($stack);
      if (false !== ($dircont = scandir($thisdir))) {
      		$i=0;
      		while (isset($dircont[$i])) {
      		  if ($dircont[$i] !== '.' && $dircont[$i] !== '..') {
      		    $current_file = "{$thisdir}/{$dircont[$i]}";
      		    if (is_file($current_file)) {
      		      $path[] = "{$thisdir}/{$dircont[$i]}";
      		    }
      		    elseif (is_dir($current_file)) {
      		      $stack[] = $current_file;
      		    }
      		  }
      		  $i++;
      		}
      }
    }
    return $path;
  } // directoryTree()
  
  /**
   * Return a array from a file - expect that the file return the array!
   * 
   * @param unknown $file_path
   * @return boolean
   */
  public static function returnArrayFromFile($file_path) {
  	if (is_file($file_path)) {
  		return include_once $file_path;
  	}
  	return false;
  } // returnArrayFromFile()
  
  /**
   * Try to get the language from the browser.
   * This code is taken from SelfHTML
   *
   * @author Christian Seiler <self@christian-seiler.de>
   * @link http://aktuell.de.selfhtml.org/artikel/php/httpsprache/
   *
   * @param array $allowed_languages Array with the available languages (lowercase)
   * @param string $default_language language to take if no other is available
   * @param string $lang_variable use instead of $_SERVER['HTTP_ACCEPT_LANGUAGE']
   * @param boolean $strict_mode use exactly the HTTP specification
   * @return string language code
   */
  public function getLanguageFromBrowser($allowed_languages, $default_language, $lang_variable = null, $strict_mode = true) {
  	// $_SERVER['HTTP_ACCEPT_LANGUAGE'] verwenden, wenn keine Sprachvariable mitgegeben wurde
  	if ($lang_variable === null) {
  		$lang_variable = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
  	}
  	// wurde irgendwelche Information mitgeschickt?
  	if (empty($lang_variable)) {
  		// Nein? => Standardsprache zurueckgeben
  		return $default_language;
  	}
  	// Den Header auftrennen
  	$accepted_languages = preg_split('/,\s*/', $lang_variable);
  	// Die Standardwerte einstellen
  	$current_lang = $default_language;
  	$current_q = 0;
  	// Nun alle mitgegebenen Sprachen abarbeiten
  	foreach ($accepted_languages as $accepted_language) {
  		// Alle Infos ueber diese Sprache rausholen
  		$res = preg_match ('/^([a-z]{1,8}(?:-[a-z]{1,8})*)'.
  				'(?:;\s*q=(0(?:\.[0-9]{1,3})?|1(?:\.0{1,3})?))?$/i', $accepted_language, $matches);
  		// war die Syntax gueltig?
  		if (!$res) {
  			// Nein? Dann ignorieren
  			continue;
  		}
  		// Sprachcode holen und dann sofort in die Einzelteile trennen
  		$lang_code = explode ('-', $matches[1]);
  		// Wurde eine Qualitaet mitgegeben?
  		if (isset($matches[2])) {
  			// die Qualitaet benutzen
  			$lang_quality = (float)$matches[2];
  		} else {
  			// Kompabilitaetsmodus: Qualitaet 1 annehmen
  			$lang_quality = 1.0;
  		}
  		// Bis der Sprachcode leer ist...
  		while (count ($lang_code)) {
  			// mal sehen, ob der Sprachcode angeboten wird
  			if (in_array (strtolower (join ('-', $lang_code)), $allowed_languages)) {
  				// Qualitaet anschauen
  				if ($lang_quality > $current_q) {
  					// diese Sprache verwenden
  					$current_lang = strtolower (join ('-', $lang_code));
  					$current_q = $lang_quality;
  					// Hier die innere while-Schleife verlassen
  					break;
  				}
  			}
  			// Wenn wir im strengen Modus sind, die Sprache nicht versuchen zu minimalisieren
  			if ($strict_mode) {
  				// innere While-Schleife aufbrechen
  				break;
  			}
  			// den rechtesten Teil des Sprachcodes abschneiden
  			array_pop ($lang_code);
  		}
  	}
  	// die gefundene Sprache zurueckgeben
  	return $current_lang;
  } // getLanguageFromBrowser()

} // class Toolbox