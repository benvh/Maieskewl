<?php
	//some simple example...
	include "Maieskewl/RecordBase.php";
	
	class Book extends RecordBase{
	}
	

	$myBook = Database::find(Book, 1);
	echo $myBook->title . "<br/>" . $myBook->description;
	
	$myBook->title = "Some new title";
	$myBook->save;


	//Database will have a table called "books" not "book"
	//
	//SELECT * FROM books
	// +----+----------------+------------------+
	// | id | title          | description      |
	// +----+----------------+------------------+
	// |  1 | Some new title | some description | 
	// +----+----------------+------------------+
	//
	//(don't forget to config c.ini)
?>