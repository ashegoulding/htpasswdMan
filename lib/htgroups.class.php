<?php

namespace ashe;

class Groups
{
	protected $fh = NULL;
	protected $groupArray = NULL;
	
	public $closeOnDelete = true;
	public $commitOnDelete = false;
	
	protected function __write($str, $len = NULL)
	// Could throw from fwrite(): IOException
	{
		if($len === NULL)
			fwrite($this->fh, $str);
		else
			fwrite($this->fh, $str, $len);
	}
	
	public function __construct($file/* Path string or file handle that was by fopen() */)
	// Throws InvalidArgumentException
	{
		if(is_string($file))
		{
			try
			{
				$this->fh = @fopen($file, "r+");
				if(! $this->fh)
					throw new \Exception();
			}
			catch(\Exception $e)
			{
				try
				{
					$this->fh = @fopen($file, "w");
					if(! $this->fh)
						throw new \Exception();
				}
				catch(\Exception $e)
				{
					throw new \Exception("Could not create a file that couldn't read.");
				}
			}
		}
		else if($file)
		{
			try
			{
				if(! fstat($file))
					throw new \Exception();
				$this->fh = $file;
			}
			catch(\Exception $e)
			{
				throw new \InvalidArgumentException("The argument 'file' is not a valid file pointer.");
			}
		}
		else
			throw new \InvalidArgumentException("Illegal argument 'file'");
		
		$this->load();
	}
	
	public function __destruct()
	// No throw guaranteed
	{
		try
		{
			if($this->commitOnDelete)
				$this->commit();			
		}
		catch(\Exception $e){}
		try
		{
			if($this->closeOnDelete)
				fclose($this->fh);			
		}
		catch(\Exception $e){}
	}
	
	public function getFileHandle() // No throw
	{
		return $this->fh;
	}
	
	public function commit()
	// Throw from rewind(), ftruncate(), __write(), fseek(): IOException
	{
		rewind($this->fh);
		ftruncate($this->fh, 0);
		
		foreach($this->groupArray as $name=>$users)
		{
			if(! count($users))
				continue;
			
			$this->__write($name . ":");
			foreach($users as $v)
			{
				$v = trim($v);
				if(empty($v))
					continue;
				$this->__write($v . " ");
			}
			fseek($this->fh, -1, SEEK_CUR);
			$this->__write("\n");
		}
	}
	public function load()
	// Throw from rewind()
	// Throws Exception when a group row duplicates
	{
		$this->truncate();
		rewind($this->fh);
		
		while(($line = fgets($this->fh)) !== false)
		{
			$line = trim($line);
			if(empty($line))
				continue;
			$exploded = array_values(explode(":", $line));
			$name = $exploded[0];
			$users = array();
			if(isset($exploded[1]))
			{
				$exploded = array_values(explode(" ", $exploded[1]));
				foreach($exploded as $v)
				{
					$v = trim($v);
					if(empty($v))
						continue;
					array_push($users, $v);
				}
			}
			
			if(array_key_exists($name, $users))
				throw new \Exception("Invalid file. Group '$name' appeared again.");
			$this->groupArray[$name] = $users;
		}
	}
	
	public function getEntireGroup() // No throw
	{
		$y = array();
		foreach($this->groupArray as $g=>$u)
			$y[$g] = $u;
		
		return $y;
	}
	public function getGroups() // No throw
	{
		$y = array();
		foreach($this->groupArray as $k=>$v)
			array_push($y, $k);
		
		return $y;
	}
	public function getUsers() // No throw
	{
		$x = array();
		foreach($this->groupArray as $g)
		{
			foreach($g as $u)
				$x[$u] = NULL;
		}
		
		$y = array();
		foreach($x as $k=>$v)
			array_push($y, $k);
		
		return $y;
	}
	public function getGroup($group)
	// Throws OutOfBoundsException when the given group doesn't exist. 
	{
		if(array_key_exists($group, $this->groupArray))
			return array_merge(array(), $this->groupArray[$group]);
		throw new \OutOfBoundsException("Group '$group' does not exist.");
	}
	
	public function add($user, $group)
	// Throws InvalidArgumentException when the arguments were not fully given.
	// Other than that, no thow guaranteed
	{
		$user = trim($user);
		$group = trim($group);
		if(empty($user))
			throw new \InvalidArgumentException("Empty user string given.");
		else if(empty($group))
			throw new \InvalidArgumentException("Empty group string given.");
		
		if(! array_key_exists($group, $this->groupArray))
			$this->groupArray[$group] = array();
		
		try
		{
			if($this->isInGroup($user, $group))
				return false;
		}
		catch(\InvalidArgumentException $e){}
		
		array_push($this->groupArray[$group], $user);
		return true;
	}
	
	public function doesUserExist($user) // No throw
	{
		foreach($this->groupArray as $g)
		{
			if(array_search($user, $g) !== false)
				return true;
		}
		return false;
	}
	public function doesGroupExist($group) // No throw
	{
		return array_key_exists($group, $this->groupArray);
	}
	public function isInGroup($user, $group)
	// Throws InvalidArgumentException when given group or user doesn't exist
	// No throw, elsewhen
	{
		if(! $this->doesGroupExist($group))
			throw new \InvalidArgumentException("Group '$group' does not exist.");
		else if(! $this->doesUserExist($user))
			throw new \InvalidArgumentException("User '$user' does not exist.");
		
		return array_search($user, $this->groupArray[$group]) !== false;
	}
	public function getBelongingGroups($user)
	// Throws InvalidArgumentException when the given user does not exist.
	{
		if(! $this->doesUserExist($user))
			throw new \InvalidArgumentException("The user '$user' does not exist.");
		$y = array();
		
		foreach($this->groupArray as $g=>$u)
		{
			if(array_search($user, $u) !== false)
				array_push($y, $g);
		}
		
		return $y;
	}
	public function remove($user, $group)
	// Throws InvalidArgumentException when the given user is not in the given group.
	{
		if($this->isInGroup($user, $group))
		{
			unset($this->groupArray[$group][array_search($user, $this->groupArray[$group])]);
			if(empty($this->groupArray[$group]))
				unset($this->groupArray[$group]);
		}
		else
			throw new \InvalidArgumentException("User '$user' is not in the group '$group'");
	}
	public function removeFromAll($user)
	// Throws InvalidArgumentException when the given user does not exist.
	{
		if(! $this->doesUserExist($user))
			throw new \InvalidArgumentException("The user '$user' does not exist.");
		
		foreach($this->groupArray as $g=>$u)
		{
			if(($k = array_search($user, $u)) !== false)
			{
				unset($this->groupArray[$g][$k]);
				if(empty($this->groupArray[$g]))
					unset($this->groupArray[$g]);
			}
		}
	}
	public function removeGroup($group)
	// Throws InvalidArgumentException when the given group does not exist.
	{
		if($this->doesGroupExist($group))
			unset($this->groupArray[$group]);
		else
			throw new \InvalidArgumentException("Group '$group' does not exist.");
	}
	public function truncate() // No throw
	{
		$this->groupArray = array();
	}
}

?>
