<?php
/* Originally from http://innvo.com/1311865299-htpasswd-manager
 * 
 * Slightly changed by Ashe.Goulding@gmail.com
 */

class htpasswd {
	var $fp;
	var $filename;
 
	function htpasswd($filename) {	
		try
		{
			if(! ($this->fp = fopen($filename,'r+')))
				throw new Exception();
		}
		catch(Exception $e)
		{
			if(!( $this->fp = fopen($filename, "w")))
				throw new Exception("Could not create a file: " . $filename);
		}
		$this->filename = $filename;
	}
	public function __destruct()
	{
		fclose($this->fp);
	}
 
	function user_exists($username) {
		rewind($this->fp);
			while(!feof($this->fp) && trim($lusername = array_shift(explode(":",$line = rtrim(fgets($this->fp)))))) {
				if($lusername == $username)
					return 1;
			}
		return 0;
	}
 
	function user_add($username,$password) {
		if($this->user_exists($username))
			return false;
		fseek($this->fp,0,SEEK_END);
		fwrite($this->fp,$username.':'.crypt($password,substr(str_replace('+','.',base64_encode(pack('N4', mt_rand(),mt_rand(),mt_rand(),mt_rand()))),0,22))."\n");
		return true;
	}
 
	function user_delete($username) {
		$data = '';
		rewind($this->fp);
		while(!feof($this->fp) && trim($lusername = array_shift(explode(":",$line = rtrim(fgets($this->fp)))))) {
			if(!trim($line))
				break;
			if($lusername != $username)
			$data .= $line."\n";
		}
		$this->fp = fopen($this->filename,'w');
		fwrite($this->fp,rtrim($data).(trim($data) ? "\n" : ''));
		fclose($this->fp);
		$this->fp = fopen($this->filename,'r+');
		return true;
	}
 
	function user_update($username,$password) {
		rewind($this->fp);
			while(!feof($this->fp) && trim($lusername = array_shift(explode(":",$line = rtrim(fgets($this->fp)))))) {
				if($lusername == $username) {
					fseek($this->fp,(-15 - strlen($username)),SEEK_CUR);
					fwrite($this->fp,$username.':'.crypt($password,substr(str_replace('+','.',base64_encode(pack('N4', mt_rand(),mt_rand(),mt_rand(),mt_rand()))),0,22))."\n");
					return true;
				}
			}
		return false;
	}
	
	function getAllUsers()
	{
		$y = array();
		rewind($this->fp);
		while(($line = fgets($this->fp)) !== false)
		{
			$line = trim($line);
			if(empty($line))
				continue;
			array_push($y, array_values(explode(":", $line))[0]);
		}
		
		return $y;
	}
}
?>
