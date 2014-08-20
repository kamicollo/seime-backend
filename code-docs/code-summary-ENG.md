Seime.lt code is licenced under Creative Commons BY-NC-SA 3.0 licence:
http://creativecommons.org/licenses/by-nc-sa/3.0/

## DOCUMENTATION OF SEIME.LT CODE  a.k.a BEWARE THERE BE DRAGONS ##

Unfortunately, a full documentation of the code is not yet ready (and, to be honest,
chances are it will not be for a long time). Thus, the navigation through the
code will be mostly up to the reader. Nevertheless, we have a brief summary of what 
you can expect. You are always welcome to shoot us an email to info@seime.lt and
we'll do our best to help you out!

### STRUCTURE OF CODE ###

- The core of the code is in the folder `classes/`. We note that it's the first 
project where we practically tried to apply OOP concepts, so you'll find a lot of
high-coupling and low-cohesion examples. In any way, the following principles will
largely hold:

    - Factory class is responsible for manipulating objects' data in the dabatse, creating objects from DB as well as traversing the main object tree.	
    - Each of the Seimas work objects (session, sitting, question, action) has its own class.
    - Each of the Seimas work objects is a child of the HTMLObject class (abstractions.php), which contains common methods as well defines the overall structure of the way	the objects are constructed. 
    - utilities.php file contains various helper functions.

- Folder `extensions/` contains classes that add extra functionality to the core object
classes. That is, classes in the `classes/` folder use only the oficial data from the 
Lithuanian Seimas website, whereas `extensions/` classes add additional calculations 
(such as participation data estimation on sub-question level). You can define which 
classes are used in the tree on runtime, by passing parameters to Factory class.
	
- Folder `cache/` contains all the HTML files downloaded from http://lrs.lt.
The caching mechanism is implemented in the Utilities class (classes/abstractions.php).

- Folder `sqls/` contains SQL queries, which are used to populate some of the  
SQL tables with additional data. They are used solely by `classes/Updater.php` class.
	
### RUNNING THE CODE ###
	
If you want to jump right away, all you need to do is create a session object
(you can, actually, start at sitting / question level, too) and initialise it:
```php
<?php	
  $s = $Factory->getObject('session', SESSION_URL); 
  //SESSION_URL looks like this: http://www3.lrs.lt/pls/inter/w5_sale.ses_pos?p_ses_id=91
  $s->scrapeData(true); // TRUE = force to redownload data
  $this->session->initialise(); //Initialise the session object (populate the fields from HTML)
  $this->session->initialiseChildren(true); //Recursively populate all children
  $s->saveData();
?>
```
However, this will only collect and save to DB the main data. The additional calculations
will not be present.

For a full information collection / update example, see the file `update-ENG.php` &
the Updater class located at `classes/Updater.php`. 
