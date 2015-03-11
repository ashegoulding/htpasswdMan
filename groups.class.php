<?php

namespace ashe;

class Groups
{
	protected $fh = NULL;
	protected $closeOnDelete = true;
	protected $groupArray = NULL;
	
	protected function __write($str, $len = NULL)
	{
		if($len === NULL)
			fwrite($this->fh, $str);
		else
			fwrite($this->fh, $str, $len);
	}
	
	public function __construct($file/* Path string or file handle that was by fopen() */, $closeOnDelete = true)
	// Throws InvalidArgumentException
	{
		if(is_string($file))
		{
			try
			{
				$this->fh = fopen($file, "r+");
				if(! $this->fh)
					throw new \Exception();
			}
			catch(\Exception $e)
			{
				try
				{
					$this->fh = fopen($file, "w");
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
		
		$this->closeOnDelete = $closeOnDelete;
		$this->load();
	}
	
	public function getFileHandle()
	{
		return $this->fh;
	}
	
	public function commit()
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
	{
		$this->truncate(); 
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
	
	public function getEntireGroup()
	{
		$y = array();
		foreach($this->fh as $g=>$u)
			$y[$g] = $u;
		
		return $y;
	}
	public function getGroups()
	{
		$y = array();
		foreach($this->groupArray as $k=>$v)
			array_push($y, $k);
		
		return $y;
	}
	public function getUsers()
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
	{
		if(array_key_exists($group, $this->groupArray))
			return array_merge(array(), $this->groupArray[$group]);
		throw new \OutOfBoundsException("Group '$group' does not exist.");
	}
	
	public function add($user, $group)
	{
		$user = trim($user);
		$group = trim($group);
		if(empty($user))
			throw new \InvalidArgumentException("Empty user string given.");
		else if(empty($group))
			throw new \InvalidArgumentException("Empty group string given.");
		
		if(! array_key_exists($group, $this->groupArray))
			$this->groupArray[$group] = array();
		
		if($this->isInGroup($user, $group))
			return false;
		else
		{
			array_push($this->groupArray[$group], $user);
			return true;
		}
	}
	
	public function doesUserExist($user)
	{
		foreach($this->groupArray as $g)
		{
			if(array_search($user, $g) !== false)
				return true;
		}
		return false;
	}
	public function doesGroupExist($group)
	{
		return array_key_exists($group, $this->groupArray);
	}
	public function isInGroup($user, $group)
	{
		if(! $this->doesGroupExist($group))
			throw new \InvalidArgumentException("Group '$group' does not exist.");
		else if(! $this->doesUserExist($user))
			throw new \InvalidArgumentException("User '$user' does not exist.");
		
		return array_search($user, $this->groupArray[$group]) !== false;
	}
	public function getBelongingGroups($user)
	{
		$y = array();
		foreach($this->groupArray as $g)
		{
			if(($k = array_search($user, $g)) !== false)
				array_push($y, $g[$k]);
		}
		
		return $y;
	}
	public function remove($user, $group)
	{
		if($this->isInGroup($user, $group))
			unset($this->groupArray[$group][array_search($user, $this->groupArray[$group])]);
		else
			throw new \InvalidArgumentException("User '$user' is not in the group '$group'");
	}
	public function removeFromAll($user)
	{
		foreach($this->groupArray as $g=>$u)
		{
			if(($k = array_search($user, $u)) !== false)
				unset($this->groupArray[$g][$k]);
		}
	}
	public function removeGroup($group)
	{
		if($this->doesGroupExist($group))
			unset($this->groupArray[$group]);
		else
			throw new \InvalidArgumentException("Group '$group' does not exist.");
	}
	public function truncate()
	{
		$this->groupArray = array();
	}
}

?>
