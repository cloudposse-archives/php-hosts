<?
/* Hosts.class.php - Class for managing /etc/hosts
 * Copyright (C) 2007 Erik Osterman <e@osterman.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/* File Authors:
 *   Erik Osterman <e@osterman.com>
 */


// Class for manipulating/resolving /etc/hosts
class Hosts extends Hash
{
  const DEFAULT_PATH = '/etc/hosts';
  
  private $file;
  private $cache;
  public function __construct( $path = self::DEFAULT_PATH )
  {
    $this->import($path);
  }

  public function __destruct()
  {
    unset($this->file);
    unset($this->cache);
    parent::__destruct();
  }

  public function import($path = self::DEFAULT_PATH)
  {
    $this->file = new FileStream($path);
    $this->cache = new Hash();
    foreach($this->file as $entry)
    {
      $entry = trim($entry, "\r\n\t ");
      $entry = preg_replace('/\#.*$/', '', $entry);
      if( preg_match('/^\s*$/', $entry) )
        continue;
      $parts = preg_split('/\s+/', $entry);
      $ip = array_shift($parts);
      foreach($parts as $name)
      {
        $this[$name] = $ip;
        $this[$ip] = $name;
      }
    }
    //print_r($this);
  }

  public function remove($key)
  {
    foreach($this as $k => $v)
      if( fnmatch($key, $k) || fnmatch($key, $v) )
      {
        parent::remove($k);
        parent::remove($v);
      }
  }

  public function export($path = self::DEFAULT_PATH)
  {
    $hosts = Array();
    foreach($this as $key => $value)
    {
      if( preg_match('/^\d+\.\d+\.\d+\.\d+$/', $key) )
      {
        $ip = $key;
        $name = $value;
      } else {
        $ip = $value;
        $name = $key;
      }

      if( array_key_exists($ip, $hosts) )
        array_push($hosts[$ip], $name);
      else
        $hosts[$ip] = Array( $name );
    }
    $file = new FileStream($path, 'w');
    $file->lock();
    foreach($hosts as $ip => $names )
    {
      $hosts[$ip] = array_unique($hosts[$ip]);
      sort($hosts[$ip]);
      $file->write( sprintf("%-15s %s\n", $ip, join(" ", $hosts[$ip])) );
    }
    $file->unlock();

  }
}
/*
$hosts = new Hosts();
$hosts->export('/tmp/hosts');
*/

?>
