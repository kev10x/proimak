<?php

/**
* Abstract class Command representing a command in a command menu
*/
class Command {
	var $text;
	var $enabled;

	function Command($text, $enabled, $sep=true) {
		//$this->text = str_replace(" ", "&nbsp;", $text);  don't need to do this because we're using <nobr></nobr> tags
		//<nobr> should not be used. it isn't a valid html tag.
		$this->text = $text;
		$this->enabled = $enabled;
		$this->wantsep = $sep;
	}

	function toString() {
		if (!$this->enabled)
			return "<nobr><span class=\"command_current\">$this->text</span></nobr>";
			//NOBR is not a valid html tag
      /**
			 *@todo - find a way to replace the nobr tags whilst keeping the menu icons next to the names
			 */       	
		else
			return $this->text;
	}

	function setEnabled($enabled) {
		$this->enabled = $enabled;
	}
}

/*	A class which represents a single command in a command menu.
*		It has a url and a visual reprenstation (text)
*/
class TextCommand extends Command {
	var $url;

	/**
	* Constructor
	*/
	function TextCommand($text, $enabled, $url) {
		parent::Command($text, $enabled);
		$this->url = $url;
	}

	function toString() {
		if (!$this->enabled)
			return parent::toString();
		else
			return "<nobr><a href=\"" . $this->url . "\" class=\"command\">" . $this->text . "</a></nobr>";
			//NOBR is not a valid html tag
			/**
			 *@todo - find a way to replace the nobr tags whilst keeping the menu icons next to the names
			 */       	
	}
}

class IconTextCommand extends TextCommand {
	var $img;

	/**
	* Constructor
	*/
	function IconTextCommand($text, $enabled, $url, $img) {
		parent::TextCommand($text, $enabled, $url);
		$this->img = $img;
	}

	function toString() {
		if (!file_exists($this->img))
			return parent::toString();
		else
			return "<nobr><a href=\"" . $this->url . "\" class=\"command\"><img src=\"" . $this->img . "\" alt=\"\" border=\"0\" align=\"absbottom\" />" . $this->text . "</a></nobr>";
			//NOBR is not a valid html tag
			/**
			 *@todo - find a way to replace the nobr tags whilst keeping the menu icons next to the names
			 */       	
	}
}

/*	A class representing a menu of commands.
*		It's responsible for printing the menu with a separator
*/
class CommandMenu {

	//array which holds the commands in the menu
	var $commands = array();

	/* adds a command to the menu */
	function add($command) {
		$this->commands[] = $command;
	}

	/* returns the command menu as html */
	function toString() {
		$printedFirstCommand = false;
		$returnString = "";

		//iterate through commands
		$count = count($this->commands);
		for ($i=0; $i < $count; $i++) {
			//append this command to the string
			$returnString = $returnString . $this->commands[$i]->toString();
			if($this->commands[$i]->wantsep)
				$returnString = $returnString . "&nbsp;&nbsp; ";
		}
		//return the command menu as a string
		return $returnString;
	}

	/**
	* Disables a menu command with the given text
	*/
	function disableCommand($text) {
		//iterate through commands
		$count = count($this->commands);
		for ($i=0; $i < $count; $i++) {
			if ($this->commands[$i]->text == $text)
				$this->commands[$i]->setEnabled(false);
		}
	}

	function disableSelf() {
		//iterate through commands
		$count = count($this->commands);
		for ($i=0; $i < $count; $i++) {
			$self = $_SERVER["PHP_SELF"];
			$slashPos = strrpos($self, "/");
			if (!is_bool($slashPos))
				$self = substr($self, $slashPos + 1);
			$url = empty($this->commands[$i]->url) ? 'noURL' : $this->commands[$i]->url;
			$pos = strpos($url, $self);
			if (!is_bool($pos) && $pos == 0)
				$this->commands[$i]->setEnabled(false);
		}
	}
}

//create the command menu object so that those files which include this one dont need to
$commandMenu = new CommandMenu;

// vim:ai:ts=4:sw=4
?>
