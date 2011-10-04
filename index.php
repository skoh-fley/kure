<?php

/* LICENSE INFORMATION
 * kure is distributed under the terms of the GNU General Public License
 * (http://www.gnu.org/licenses/gpl.html).
 * kure Copyright 2007-2011 Ben Carlsson
 * 
 * This file is part of kure.
 * 
 * kure is free software: you can redistribute it and/or modify it under the
 * terms of the * GNU General Public License as published by the Free Software
 * Foundation, either version 3 of the License, or (at your option) any later
 * version.
 * 
 * kure is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; * without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more
 * details.
 * 
 * You should have received a copy of the GNU General Public License along with
 * kure. If not, see <http://www.gnu.org/licenses/>.
 * 
 * NOTES
 * kure is in BETA. Be aware that there may be BUGS and/or SECURITY HOLES
 * in the engine, and that you are using it at your own risk. Please be
 * cautious.
 * 
 * I graciously accept bug reports and suggestions for kure's engine. Visit
 * kure's repo at github.com/skoh-fley/kure. My email is breachreach@gmail.com
 * if you wish to reach me directly.
 *  
 * If you wish to remove the footer's "powered by kure" text, feel free. I
 * always appreciate recognition somewhere, but I'll let you decide what's fair.
 * :P
 */

// Tell all files to include relative to THIS FILE's directory
set_include_path(dirname($_SERVER['SCRIPT_FILENAME']));

// Autoload any class which is used in this file
function __autoload($class) {
  
  include 'classes/' . $class . '.php';

}

if(!Config::load())
  Engine::quit('<p>It looks like you haven\'t installed kure yet!<br/>Proceed to <a href="install.php">installation</a> if you need to install.<br/>If you don\'t, be sure to make sure your kure-related directories exist.</p>');

Template::run('header');

/***** DOCS *****/
if(isset($_GET['docs'])) { // if we're at the docs page

  Template::run('doclist_header');
  $alldocs = glob('docs/*.txt');

  if(!Config::$abcdocs) // if we're NOT sorting alphabetically
    usort($alldocs, 'sort_by_mtime');

  if(count($alldocs) == 0) {

    print('<i>&lt;no docs to display&gt;</i>');

  } else {

    foreach($alldocs as $file) {

      $title = $file;
      $title = str_replace('docs/', '', $title);
      $title = str_replace('.txt', '', $title);
      $uftitle = $title;
      $id = md5($title); // dynamic hook identifier
      $title = deparse_title($title);
      Template::run('doclist', array('id' => $id, 'DOCTITLE' => $title, 'DOCADDRESS' => $uftitle, 'DOCDATE' => date('F jS, Y', filemtime($file))));

    }

  }

  Template::run('doclist_footer');

}

/***** VIEWPOST/VIEWDOC *****/
elseif(isset($_GET['post']) || isset($_GET['doc'])) { // if a post/doc has been requested

  if(isset($_GET['post'])) {

    $type = 'post';
    $filename = $_GET['post'];

  } else {

    $type = 'doc';
    $filename = $_GET['doc'];

  }

  $filename = sanitize($filename);
  print plug($type, 'top');

  if(!file_exists($type . 's/' . $filename . '.txt')) {

    print 'The requested file <tt>' . $type . 's/' . $filename . '.txt</tt> does not exist.';

  } else {

    $file    = $type . 's/' . $filename . '.txt';
    $title   = $file;
    $title   = str_replace($type . 's/', '', $title);
    $title   = str_replace('.txt', '', $title);
    $uftitle = $title;
    $title   = str_replace('_', ' ', $title);
    $content = str_replace('\n', '<br/>', file_get_contents($file));
    
    Template::run('entry', array('ENTRYTYPE' => $type, 'ENTRYTITLE' => $title, 'ENTRYADDRESS' => $uftitle, 'ENTRYDATE' => date('F jS, Y', filemtime($file)), 'ENTRYCONTENT' => $content));

  }

}

/***** POSTS *****/
else { // if we weren't told to do anything else

  Template::run('postlist_header');

  if(!isset($_GET['page']))
    $_GET['page'] = 1; // default to page 1

  $postHandler = new PostHandler();

  $allposts = glob('posts/*.txt');
  $total_posts = count($allposts);

  // if the total number of posts isn't divisible by the number we want to display,
  // then we want to make $total_posts / Config::$postsPerPage round up one. (think it out.) this is for pagination.
  if($total_posts % Config::$postsPerPage != 0)
    $total_posts += Config::$postsPerPage;
  
  if(!Config::$abcPosts) // if we're NOT sorting alphabetically
    usort($allposts, 'sort_by_mtime');

  $page_firstpost = ($_GET['page'] * Config::$postsPerPage) - Config::$postsPerPage;
  $curpost = 0;
  $i = 0; // monitor how many posts we display

  if(count($allposts) == 0) {

    print '<i>&lt;no posts to display&gt;</i>';

  } else {

    foreach($allposts as $file) {

      if($i == Config::$postsPerPage && Config::$postsPerPage != 0)
        break;

      if(isset($_GET['page']) && ($curpost < $page_firstpost) || ($curpost > ($page_firstpost + Config::$postsPerPage))) {

        $curpost++;
        continue;

      }

      $title = $file;
      $title = str_replace('posts/', '', $title);
      $title = str_replace('.txt', '', $title);
      $uftitle = $title;
      $id = md5($title); // dynamic hook identifier
      $title = deparse_title($title);
      $content = str_replace("\n", '<br/>' . "\n", file_get_contents($file));
      
      Template::run('postlist', array('id' => $id, 'POSTTITLE' => $title, 'POSTADDRESS' => $uftitle, 'POSTDATE' => date('F jS, Y', filemtime($file)), 'POSTCONTENT' => $content));
      
      $i++;

    }

  }

  if(Config::$postsPerPage != 0 && $total_posts > Config::$postsPerPage) {

    if($_GET['page'] + 1 <= $total_posts / Config::$postsPerPage)
      print '<a class="navitem" href="?page=' . ($_GET['page'] + 1) . '"><font size="1">&lt;&lt;</font>previous posts</a>';
    
    if($_GET['page'] != 1) {

      // we check if we're on page 2, because the next page will be 1, aka homepage.
      if($_GET['page'] == 2)
        $next = './';
      else
        $next = '?page=' . ($_GET['page'] - 1);

      print '| <a class="navitem" href="' . $next . '">more recent posts<font size="1">&gt;&gt;</font></a>';

    }

  }

  Template::run('postlist_footer');

}

/***** DISASSEMBLY *****/
Template::run('footer');

?>
